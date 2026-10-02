---
paths:
  - 'resources/js/**'
---

# HTTP Client

## Use the shared HTTP client for API calls
Never hand-roll `fetch()` calls to the REST API. Use the helpers in `resources/js/shared/http.js` (`get`, `post`, `put`, `patch`, `del`), which set the CSRF header, JSON `Accept`/`Content-Type` headers, `credentials: 'same-origin'`, and safely parse the JSON response body. Each helper returns `{ ok, status, data }` so callers can branch on validation vs success. Add a new method to `resources/js/shared/http.js` instead of building requests ad hoc at the call site.

## Never hardcode page paths
Never assign a literal path string to `window.location.href` (or use one in an `<a href>` built from JS). Add the path to the `pages` object in `resources/js/routes.js` and reference it as `routes.pages.<name>` instead. Same rule as API endpoints: every URL frontend JS navigates to lives in `routes.js`, not inline at the call site.
