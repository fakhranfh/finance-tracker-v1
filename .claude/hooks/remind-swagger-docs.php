<?php

/**
 * PostToolUse hook: reminds about the REST API Swagger documentation rule
 * (.ai/rules/api-docs.md) whenever an API controller or routes/api.php is
 * written or edited without matching OpenAPI/Swagger annotations, and
 * whenever routes/api.php is changed at all (to prompt regenerating docs).
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

$isApiController = str_contains($normalized, '/app/Http/Controllers/Api/');
$isApiRoutes = str_ends_with($normalized, '/routes/api.php');
$isSwaggerConfig = str_ends_with($normalized, '/config/l5-swagger.php');

if (! $isApiController && ! $isApiRoutes && ! $isSwaggerConfig) {
    exit(0);
}

if ($isSwaggerConfig) {
    $code = file_get_contents($filePath);
    $gatedToLocal = (bool) preg_match('/environment\(\s*[\'"]local[\'"]\s*\)|app\(\)->isLocal\(\)|APP_ENV.*local/i', $code);

    if (! $gatedToLocal) {
        $message = 'API docs rule (.ai/rules/api-docs.md): config/l5-swagger.php changed but no local/dev '
            ."gating was detected. Swagger UI must only be reachable in local/dev environments — guard the "
            ."UI route (e.g. in routes or a middleware) with `app()->environment('local')`/`app()->isLocal()` "
            .'or equivalent, and never expose it in production.';

        echo json_encode([
            'systemMessage' => $message,
            'hookSpecificOutput' => [
                'hookEventName' => 'PostToolUse',
                'additionalContext' => $message,
            ],
        ]);
    }

    exit(0);
}

if ($isApiRoutes) {
    $message = 'API docs rule (.ai/rules/api-docs.md): routes/api.php changed. '
        .'Make sure any new/changed endpoint has matching Swagger/OpenAPI annotations on its '
        .'controller method, then run `php artisan l5-swagger:generate`. Also confirm the Swagger UI '
        .'route itself stays gated to local/dev only — never exposed in production.';

    echo json_encode([
        'systemMessage' => $message,
        'hookSpecificOutput' => [
            'hookEventName' => 'PostToolUse',
            'additionalContext' => $message,
        ],
    ]);

    exit(0);
}

$code = file_get_contents($filePath);

if (! preg_match('/\bclass\s+\w+/', $code) || preg_match('/\babstract\s+class\s+\w+/', $code)) {
    exit(0);
}

// OpenAPI annotations must live in a dedicated per-feature DocumentsApi
// trait (.ai/rules/api-docs.md), never written directly on the controller.
$hasInlineOpenApiAnnotations = (bool) preg_match('/@OA\\\\|#\[OA\\\\|OpenApi\\\\Annotations/i', $code);

if ($hasInlineOpenApiAnnotations) {
    $message = "API docs rule (.ai/rules/api-docs.md): {$filePath} has OpenAPI/Swagger annotations "
        .'(`@OA\\...`/`#[OA\\...]`) written directly on the controller. Move them into that feature\'s own '
        .'dedicated trait (e.g. `app/Traits/{Feature}/DocumentsApi.php`), one trait per feature, and have '
        .'the controller `use` it instead. Do not centralize multiple features\' annotations into one shared '
        .'docs trait/class.';

    echo json_encode([
        'systemMessage' => $message,
        'hookSpecificOutput' => [
            'hookEventName' => 'PostToolUse',
            'additionalContext' => $message,
        ],
    ]);

    exit(0);
}

// Public action methods (Laravel convention), excluding constructor/magic methods.
preg_match_all('/\bpublic\s+function\s+(?!__)(\w+)\s*\(/', $code, $matches);
$publicMethods = $matches[1] ?? [];

if (empty($publicMethods)) {
    exit(0);
}

$usesDocumentsApiTrait = (bool) preg_match('/use\s+App\\\\Traits\\\\\w+\\\\DocumentsApi\s*;/i', $code)
    || preg_match('/\buse\s+DocumentsApi\s*;/i', $code);

if ($usesDocumentsApiTrait) {
    exit(0);
}

$message = "API docs rule (.ai/rules/api-docs.md): {$filePath} defines API endpoint method(s) ("
    .implode(', ', $publicMethods)
    .') but does not `use` a `DocumentsApi` trait. Add OpenAPI/Swagger annotations for each endpoint in '
    .'that feature\'s own `app/Traits/{Feature}/DocumentsApi.php` trait, have the controller consume it, '
    .'then run `php artisan l5-swagger:generate`.';

echo json_encode([
    'systemMessage' => $message,
    'hookSpecificOutput' => [
        'hookEventName' => 'PostToolUse',
        'additionalContext' => $message,
    ],
]);

exit(0);
