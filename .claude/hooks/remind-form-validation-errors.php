<?php

/**
 * PostToolUse hook: reminds about the form validation error display rule
 * (.ai/rules/form-validation-errors.md).
 *
 * Flags:
 * - a resources/js/**\/*.js file that appears to render form validation
 *   errors (references `data-error-for` or a validation `errors` object) but
 *   does not go through the `showFieldErrors` helper in
 *   resources/js/shared/field-errors.js.
 * - any resources/js/** or resources/views/** file that reintroduces a
 *   static `data-form-alert` element/reference instead of the shared
 *   snackbar (`showGenericError`/`[data-error-snackbar]`).
 * - a new resources/js/auth/shared.js (or resources/js/shared.js) file (a
 *   single catch-all "shared" file is not allowed; each helper must live in
 *   its own file under resources/js/shared/).
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

$isJs = str_ends_with($normalized, '.js') && str_contains($normalized, '/resources/js/');
$isBlade = str_ends_with($normalized, '.blade.php') && str_contains($normalized, '/resources/views/');

if (! $isJs && ! $isBlade) {
    exit(0);
}

$code = file_get_contents($filePath);
$messages = [];

if (str_contains($code, 'data-form-alert')) {
    $messages[] = "Form validation error rule (.ai/rules/form-validation-errors.md): {$filePath} references "
        .'`data-form-alert`, which was removed. Errors must render via the shared JS snackbar '
        .'(`showGenericError()` / `[data-error-snackbar]`), not a static alert box.';
}

if ($isJs && (str_ends_with($normalized, '/resources/js/auth/shared.js') || str_ends_with($normalized, '/resources/js/shared.js'))) {
    $messages[] = "Form validation error rule (.ai/rules/form-validation-errors.md): {$filePath} recreates "
        .'a single catch-all `shared.js` file, which is not allowed. Split each helper into its own file under '
        .'resources/js/shared/ (e.g. field-errors.js, error-snackbar.js, loading-state.js) instead.';
}

$isSharedHelper = $isJs && str_contains($normalized, '/resources/js/shared/');

if ($isJs && ! $isSharedHelper) {
    $rendersErrorsDirectly = (bool) preg_match('/data-error-for/i', $code)
        || (bool) preg_match('/\berrors\s*\[[\'"]?\w+[\'"]?\]/', $code);

    $usesSharedHelper = (bool) preg_match('/\bshowFieldErrors\s*\(/', $code);

    if ($rendersErrorsDirectly && ! $usesSharedHelper) {
        $messages[] = "Form validation error rule (.ai/rules/form-validation-errors.md): {$filePath} appears to "
            .'render form validation errors directly instead of calling `showFieldErrors()` from '
            .'resources/js/shared/field-errors.js. Use the shared helper so errors show per-input, scroll to '
            .'the first errored input, and (when there are more than 3 errors) show a clickable error snackbar.';
    }
}

if ($messages !== []) {
    $message = implode("\n", $messages);

    echo json_encode([
        'systemMessage' => $message,
        'hookSpecificOutput' => [
            'hookEventName' => 'PostToolUse',
            'additionalContext' => $message,
        ],
    ]);
}

exit(0);
