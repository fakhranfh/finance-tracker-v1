<?php

/**
 * PostToolUse hook: validates that the users table migration includes a
 * `timezone` column, per .ai/rules/migrations.md.
 *
 * Reads the Claude Code hook payload (tool_name/tool_input JSON) from STDIN,
 * static-checks the edited file if it is a users table migration, and prints
 * a hook-JSON response with any violations found.
 */

$stdin = stream_get_contents(STDIN);
$payload = json_decode($stdin, true) ?? [];

$filePath = $payload['tool_input']['file_path']
    ?? $payload['tool_response']['filePath']
    ?? null;

if (! $filePath || ! is_string($filePath) || ! preg_match('/_create_users_table\.php$/', $filePath) || ! is_file($filePath)) {
    exit(0);
}

$code = file_get_contents($filePath);

$violations = validateUsersMigration($code);

if (empty($violations)) {
    exit(0);
}

$list = implode("\n", array_map(fn ($v) => "- {$v}", $violations));
$message = "Timezone rule check (.ai/rules/migrations.md) found issues in {$filePath}:\n{$list}";

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
function validateUsersMigration(string $code): array
{
    $violations = [];

    if (! preg_match('/\$table\s*->\s*\w+\s*\(\s*[\'"]timezone[\'"]/', $code)) {
        $violations[] = 'Missing `timezone` column; users table migrations must always include a `timezone` column to store each user\'s timezone preference.';
    }

    return $violations;
}
