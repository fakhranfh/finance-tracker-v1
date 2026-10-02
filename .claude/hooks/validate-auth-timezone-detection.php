<?php

/**
 * PostToolUse hook: validates that Auth controllers detect and persist the
 * user's timezone from their IP address on login, per .ai/rules/auth.md.
 *
 * Reads the Claude Code hook payload (tool_name/tool_input JSON) from STDIN,
 * static-checks the edited file if it lives under app/Http/Controllers/Auth,
 * and prints a hook-JSON response with any violations found.
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

if (! str_contains($normalized, '/app/Http/Controllers/Auth/')) {
    exit(0);
}

$code = file_get_contents($filePath);

$violations = validateAuthController($code);

if (empty($violations)) {
    exit(0);
}

$list = implode("\n", array_map(fn ($v) => "- {$v}", $violations));
$message = "Timezone rule check (.ai/rules/auth.md) found issues in {$filePath}:\n{$list}";

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
function validateAuthController(string $code): array
{
    $violations = [];

    // Only applies to files that actually handle a login action.
    if (! preg_match('/function\s+\w*[Ll]ogin\w*\s*\(/', $code)) {
        return $violations;
    }

    $mentionsIp = preg_match('/->\s*ip\s*\(\s*\)|getClientIp|REMOTE_ADDR/', $code);
    $mentionsTimezone = preg_match('/timezone/i', $code);

    if (! $mentionsIp || ! $mentionsTimezone) {
        $violations[] = 'Login method does not appear to detect the user\'s timezone from their IP address; on every login, detect the timezone via IP geolocation and save it to the `timezone` column.';
    }

    return $violations;
}
