---
paths:
  - '**'
---

# Manage Feature Generator

## Always scaffold manage features with `php artisan make:manage`
Whenever you add a "manage" feature (a list page with search/sort/pagination/bulk-delete plus create/edit form for an entity, like Manage Users), generate it with the generator instead of hand-writing the files:

```sh
php artisan make:manage Product --fields="name:string,description:text?,price:decimal,stock:integer,is_active:boolean,released_at:date?"
```

- `name` is the singular entity name (`Product`, `ProductCategory`). URLs, route names, lang file, view/JS folders use the kebab-case plural (`product-categories`).
- `--fields` is a comma-separated `name:type` list. Types: `string`, `text`, `integer`, `decimal`, `boolean`, `date`. A trailing `?` makes the field nullable. At least one `string`/`text` field is required (used for search and as the row label). Default: `name:string`.
- `--label-id=Produk` sets the Indonesian entity name and `--labels-id="name:Nama,price:Harga"` the Indonesian field labels. Always pass them so the ID lang files and nav entry are translated (omitted values fall back to English).
- `--force` overwrites existing files.

The command generates the model, migration, factory, repository (+ interface and binding), service, form requests, API resource, API controller (+ `DocumentsApi` and `GeneratesSampleData` traits), page controller, routes (`web.php`, `api.php`, `resources/js/routes.js`), Blade views and skeletons, JS (`index`, `form`, dev-tools, autofill fixture), EN/ID lang files, sidebar/dashboard nav entry, Vite input, a Pest feature test and a Playwright spec.

## After generating
- Run `php artisan migrate`, `vendor/bin/pint --dirty`, `npm run build`, and the Pest + Playwright tests.
- Pass `--label-id`/`--labels-id` when generating; otherwise translate the entity name and field labels in `lang/id/<slug>.php` and the nav entry in `lang/id/dashboard.php` by hand (fallback output is English, e.g. `Product` -> `Produk`, `Name` -> `Nama`, `Kelola Products` -> `Kelola Produk`). Never leave English entity names in the Indonesian lang files.
- Then customize the output (extra validation, relations, custom columns). Do not hand-write what the generator already covers.
- If a feature needs something the generator cannot express (relations, enums, file uploads, non-CRUD actions), still generate the base with `make:manage`, then extend it.

## Keep the generator in sync with the reference
`stubs/manage/*.stub` and `app/Console/Commands/MakeManage.php` are modelled on Manage Users (`resources/views/users/index.blade.php` and its siblings). When a rule or the Manage Users reference implementation changes (table/modal components, JS behaviour, hooks, lang keys), update the stubs in the same change so newly generated features follow the current conventions.

## Removing a manage feature
To delete a manage feature, always use the remover instead of deleting files by hand:

```sh
php artisan remove:manage Product [--drop-table]
```

- **Ask the user first.** Before running it, name the entity and what will be deleted (files, routes, nav, lang, tests, and the table if `--drop-table`) and wait for an explicit yes in chat. Deletion is irreversible; never assume approval from an earlier message.
- The command lists everything and asks to confirm. Pass `--force` to skip its prompt only after the user has said yes in chat.
- `--drop-table` also drops the database table (data is lost) and its migration records. Only add it if the user asked for the data to be removed too; otherwise leave the table.
- Afterwards run `vendor/bin/pint --dirty`, `npm run build` and the Pest + Playwright tests.
- When `make:manage` changes what it generates or registers, update `app/Console/Commands/RemoveManage.php` in the same change.
