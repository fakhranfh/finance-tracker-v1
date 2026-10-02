<?php

/**
 * PostToolUse hook: validates that Eloquent models handling datetime fields
 * use the `HasUserTimezone` trait, per .ai/rules/models.md.
 *
 * Reads the Claude Code hook payload (tool_name/tool_input JSON) from STDIN,
 * static-checks the edited file if it lives under app/Models, and prints a
 * hook-JSON response with any violations found.
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

if (! str_contains($normalized, '/app/Models/')) {
    exit(0);
}

$code = file_get_contents($filePath);

$violations = validateModel($code);

if (empty($violations)) {
    exit(0);
}

$list = implode("\n", array_map(fn ($v) => "- {$v}", $violations));
$message = "Timezone rule check (.ai/rules/models.md) found issues in {$filePath}:\n{$list}";

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
function validateModel(string $code): array
{
    $violations = [];

    // Only flag models that actually deal with datetime fields.
    $handlesDatetime = preg_match('/\$casts\s*=.*(datetime|date|immutable_datetime|immutable_date)/is', $code)
        || preg_match('/protected\s+function\s+casts\s*\(/', $code)
        || preg_match('/CREATED_AT|UPDATED_AT|\$dates\s*=/', $code);

    if (! $handlesDatetime) {
        return $violations;
    }

    if (! preg_match('/use\s+HasUserTimezone\s*;/', $code)) {
        $violations[] = 'Model handles datetime fields but does not `use HasUserTimezone;`; every model with datetime fields must use this trait to convert values to the authenticated user\'s timezone.';
    }

    return $violations;
}
