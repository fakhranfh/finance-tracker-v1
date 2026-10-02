<?php

/**
 * PostToolUse hook: validates Blade views against .ai/rules/views.md
 * "Always use skeleton loading" rule — every content view must have a
 * corresponding skeleton loading state defined in a separate component
 * file, not inlined in the view itself.
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

// Skeleton component files themselves are exempt.
if (preg_match('/skeleton/i', basename($filePath))) {
    exit(0);
}

$code = file_get_contents($filePath);

$violations = validateSkeletonUsage($code, $filePath);

if (empty($violations)) {
    exit(0);
}

$list = implode("\n", array_map(fn ($v) => "- {$v}", $violations));
$message = "Skeleton loading check (.ai/rules/views.md) found issues in {$filePath}:\n{$list}";

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
function validateSkeletonUsage(string $code, string $filePath): array
{
    $violations = [];

    // Inline skeleton markup (animate-pulse placeholder blocks) instead of a
    // dedicated skeleton component file.
    if (preg_match('/animate-pulse/', $code) && ! preg_match('/<x-[\w.\-]*skeleton[\w.\-]*\s*\/?>/i', $code)) {
        $violations[] = 'Inline skeleton markup (`animate-pulse`) found; move the skeleton loading state into its own Blade component file and include it with `<x-.../>`, per .ai/rules/views.md.';
    }

    // Views that fetch/display data (wire:loading targets, @if($loading) etc.)
    // but never reference a skeleton component at all.
    $looksAsync = preg_match('/wire:loading|wire:target|Livewire::mount|defer\(\)/i', $code);
    if ($looksAsync && ! preg_match('/<x-[\w.\-]*skeleton[\w.\-]*\s*\/?>/i', $code)) {
        $violations[] = 'View has async/loading behavior but does not include a skeleton component (`<x-*skeleton*/>`); every view needs a corresponding skeleton loading state defined in a separate file, per .ai/rules/views.md.';
    }

    return array_unique($violations);
}
