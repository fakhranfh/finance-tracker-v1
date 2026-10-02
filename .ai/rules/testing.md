---
paths:
  - '**'
---

# Testing

## Write tests before implementation (TDD)
Always create unit & feature tests first every time you make or change a feature. Write tests before implementation, then implement code to make tests pass.

## Test database must match production connection
The test suite's `DB_CONNECTION` must match `.env`'s (e.g. `pgsql`), just against a different database name (e.g. `laravel_13_boilerplate_v1_test` vs `laravel_13_boilerplate_v1`). Do not switch tests to `sqlite` for convenience. Configure this in `phpunit.xml`'s `<php>` env block, and enable `RefreshDatabase` in `tests/Pest.php` so migrations run against the test database.

## Always run Larastan and the test suite after changing a feature
Every time you add or change a feature, run `vendor/bin/phpstan analyse` (Larastan) and the Pest tests for that feature (see "Run only the affected tests" below) before considering the work done. Fix any static analysis errors or failing tests before moving on. This is enforced by a `Stop` hook (`.claude/hooks/run-pest-on-change.php`) that runs `vendor/bin/pest` when app/routes/config/lang/database/views/tests changed and blocks finishing on failure.

## Never finish a feature without its Pest test AND its Playwright spec
A feature is not done when the code works in the browser — it is done when it has both a Pest test (`tests/Feature/{Entity}Test.php`, covering every new API endpoint: happy path + validation errors + auth) and a Playwright spec (`tests/playwright/{page}.spec.ts`, per `.ai/rules/e2e-testing.md`) committed in the same change. Do not treat "I clicked through it in the browser" as a substitute for either — that is a one-time manual check, not a regression test. Write both before reporting the feature complete, not as a follow-up.

This is enforced by a Claude Code hook (`.claude/hooks/remind-missing-tests.php`, registered in `.claude/settings.json` as a `PostToolUse` hook on `Write` of `app/Http/Controllers/**`, `app/Services/**`, and `resources/views/**/*.blade.php`), which reminds (non-blocking, since exact filenames can't be guessed with certainty) when a new controller/service or a new page view has no matching file under `tests/Feature/` or `tests/playwright/`.

## Run only the affected tests (Pest and Playwright)
When running Pest or Playwright, run only the tests for the feature you added or changed (e.g. `vendor/bin/pest tests/Feature/UserTest.php` or `--filter=...`, `npx playwright test tests/playwright/users.spec.ts`). Do not run the whole suite. Run the full suite only when the user explicitly asks for it.

The `Stop` hooks (`run-pest-on-change.php`, `run-playwright-on-change.php`) follow this too: they map changed files to tests by name via `affected_tests()` in `.claude/hooks/lib/fingerprint.php`, and, when nothing matches, skip that step without blocking the stop.
