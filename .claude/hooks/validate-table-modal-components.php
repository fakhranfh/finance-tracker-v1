<?php

/**
 * PostToolUse hook: enforces .ai/rules/views.md's "Tables and modals must be
 * built as reusable components" rule.
 *
 * Reads the Claude Code hook payload (tool_name/tool_input JSON) from STDIN,
 * and when the edited file is a Blade view (other than the shared
 * components/organisms/table.blade.php or modal.blade.php themselves),
 * blocks it if it contains a raw <table> tag or a data-modal="" div, which
 * must instead be built via <x-organisms.table> / <x-organisms.modal>.
 */
$stdin = stream_get_contents(STDIN);
$payload = json_decode($stdin, true) ?? [];

$filePath = $payload['tool_input']['file_path']
    ?? $payload['tool_response']['filePath']
    ?? null;

if (! $filePath || ! is_string($filePath) || ! str_ends_with($filePath, '.blade.php') || ! is_file($filePath)) {
    exit(0);
}

$normalized = str_replace('\\', '/', $filePath);

if (! str_contains($normalized, '/resources/views/')) {
    exit(0);
}

$exempt = [
    'resources/views/components/organisms/table.blade.php',
    'resources/views/components/organisms/modal.blade.php',
];

foreach ($exempt as $path) {
    if (str_ends_with($normalized, $path)) {
        exit(0);
    }
}

$code = file_get_contents($filePath);
$violations = [];

if (preg_match('/<table\b/i', $code)) {
    $violations[] = 'Contains a raw `<table>` tag; use `<x-organisms.table :headers="[...]">...</x-organisms.table>` instead of hand-rolling a table.';
}

if (preg_match('/<div[^>]*\bdata-modal\b/i', $code)) {
    $violations[] = 'Contains a raw `data-modal` div; use `<x-organisms.modal id="..." :title="...">...</x-organisms.modal>` instead of hand-rolling a modal.';
}

if (empty($violations)) {
    exit(0);
}

$list = implode("\n", array_map(fn ($v) => "- {$v}", $violations));
$message = "Table/modal component check (.ai/rules/views.md) found issues in {$filePath}:\n{$list}";

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
