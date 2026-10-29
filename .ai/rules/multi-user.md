---
paths:
  - 'app/Models/**'
  - 'app/Http/Requests/**'
  - 'database/migrations/**'
---

# Multi-user data and roles

## Every user only sees their own data
- A table that holds a user's data directly (accounts, categories, goals, debts, webhooks) has a `user_id` column (FK to `users`, cascade on delete) and its model uses `App\Models\Concerns\BelongsToUser`. That trait scopes every query to the signed-in user and fills `user_id` on create.
- A child table (records, sub goals, goal savings, sub debts, debt payments, webhook logs) has no `user_id`; its model uses `BelongsToOwnerThrough` and names its parent in `ownerParent()`.
- The scopes do nothing without a signed-in user (queue workers, console, the public webhook endpoint). Code that runs there must sign in as the owner first (`Auth::onceUsingId($webhook->user_id)`, `Auth::setUser(...)` + `Auth::forgetUser()` in jobs).
- `exists:` / `unique:` rules and raw `DB::table()` queries bypass the scopes. In form requests use `App\Rules\Owned::exists('table')`, `Owned::record()` and `Owned::unique('table', 'column')`; in raw queries only use ids that came from a scoped query.
- Factories set `user_id` through `UserFactory::ownerId()` (signed-in user, else the oldest user), so a test that makes one actor and then its data keeps working. Create the user before the data.

## Roles (spatie/laravel-permission)
- Roles are `App\Enums\RoleName` (`admin`, `user`). Every new user gets `user`; the first user to register on an empty app becomes `admin`; more admins come from `php artisan admin:create` or Manage Users.
- Admins only manage users (`role:admin` middleware on the users routes). They do not get access to other users' finances.
- Show admin-only sidebar items with the `role` key in `config/sidebar.php`.
