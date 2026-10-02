<?php

/**
 * PostToolUse hook: validates that password rules use `Password::defaults()`
 * and that the strong policy is defined in AppServiceProvider, per
 * .ai/rules/password-validation.md.
 *
 * Reads the Claude Code hook payload (tool_name/tool_input JSON) from STDIN,
 * static-checks the edited file, and prints a hook-JSON response with any
 * violations found.
 */

$stdin = stream_get_contents(STDIN);
$payload = json_decode($stdin, true) ?? [];

$filePath = $payload['tool_input']['file_path']
    ?? $payload['tool_response']['filePath']
    ?? null;

if (! $filePath || ! is_string($filePath) || ! str_ends_with($filePath, '.php') || ! is_file($filePath)) {
    exit(0);
}

$normalized = str_replace('\\', '/', $filePath);
$code = file_get_contents($filePath);

if (str_contains($normalized, '/app/Http/Requests/')) {
    $violations = validateRequest($code, basename($filePath));
} elseif (str_ends_with($normalized, '/app/Providers/AppServiceProvider.php')) {
    $violations = validateProvider($code);
} else {
    exit(0);
}

if (empty($violations)) {
    exit(0);
}

$list = implode("\n", array_map(fn ($v) => "- {$v}", $violations));
$message = "Password rule check (.ai/rules/password-validation.md) found issues in {$filePath}:\n{$list}";

echo json_encode([
    'systemMessage' => $message,
    'hookSpecificOutput' => [
        'hookEventName' => 'PostToolUse',
        'additionalContext' => $message,
    ],
]);

exit(0);

/**
 * @return array<int, string>
 */
function validateRequest(string $code, string $file): array
{
    // Login only checks presence, so existing users are never locked out.
    if ($file === 'LoginRequest.php') {
        return [];
    }

    $violations = [];

    if (! preg_match('/[\'"]password[\'"]\s*=>\s*\[?([^\n]*)/', $code, $m)) {
        return $violations;
    }

    $rule = $m[1];

    if (! str_contains($rule, 'Password::defaults()')) {
        $violations[] = 'The `password` rule must use `Password::defaults()`.';
    }

    if (preg_match('/Password::min\(|[\'"]min:\d+[\'"]/', $rule)) {
        $violations[] = 'Do not hardcode `min:N` or a custom `Password::min()` chain; change the policy in AppServiceProvider only.';
    }

    return $violations;
}

/**
 * @return array<int, string>
 */
function validateProvider(string $code): array
{
    $violations = [];

    if (! str_contains($code, 'Password::defaults(')) {
        return ['AppServiceProvider::boot() must define `Password::defaults()` with the strong password policy.'];
    }

    foreach (['letters', 'mixedCase', 'numbers', 'symbols'] as $method) {
        if (! str_contains($code, "->{$method}()")) {
            $violations[] = "Password::defaults() policy is missing `->{$method}()`.";
        }
    }

    return $violations;
}
