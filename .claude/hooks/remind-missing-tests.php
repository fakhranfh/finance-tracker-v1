<?php

/**
 * PostToolUse hook: reminds about .ai/rules/testing.md's "Never finish a
 * feature without its Pest test AND its Playwright spec" rule.
 *
 * Fires when a NEW file is written (not edited, to avoid nagging on every
 * touch-up) under app/Http/Controllers/**, app/Services/**, or a page view
 * in resources/views/**. Reminds (non-blocking) if no matching test file is
 * found under tests/Feature/ (Pest) or tests/playwright/ (Playwright).
 */
$stdin = stream_get_contents(STDIN);
$payload = json_decode($stdin, true) ?? [];

$filePath = $payload['tool_input']['file_path']
    ?? $payload['tool_response']['filePath']
    ?? null;

if (! $filePath || ! is_string($filePath) || ! is_file($filePath)) {
    exit(0);
}

$normalized = str_replace('\\', '/', $filePath);

if (! preg_match('#/(.+)$#', $normalized, $rootMatch)) {
    exit(0);
}

$projectRoot = preg_replace('#/(app|resources|tests)/.*$#', '', $normalized);

function emit(string $filePath, string $message): void
{
    $full = "Testing rule (.ai/rules/testing.md): {$filePath} {$message}";

    echo json_encode([
        'systemMessage' => $full,
        'hookSpecificOutput' => [
            'hookEventName' => 'PostToolUse',
            'additionalContext' => $full,
        ],
    ]);
}

function entityNameFromController(string $className): string
{
    return preg_replace('/(Page)?Controller$/', '', $className);
}

// --- PHP controllers/services -> tests/Feature/{Entity}Test.php ---
if (str_ends_with($normalized, '.php')
    && (str_contains($normalized, '/app/Http/Controllers/') || str_contains($normalized, '/app/Services/'))
) {
    $code = file_get_contents($filePath);

    if (! preg_match('/\bclass\s+(\w+)/', $code, $m) || preg_match('/\babstract\s+class\s+\w+/', $code)) {
        exit(0);
    }

    $className = $m[1];
    $entity = entityNameFromController(preg_replace('/Service$/', '', $className));

    if ($entity === '') {
        exit(0);
    }

    $candidates = glob("{$projectRoot}/tests/Feature/*{$entity}*Test.php") ?: [];

    if (empty($candidates)) {
        emit($filePath, 'is a new class with no matching Pest test found under tests/Feature/ (expected something '
            ."like tests/Feature/{$entity}Test.php). Add a Pest test covering its behavior (happy path + "
            .'validation/auth edge cases for API endpoints) before considering this feature done.');
    }

    exit(0);
}

// --- New Blade page view -> tests/playwright/{page}.spec.ts ---
if (str_ends_with($normalized, '.blade.php')
    && str_contains($normalized, '/resources/views/')
    && ! str_contains($normalized, '/resources/views/components/')
    && ! str_contains($normalized, '/resources/views/layouts/')
) {
    preg_match('#/resources/views/(.+)\.blade\.php$#', $normalized, $m);
    $viewName = $m[1] ?? null;

    if (! $viewName) {
        exit(0);
    }

    $slug = basename($viewName);
    $candidates = glob("{$projectRoot}/tests/playwright/*{$slug}*.spec.ts") ?: [];

    if (empty($candidates)) {
        emit($filePath, 'is a new page view with no matching Playwright spec found under tests/playwright/ '
            ."(expected something like tests/playwright/{$slug}.spec.ts). Add a spec covering the happy path "
            .'and the auth-guard redirect (if protected) before considering this feature done.');
    }

    exit(0);
}

exit(0);
