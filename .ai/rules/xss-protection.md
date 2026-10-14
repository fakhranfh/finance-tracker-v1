---
paths:
  - '**'
---

# XSS Protection

## Escape `<script>` in REST API responses
`App\Http\Middleware\EscapeApiScripts` (appended to the `api` middleware group in `bootstrap/app.php`) HTML-escapes any string in a JSON response that contains a `<script` tag and encodes the JSON with `JSON_HEX_TAG`. Keep it registered; never remove it or return raw user content from an API route that bypasses the `api` group.

## Content-Security-Policy header
`App\Http\Middleware\SecurityHeaders` (appended to the `web` group) sends a CSP on every page: nonce-based `script-src`, `object-src 'none'`, `base-uri 'self'`, `frame-ancestors 'none'`. Do not add inline `<script>` blocks to views without `@vite`'s nonce (`Vite::cspNonce()`) and never loosen the policy with `'unsafe-inline'` for scripts or `'unsafe-eval'`. The Vite dev server (any `http:`/`ws:` origin, since CSP cannot match IPv6 hosts like `[::1]`) is allowed only while `public/hot` exists; Swagger UI is exempt only in `local`.

## Short-lived API tokens
Sanctum tokens expire after 60 minutes (`config/sanctum.php`, `SANCTUM_TOKEN_EXPIRATION`, default `60`). Do not raise the default or issue non-expiring tokens.

Covered by `tests/Feature/XssProtectionTest.php`.
