<?php

/**
 * PostToolUse hook: reminds about the dev-only autofill / generate-data rule
 * (.ai/rules/dev-autofill.md).
 *
 * - Autofill must be pure client-side JS (a fixture function in
 *   resources/js/**), never a REST/API endpoint or PHP trait. Flags a PHP
 *   controller/service using an "Autofill"-named trait, and flags JS code
 *   that fetches a `/dev/.../autofill`-style endpoint.
 * - Generate-data stays server-side but only belongs on list/table pages;
 *   flags its use as a reminder to confirm that, and flags a single
 *   controller centralizing generate-data traits from multiple features.
 */

$stdin = stream_get_contents(STDIN);
$payload = json_decode($stdin, true) ?? [];

$filePath = $payload['tool_input']['file_path']
    ?? $payload['tool_response']['filePath']
    ?? null;

if (! $filePath || ! is_string($filePath) || ! is_file($filePath)) {
    exit(0);
}

$normalized = str_replace('\\', '/', $filePath);

function emit(string $filePath, string $message): void
{
    echo json_encode([
        'systemMessage' => "Dev-autofill rule (.ai/rules/dev-autofill.md): {$filePath} {$message}",
        'hookSpecificOutput' => [
            'hookEventName' => 'PostToolUse',
            'additionalContext' => "Dev-autofill rule (.ai/rules/dev-autofill.md): {$filePath} {$message}",
        ],
    ]);
}

$isJs = str_ends_with($normalized, '.js') && str_contains($normalized, '/resources/js/');
$isPhp = str_ends_with($normalized, '.php')
    && (str_contains($normalized, '/app/Http/Controllers/') || str_contains($normalized, '/app/Services/'));

if ($isJs) {
    $code = file_get_contents($filePath);

    if (preg_match('#[\'"`][^\'"`]*dev/[^\'"`]*autofill[^\'"`]*[\'"`]#i', $code)) {
        emit($filePath, 'appears to fetch a REST endpoint for autofill. Autofill must be pure client-side '
            .'JS — generate the fixture values directly in JS (see resources/js/auth/dev-fixtures/ for the '
            .'pattern) instead of calling a `/dev/.../autofill` API route.');

        exit(0);
    }

    exit(0);
}

if (! $isPhp) {
    exit(0);
}

$code = file_get_contents($filePath);

if (! preg_match('/\bclass\s+\w+/', $code) || preg_match('/\babstract\s+class\s+\w+/', $code)) {
    exit(0);
}

$usesAutofillTrait = (bool) preg_match('/\buse\s+[^;]*Autofill\w*\s*;/i', $code);
$usesGenerateDataTrait = (bool) preg_match('/\buse\s+[^;]*(GenerateData|GeneratesSampleData|GenerateSampleData)\w*\s*;/i', $code);

if ($usesAutofillTrait) {
    emit($filePath, 'uses an autofill trait. Autofill must be implemented client-side in JS '
        .'(no PHP trait, no REST endpoint) — move this logic into its own fixture file under '
        .'resources/js/**/dev-fixtures/ and remove the PHP trait/endpoint.');

    exit(0);
}

// Detect a shared/centralized dev controller: one file pulling in
// generate-data traits from more than one feature namespace.
preg_match_all('/use\s+App\\\\Traits\\\\(\w+)\\\\(?:GenerateData|GeneratesSampleData|GenerateSampleData)\w*\s*;/i', $code, $traitMatches);
$featureNamespaces = array_unique($traitMatches[1] ?? []);

if (count($featureNamespaces) > 1) {
    emit($filePath, 'pulls in generate-data traits from multiple features ('.implode(', ', $featureNamespaces).'). '
        ."Each feature's own controller must consume its own trait directly — do not centralize "
        .'generate-data endpoints for multiple features into one shared/dev controller.');

    exit(0);
}

if ($usesGenerateDataTrait) {
    emit($filePath, 'uses a generate-data trait. Confirm this controller backs a page that displays a '
        .'table/list of data — generate-data is scoped to list/index pages only, not plain form pages. '
        .'If this is a form-only feature, remove the generate-data trait/endpoint (use client-side JS '
        .'autofill instead).');
}

exit(0);
