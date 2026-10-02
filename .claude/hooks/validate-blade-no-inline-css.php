<?php

/**
 * PostToolUse hook: enforces AGENTS.md's "always keep CSS in separate .css
 * files, never inline <style> blocks or style=".." attributes inside Blade
 * templates" rule.
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

$violations = validateNoInlineCss($code);

if (empty($violations)) {
    exit(0);
}

$list = implode("\n", array_map(fn ($v) => "- {$v}", $violations));
$message = "Inline CSS check (AGENTS.md: keep CSS in separate .css files) found issues in {$filePath}:\n{$list}";

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
function validateNoInlineCss(string $code): array
{
    $violations = [];

    if (preg_match('/<style\b[^>]*>(.*?)<\/style>/is', $code, $match)) {
        if (trim($match[1]) !== '') {
            $violations[] = 'Inline `<style>...</style>` block found; move the CSS to its own .css file and include it via `@vite`/an asset link instead.';
        }
    }

    if (preg_match('/\sstyle\s*=\s*["\'][^"\']*["\']/i', $code)) {
        $violations[] = 'Inline `style="..."` attribute found; move the styling to a separate .css file (use a class instead).';
    }

    return array_unique($violations);
}
