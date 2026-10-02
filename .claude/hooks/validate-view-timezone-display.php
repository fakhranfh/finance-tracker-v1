<?php

/**
 * PostToolUse hook: validates that Blade templates display datetime/timestamp
 * values converted to the authenticated user's timezone, per
 * .ai/rules/views.md.
 *
 * Reads the Claude Code hook payload (tool_name/tool_input JSON) from STDIN,
 * static-checks the edited file if it is a *.blade.php file, and prints a
 * hook-JSON response with any violations found.
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

$violations = validateView($code);

if (empty($violations)) {
    exit(0);
}

$list = implode("\n", array_map(fn ($v) => "- {$v}", $violations));
$message = "Timezone rule check (.ai/rules/views.md) found issues in {$filePath}:\n{$list}";

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
function validateView(string $code): array
{
    $violations = [];

    $datetimeFields = ['created_at', 'updated_at', 'deleted_at', 'email_verified_at', 'last_login_at'];
    $pattern = '/\{\{\s*\$?\w+(?:->\w+)*->(' . implode('|', $datetimeFields) . ')(?!->for_user_timezone|->userTimezone)\s*(?:->format\([^)]*\))?\s*\}\}/';

    if (preg_match($pattern, $code, $m)) {
        $violations[] = "Displays `->{$m[1]}` directly without a timezone-converted accessor; use the HasUserTimezone trait accessor (e.g. `->{$m[1]}_for_user_timezone` or equivalent) instead of raw UTC datetime.";
    }

    return array_unique($violations);
}
