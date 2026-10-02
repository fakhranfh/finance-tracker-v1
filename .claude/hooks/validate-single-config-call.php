<?php

// PostToolUse hook: enforces .ai/rules/js.md ("one entry point, one config object").
// - JS page scripts (resources/js/<module>/) must not import the table sub-modules;
//   they call createTable({...}) from shared/table.js only.
// - Blade views must not pass data-label / data-locked attributes to
//   <x-organisms.table>; options go through a single :config array.

$payload = json_decode(stream_get_contents(STDIN), true) ?? [];

$filePath = $payload['tool_input']['file_path']
    ?? $payload['tool_response']['filePath']
    ?? null;

$normalized = $filePath ? str_replace('\\', '/', $filePath) : null;

if (! $normalized || ! is_file($filePath)) {
    exit(0);
}

$content = file_get_contents($filePath);
$problems = [];

if (preg_match('#resources/js/(?!shared/)[^/]+/[^/]+\.js$#', $normalized)) {
    if (preg_match_all('#import\s[^;]*from\s+[\'"]\.\./shared/(table-[\w-]+)\.js[\'"]#', $content, $m)) {
        $problems[] = 'Page scripts must not import table sub-modules ('.implode(', ', array_unique($m[1])).'). '
            .'Call createTable({ ... }) from ../shared/table.js with one config object instead.';
    }
}

if (str_contains($normalized, 'resources/views/') && str_ends_with($normalized, '.blade.php')
    && ! str_ends_with($normalized, 'components/organisms/table.blade.php')) {
    if (preg_match('#<x-organisms\.table\b[^>]*\sdata-(label|locked)-#s', $content)) {
        $problems[] = '<x-organisms.table> must receive its labels/options through a single :config array, '
            .'not data-label-*/data-locked-id attributes.';
    }
}

if (! $problems) {
    exit(0);
}

$message = "Violates .ai/rules/js.md (one entry point, one config object):\n- ".implode("\n- ", $problems);

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
