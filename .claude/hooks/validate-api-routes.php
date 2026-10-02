<?php

/**
 * PostToolUse hook: enforces .ai/rules/views.md's "Call REST API endpoints
 * through resources/js/routes.js" rule.
 *
 * Reads the Claude Code hook payload (tool_name/tool_input JSON) from STDIN,
 * runs scripts/check-api-routes.js when the edited file is under
 * resources/js, and prints a hook-JSON response if a hardcoded `/api/...`
 * path is found outside resources/js/routes.js.
 */

$stdin = stream_get_contents(STDIN);
$payload = json_decode($stdin, true) ?? [];

$filePath = $payload['tool_input']['file_path']
    ?? $payload['tool_response']['filePath']
    ?? null;

$normalized = $filePath ? str_replace('\\', '/', $filePath) : null;

if (! $normalized || ! str_contains($normalized, 'resources/js/') || ! str_ends_with($normalized, '.js')) {
    exit(0);
}

$output = [];
$exitCode = 0;
exec('node scripts/check-api-routes.js 2>&1', $output, $exitCode);

if ($exitCode === 0) {
    exit(0);
}

$message = implode("\n", $output);

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
