---
paths:
  - 'database/migrations/*_create_users_table.php'
  - 'database/migrations/**'
---

# Migrations

## Users table timezone column
User migrations must always include a `timezone` column to store each user's timezone preference. This is required for accurate per-user datetime conversion throughout the application.

## Enum columns must have a matching PHP enum
Whenever a migration defines a column using `->enum(...)`, create a corresponding native PHP backed enum in `app/Enums/` (e.g. `app/Enums/StatusEnum.php`), with cases matching the enum values in the migration. Use this enum class when referencing the column's possible values elsewhere (models, form requests, casts, etc.) instead of hardcoding raw strings.
