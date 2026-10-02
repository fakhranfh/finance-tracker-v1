<?php

/**
 * PostToolUse hook: enforces .ai/rules/views.md's "No computed PHP inside
 * Blade files" rule.
 *
 * Reads the Claude Code hook payload (tool_name/tool_input JSON) from STDIN,
 * static-checks the edited file if it is a *.blade.php file, and blocks if
 * it contains an `@php ... @endphp` block or a raw `<?php` tag.
 */

$stdin = stream_get_contents(STDIN);
$payload = json_decode($stdin, true) ?? [];

$filePath = $payload['tool_input']['file_path']
    ?? $payload['tool_response']['filePath']
    ?? null;

if (! $filePath || ! is_string($filePath) || ! str_ends_with($filePath, '.blade.php') || ! is_file($filePath)) {
    exit(0);
}

$code = file_get_contents($filePath);
$violations = [];

if (preg_match('/@php\b/', $code) === 1) {
    $violations[] = 'Found an `@php ... @endphp` block; move the computed logic into a class-based Blade component (app/View/Components/**) or a view composer instead.';
}

if (preg_match('/<\?php/', $code) === 1) {
    $violations[] = 'Found a raw `<?php` tag; move the computed logic into a class-based Blade component (app/View/Components/**) or a view composer instead.';
}

if (empty($violations)) {
    exit(0);
}

$list = implode("\n", array_map(fn ($v) => "- {$v}", $violations));
$message = "No-computed-PHP-in-Blade check (.ai/rules/views.md) found issues in {$filePath}:\n{$list}";

echo json_encode([
    'decision' => 'block',
    'reason' => $message,
    'systemMessage' => $message,
    'hookSpecificOutput' => [
        'hookEventName' => 'PostToolUse',
        'additionalContext' => $message,
    ],
]);

exit(0);
