<?php

/**
 * PostToolUse hook: enforces AGENTS.md's "always keep JS in separate .js
 * files, never inline <script> blocks inside Blade templates" rule.
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

$violations = validateNoInlineScript($code);

if (empty($violations)) {
    exit(0);
}

$list = implode("\n", array_map(fn ($v) => "- {$v}", $violations));
$message = "Inline JS check (AGENTS.md: keep JS in separate .js files) found issues in {$filePath}:\n{$list}";

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
function validateNoInlineScript(string $code): array
{
    $violations = [];

    if (preg_match_all('/<script\b(?![^>]*\bsrc=)[^>]*>(.*?)<\/script>/is', $code, $matches)) {
        foreach ($matches[1] as $body) {
            if (trim($body) !== '') {
                $violations[] = 'Inline `<script>...</script>` block with JS code found; move the JS to its own .js file and reference it with a `src` attribute (or `@vite`/`@push(\'scripts\')` asset include) instead.';
                break;
            }
        }
    }

    if (preg_match('/\son\w+\s*=\s*["\'][^"\']*["\']/i', $code)) {
        $violations[] = 'Inline HTML event-handler attribute (e.g. `onclick="..."`) found; move the logic to a separate .js file and bind the listener there.';
    }

    return array_unique($violations);
}
