<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Scaffolds a complete "manage" feature (list page with search/sort/pagination/
 * bulk-delete, create/edit form, REST API, repository/service, tests) modelled
 * on the Manage Users feature. See .ai/rules/manage-generator.md.
 *
 * Stubs live in stubs/manage/*.stub; when the Manage Users reference changes,
 * update the stubs so newly generated features stay in sync.
 *
 * @phpstan-type Field array{name: string, type: 'string'|'text'|'integer'|'decimal'|'boolean'|'date', nullable: bool, label: string}
 */
#[AsCommand(name: 'make:manage')]
class MakeManage extends Command
{
    protected $signature = 'make:manage
        {name : Singular entity name, e.g. Product or ProductCategory}
        {--fields=name:string : Comma separated name:type[?] list. Types: string, text, integer, decimal, boolean, date. Suffix ? makes a field nullable}
        {--label-id= : Indonesian entity name, e.g. Produk (defaults to the English name)}
        {--labels-id= : Indonesian field labels as name:Label list, e.g. name:Nama,price:Harga (defaults to English labels)}
        {--force : Overwrite existing files}';

    protected $description = 'Generate a full manage feature (page, form, REST API, repository, service, tests) modelled on Manage Users.';

    private const TYPES = ['string', 'text', 'integer', 'decimal', 'boolean', 'date'];

    /** @var array<int, Field> */
    private array $fields = [];

    public function __construct(private readonly Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! $this->parseFields((string) $this->option('fields'))) {
            return self::FAILURE;
        }

        $tokens = $this->buildTokens();

        $written = [];

        foreach ($this->targets($tokens) as $stub => $path) {
            if ($this->files->exists(base_path($path)) && ! $this->option('force')) {
                $this->error("{$path} already exists. Use --force to overwrite.");

                return self::FAILURE;
            }

            $written[] = [$stub, $path];
        }

        foreach ($written as [$stub, $path]) {
            $this->write($path, $this->render($stub, $tokens));
        }

        $this->registerRoutes($tokens);
        $this->registerRepositoryBinding($tokens);
        $this->registerRoutesJs($tokens);
        $this->registerViteInput($tokens);
        $this->registerSidebar($tokens);
        $this->registerDashboardLang($tokens);

        $this->info("Manage feature for {$tokens['__Model__']} generated.");
        $this->line('Next: php artisan migrate, then run "vendor/bin/pint --dirty", "npm run build" and the Pest/Playwright tests.');

        return self::SUCCESS;
    }

    private function parseFields(string $spec): bool
    {
        foreach (array_filter(array_map('trim', explode(',', $spec))) as $part) {
            [$name, $type] = array_pad(explode(':', $part, 2), 2, 'string');

            $nullable = str_ends_with($type, '?');
            $type = rtrim($type, '?');
            $name = Str::snake($name);

            if (! in_array($type, self::TYPES, true)) {
                $this->error("Unknown field type \"{$type}\" for \"{$name}\". Allowed: ".implode(', ', self::TYPES).'.');

                return false;
            }

            if (in_array($name, ['id', 'created_at', 'updated_at'], true)) {
                $this->error("\"{$name}\" is reserved and generated automatically.");

                return false;
            }

            $this->fields[] = [
                'name' => $name,
                'type' => $type,
                'nullable' => $nullable && $type !== 'boolean',
                'label' => Str::headline($name),
            ];
        }

        if ($this->textFields() === []) {
            $this->error('At least one string or text field is required (used for search and as the row label).');

            return false;
        }

        return true;
    }

    /**
     * @return array<int, Field>
     */
    private function textFields(): array
    {
        return array_values(array_filter($this->fields, fn ($f) => in_array($f['type'], ['string', 'text'], true)));
    }

    /**
     * @return array<string, string>
     */
    private function buildTokens(): array
    {
        $model = Str::studly($this->argument('name'));
        $singular = Str::kebab($model);
        $slug = Str::plural($singular);
        $label = str_replace('-', ' ', $singular);
        $main = $this->textFields()[0];

        $tokens = [
            '__Model__' => $model,
            '__modelVar__' => Str::camel($model),
            '__Plural__' => Str::plural($model),
            '__pluralVar__' => Str::camel(Str::plural($model)),
            '__singular__' => $singular,
            '__slug__' => $slug,
            '__table__' => Str::snake(Str::plural($model)),
            '__label__' => $label,
            '__pluralLabel__' => str_replace('-', ' ', $slug),
            '__Label__' => ucfirst($label),
            '__Title__' => Str::headline($slug),
            '__mainField__' => $main['name'],
            '__mainLabel__' => $main['label'],
            '__mainValue__' => $this->phpSample($main, false),
            '__mainUpdatedValue__' => $this->phpSample($main, true),
            '__idLabel__' => $idLabel = Str::lower((string) ($this->option('label-id') ?: $label)),
            '__IdLabel__' => ucfirst($idLabel),
            '__IdTitle__' => Str::headline($this->option('label-id') ?: $slug),
        ];

        return $tokens + $this->buildFragments($tokens);
    }

    /**
     * @param  array<string, string>  $tokens
     * @return array<string, string>
     */
    private function buildFragments(array $tokens): array
    {
        $slug = $tokens['__slug__'];
        $names = array_column($this->fields, 'name');
        $quoted = fn (array $items): string => implode(', ', array_map(fn ($i) => "'{$i}'", $items));
        $lines = fn (callable $fn, string $indent): string => implode("\n", array_map(fn ($f) => $indent.$fn($f), $this->fields));

        $idLabels = [];

        foreach (array_filter(array_map('trim', explode(',', (string) $this->option('labels-id')))) as $pair) {
            [$key, $value] = array_pad(explode(':', $pair, 2), 2, '');
            $idLabels[Str::snake(trim($key))] = trim($value);
        }

        $searchable = array_column($this->textFields(), 'name');
        $searchClause = [];

        foreach ($searchable as $i => $column) {
            $searchClause[] = $i === 0
                ? "            \$inner->where('{$column}', 'like', \"%{\$search}%\")"
                : "                ->orWhere('{$column}', 'like', \"%{\$search}%\")";
        }

        $casts = array_filter(array_map(fn ($f) => match ($f['type']) {
            'boolean' => "            '{$f['name']}' => 'boolean',",
            'decimal' => "            '{$f['name']}' => 'decimal:2',",
            'integer' => "            '{$f['name']}' => 'integer',",
            default => null,
        }, $this->fields));

        $castsMethod = $casts === [] ? '' : "\n    /**\n     * Get the attributes that should be cast.\n     *\n     * @return array<string, string>\n     */\n    protected function casts(): array\n    {\n        return [\n".implode("\n", $casts)."\n        ];\n    }\n";

        $headers = $lines(fn ($f) => "['label' => __('{$slug}.table.{$f['name']}'), 'sort' => '{$f['name']}'],", '        ')
            ."\n        ['label' => __('{$slug}.table.created_at'), 'sort' => 'created_at'],";

        $rowCells = [];

        foreach ($this->fields as $f) {
            $rowCells[] = "            {$this->jsDisplay($f)},";
        }

        $rowCells[] = '            formatDateTime(item.created_at),';

        return [
            '__fillable__' => $quoted($names),
            '__castsMethod__' => $castsMethod,
            '__migrationColumns__' => $lines(fn ($f) => $this->migrationColumn($f), '            '),
            '__factoryFields__' => $lines(fn ($f) => "'{$f['name']}' => {$this->factoryValue($f)},", '            '),
            '__sortable__' => $quoted([...$names, 'created_at']),
            '__searchClause__' => implode("\n", $searchClause).';',
            '__searchFields__' => implode(' or ', array_map(fn ($f) => strtolower($f['label']), $this->textFields())),
            '__rules__' => $lines(fn ($f) => "'{$f['name']}' => [{$this->rules($f)}],", '            '),
            '__resourceFields__' => $lines(fn ($f) => "'{$f['name']}' => \${$tokens['__modelVar__']}->{$f['name']},", '            '),
            '__openApiProps__' => $lines(fn ($f) => "new OA\\Property(property: '{$f['name']}', {$this->openApiType($f)}),", '                    '),
            '__tableHeaders__' => $headers,
            '__colspan__' => (string) (count($this->fields) + 3),
            '__formSkeletonRows__' => implode("\n", array_map(fn () => '    <x-atoms.skeleton class="h-10 w-full" />', $this->fields)),
            '__rowCells__' => implode("\n", $rowCells),
            '__formFields__' => implode("\n", array_map(fn ($f, $i) => $this->formField($f, $slug, $i === 0), $this->fields, array_keys($this->fields))),
            '__formLoad__' => $lines(fn ($f) => $f['type'] === 'boolean'
                ? "form.elements.namedItem('{$f['name']}').checked = Boolean(data.data.{$f['name']});"
                : "form.elements.namedItem('{$f['name']}').value = data.data.{$f['name']} ?? '';", '        '),
            '__formPayload__' => $lines(fn ($f) => $f['type'] === 'boolean'
                ? "{$f['name']}: form.elements.namedItem('{$f['name']}').checked,"
                : "{$f['name']}: form.elements.namedItem('{$f['name']}').value,", '            '),
            '__fixture__' => $lines(fn ($f) => "{$f['name']}: {$this->jsFixture($f)},", '        '),
            '__langTable__' => $lines(fn ($f) => "'{$f['name']}' => '".addslashes($f['label'])."',", '        '),
            '__langTableId__' => $lines(fn ($f) => "'{$f['name']}' => '".addslashes($idLabels[$f['name']] ?? $f['label'])."',", '        '),
            '__langFieldsId__' => $lines(fn ($f) => "'{$f['name']}' => '".addslashes($idLabels[$f['name']] ?? $f['label'])."',", '        '),
            '__searchFieldsId__' => implode(' atau ', array_map(fn ($f) => strtolower($idLabels[$f['name']] ?? $f['label']), $this->textFields())),
            '__langFields__' => $lines(fn ($f) => "'{$f['name']}' => '".addslashes($f['label'])."',", '        '),
            '__fieldKeys__' => $quoted($names),
            '__requiredFields__' => $quoted(array_column(array_filter($this->fields, fn ($f) => ! $f['nullable']), 'name')),
            '__testPayload__' => $lines(fn ($f) => "'{$f['name']}' => {$this->phpSample($f, false)},", '        '),
            '__testPayloadUpdated__' => $lines(fn ($f) => "'{$f['name']}' => {$this->phpSample($f, true)},", '        '),
            '__e2eFill__' => implode("\n", array_map(fn ($f) => $this->e2eFill($f, $f['name'] === $tokens['__mainField__']), $this->fields)),
        ];
    }

    /**
     * @param  Field  $f
     */
    private function migrationColumn(array $f): string
    {
        $column = match ($f['type']) {
            'string' => "\$table->string('{$f['name']}')",
            'text' => "\$table->text('{$f['name']}')",
            'integer' => "\$table->integer('{$f['name']}')",
            'decimal' => "\$table->decimal('{$f['name']}', 12, 2)",
            'boolean' => "\$table->boolean('{$f['name']}')->default(false)",
            'date' => "\$table->date('{$f['name']}')",
        };

        return $column.($f['nullable'] ? '->nullable()' : '').';';
    }

    /**
     * @param  Field  $f
     */
    private function factoryValue(array $f): string
    {
        return match ($f['type']) {
            'string' => 'fake()->words(3, true)',
            'text' => 'fake()->paragraph()',
            'integer' => 'fake()->numberBetween(1, 1000)',
            'decimal' => 'fake()->randomFloat(2, 1, 1000)',
            'boolean' => 'fake()->boolean()',
            'date' => "fake()->date('Y-m-d')",
        };
    }

    /**
     * @param  Field  $f
     */
    private function rules(array $f): string
    {
        $rules = [$f['nullable'] ? 'nullable' : 'required', ...match ($f['type']) {
            'string' => ['string', 'max:255'],
            'text' => ['string'],
            'integer' => ['integer'],
            'decimal' => ['numeric', 'min:0'],
            'boolean' => ['boolean'],
            'date' => ['date'],
        }];

        return implode(', ', array_map(fn ($r) => "'{$r}'", $rules));
    }

    /**
     * @param  Field  $f
     */
    private function openApiType(array $f): string
    {
        $example = $this->phpSample($f, false);

        return match ($f['type']) {
            'integer' => "type: 'integer', example: {$example}",
            'decimal' => "type: 'number', format: 'float', example: {$example}",
            'boolean' => "type: 'boolean', example: {$example}",
            'date' => "type: 'string', format: 'date', example: {$example}",
            default => "type: 'string', example: {$example}",
        };
    }

    /**
     * PHP literal used as a sample value in generated tests.
     *
     * @param  Field  $f
     */
    private function phpSample(array $f, bool $updated): string
    {
        return match ($f['type']) {
            'string', 'text' => "'".($updated ? 'Updated' : 'Sample').' '.str_replace('_', ' ', $f['name'])."'",
            'integer' => $updated ? '8' : '5',
            'decimal' => $updated ? '19.5' : '9.99',
            'boolean' => $updated ? 'false' : 'true',
            'date' => $updated ? "'2026-02-20'" : "'2026-01-15'",
        };
    }

    /**
     * @param  Field  $f
     */
    private function jsDisplay(array $f): string
    {
        return match ($f['type']) {
            'boolean' => "item.{$f['name']} ? '✓' : '—'",
            'date' => "item.{$f['name']} ? formatDateTime(item.{$f['name']}) : ''",
            default => "item.{$f['name']} ?? ''",
        };
    }

    /**
     * @param  Field  $f
     */
    private function jsFixture(array $f): string
    {
        return match ($f['type']) {
            'string' => 'randomWords(2)',
            'text' => 'randomWords(8)',
            'integer' => 'randomInt(1, 100)',
            'decimal' => '(randomInt(100, 100000) / 100).toFixed(2)',
            'boolean' => 'Math.random() > 0.5',
            'date' => 'new Date().toISOString().slice(0, 10)',
        };
    }

    /**
     * @param  Field  $f
     */
    private function formField(array $f, string $slug, bool $first): string
    {
        $indent = '            ';
        $label = "__('{$slug}.fields.{$f['name']}')";

        if ($f['type'] === 'boolean') {
            return "{$indent}<div class=\"flex items-center gap-2\">\n"
                ."{$indent}    <x-atoms.checkbox name=\"{$f['name']}\" id=\"{$f['name']}\" />\n"
                ."{$indent}    <x-atoms.label for=\"{$f['name']}\">{{ {$label} }}</x-atoms.label>\n"
                ."{$indent}</div>";
        }

        $type = match ($f['type']) {
            'integer', 'decimal' => 'number',
            'date' => 'date',
            default => 'text',
        };

        $extra = $f['type'] === 'decimal' ? ' step="0.01" min="0"' : '';
        $extra .= $f['nullable'] ? '' : ' required';
        $extra .= $first ? ' autofocus' : '';

        return "{$indent}<x-molecules.form-group name=\"{$f['name']}\" type=\"{$type}\" :label=\"{$label}\"{$extra} />";
    }

    /**
     * @param  Field  $f
     */
    private function e2eFill(array $f, bool $isMain): string
    {
        $label = addslashes($f['label']);

        if ($f['type'] === 'boolean') {
            return "        await page.getByLabel('{$label}', { exact: true }).check();";
        }

        $value = match (true) {
            $isMain => '${mainValue}',
            $f['type'] === 'integer' => '10',
            $f['type'] === 'decimal' => '12.50',
            $f['type'] === 'date' => '2026-01-15',
            default => "E2E {$label}",
        };

        return "        await page.getByLabel('{$label}', { exact: true }).fill(".($isMain ? '`'.$value.'`' : "'{$value}'").');';
    }

    /**
     * @param  array<string, string>  $tokens
     * @return array<string, string> stub => target path
     */
    private function targets(array $tokens): array
    {
        $m = $tokens['__Model__'];
        $slug = $tokens['__slug__'];
        $singular = $tokens['__singular__'];

        return [
            'model' => "app/Models/{$m}.php",
            'migration' => 'database/migrations/'.date('Y_m_d_His')."_create_{$tokens['__table__']}_table.php",
            'factory' => "database/factories/{$m}Factory.php",
            'repository-interface' => "app/Repositories/{$m}/{$m}RepositoryInterface.php",
            'repository' => "app/Repositories/{$m}/{$m}Repository.php",
            'service' => "app/Services/{$m}ManagementService.php",
            'request:Store' => "app/Http/Requests/{$m}/Store{$m}Request.php",
            'request:Update' => "app/Http/Requests/{$m}/Update{$m}Request.php",
            'request-bulk-delete' => "app/Http/Requests/{$m}/BulkDelete{$m}Request.php",
            'resource' => "app/Http/Resources/{$m}Resource.php",
            'api-controller' => "app/Http/Controllers/Api/{$m}Controller.php",
            'page-controller' => "app/Http/Controllers/{$m}PageController.php",
            'sample-data-trait' => "app/Traits/{$m}/GeneratesSampleData.php",
            'documents-api-trait' => "app/Traits/{$m}/DocumentsApi.php",
            'view-index' => "resources/views/{$slug}/index.blade.php",
            'view-form' => "resources/views/{$slug}/form.blade.php",
            'form-skeleton' => "resources/views/components/organisms/{$singular}-form-skeleton.blade.php",
            'js-index' => "resources/js/{$slug}/index.js",
            'js-form' => "resources/js/{$slug}/form.js",
            'js-dev-tools' => "resources/js/{$slug}/dev-tools.js",
            'js-fixture' => "resources/js/{$slug}/dev-fixtures/form.js",
            'lang-en' => "lang/en/{$slug}.php",
            'lang-id' => "lang/id/{$slug}.php",
            'test-feature' => "tests/Feature/{$m}ManagementTest.php",
            'test-playwright' => "tests/playwright/{$slug}.spec.ts",
        ];
    }

    /**
     * @param  array<string, string>  $tokens
     */
    private function render(string $stub, array $tokens): string
    {
        [$name, $action] = array_pad(explode(':', $stub, 2), 2, null);

        $contents = $this->files->get(base_path("stubs/manage/{$name}.stub"));

        if ($action !== null) {
            $tokens['__Action__'] = $action;
        }

        return str_replace(array_keys($tokens), array_values($tokens), $contents);
    }

    private function write(string $path, string $contents): void
    {
        $full = base_path($path);

        $this->files->ensureDirectoryExists(dirname($full));
        $this->files->put($full, $contents);

        $this->line("  <info>created</info> {$path}");
    }

    /**
     * Insert $insert before the first occurrence of $anchor in $path (skipped when already present).
     */
    private function insertBefore(string $path, string $anchor, string $insert, ?string $guard = null): void
    {
        $full = base_path($path);
        $contents = $this->files->get($full);

        if (str_contains($contents, $guard ?? $insert)) {
            return;
        }

        $position = strpos($contents, $anchor);

        if ($position === false) {
            $this->warn("Could not update {$path}; add this manually:\n{$insert}");

            return;
        }

        $this->files->put($full, substr_replace($contents, $insert, $position, 0));
        $this->line("  <info>updated</info> {$path}");
    }

    /**
     * @param  array<string, string>  $tokens
     */
    private function registerRoutes(array $tokens): void
    {
        $m = $tokens['__Model__'];
        $slug = $tokens['__slug__'];

        $this->insertBefore('routes/web.php', 'use Illuminate\Support\Facades\Route;', "use App\\Http\\Controllers\\{$m}PageController;\n");

        $webRoutes = "\n    Route::get('/{$slug}', [{$m}PageController::class, 'index'])->name('{$slug}.index');\n"
            ."    Route::get('/{$slug}/create', [{$m}PageController::class, 'create'])->name('{$slug}.create');\n"
            ."    Route::get('/{$slug}/{id}/edit', [{$m}PageController::class, 'edit'])->name('{$slug}.edit');\n";

        $web = $this->files->get(base_path('routes/web.php'));

        if (! str_contains($web, "'{$slug}.index'")) {
            $position = strrpos($web, '});');
            $this->files->put(base_path('routes/web.php'), substr_replace($web, $webRoutes, $position, 0));
            $this->line('  <info>updated</info> routes/web.php');
        }

        $this->insertBefore('routes/api.php', 'use Illuminate\Support\Facades\Route;', "use App\\Http\\Controllers\\Api\\{$m}Controller;\n");

        $this->insertBefore('routes/api.php', "        Route::post('/logout'", "        Route::delete('/{$slug}', [{$m}Controller::class, 'bulkDestroy'])->name('{$slug}.bulk-destroy');\n"
            ."        Route::post('/{$slug}/generate', [{$m}Controller::class, 'generate'])\n"
            ."            ->middleware(EnsureLocalEnvironment::class)\n"
            ."            ->name('{$slug}.generate');\n"
            ."        Route::resource('{$slug}', {$m}Controller::class)\n"
            ."            ->parameters(['{$slug}' => '{$tokens['__modelVar__']}'])\n"
            ."            ->except('create', 'edit')\n"
            ."            ->names('{$slug}');\n", "Route::resource('{$slug}'");
    }

    /**
     * @param  array<string, string>  $tokens
     */
    private function registerRepositoryBinding(array $tokens): void
    {
        $m = $tokens['__Model__'];
        $path = 'app/Providers/AppServiceProvider.php';

        $this->insertBefore($path, 'use Illuminate\Support\ServiceProvider;', "use App\\Repositories\\{$m}\\{$m}Repository;\nuse App\\Repositories\\{$m}\\{$m}RepositoryInterface;\n");

        $contents = $this->files->get(base_path($path));

        if (str_contains($contents, "{$m}RepositoryInterface::class,")) {
            return;
        }

        $anchor = "public function register(): void\n    {\n";
        $position = strpos($contents, $anchor);

        if ($position === false) {
            $this->warn("Could not bind {$m}RepositoryInterface in {$path}; add it manually.");

            return;
        }

        $offset = $position + strlen($anchor);
        $this->files->put(base_path($path), substr_replace($contents, "        \$this->app->bind({$m}RepositoryInterface::class, {$m}Repository::class);\n", $offset, 0));
        $this->line("  <info>updated</info> {$path}");
    }

    /**
     * @param  array<string, string>  $tokens
     */
    private function registerRoutesJs(array $tokens): void
    {
        $slug = $tokens['__slug__'];
        $key = $tokens['__pluralVar__'];
        $var = $tokens['__modelVar__'];
        $path = 'resources/js/routes.js';

        $this->insertBefore($path, "    pages: {\n", "    {$key}: {\n"
            ."        index: endpoint('/{$slug}'),\n"
            ."        show: (id) => endpoint(`/{$slug}/\${id}`),\n"
            ."        store: endpoint('/{$slug}'),\n"
            ."        update: (id) => endpoint(`/{$slug}/\${id}`),\n"
            ."        destroy: (id) => endpoint(`/{$slug}/\${id}`),\n"
            ."        bulkDestroy: endpoint('/{$slug}'),\n"
            ."        generate: endpoint('/{$slug}/generate'),\n"
            ."    },\n", "    {$key}: {\n");

        $contents = $this->files->get(base_path($path));

        if (str_contains($contents, "        {$var}Create:")) {
            return;
        }

        $pagesStart = strpos($contents, "    pages: {\n");
        $close = strpos($contents, "\n    },", $pagesStart);

        if ($pagesStart === false || $close === false) {
            $this->warn("Could not add page paths to {$path}; add them manually.");

            return;
        }

        $pages = "\n        {$key}: '/{$slug}',\n        {$var}Create: '/{$slug}/create',\n        {$var}Edit: (id) => `/{$slug}/\${id}/edit`,";

        $this->files->put(base_path($path), substr_replace($contents, $pages, $close, 0));
    }

    /**
     * @param  array<string, string>  $tokens
     */
    private function registerViteInput(array $tokens): void
    {
        $slug = $tokens['__slug__'];

        $this->insertBefore('vite.config.js', "            ],\n            refresh", "                'resources/js/{$slug}/index.js',\n                'resources/js/{$slug}/form.js',\n", "resources/js/{$slug}/index.js");
    }

    /**
     * @param  array<string, string>  $tokens
     */
    private function registerSidebar(array $tokens): void
    {
        $slug = $tokens['__slug__'];
        $path = 'config/sidebar.php';

        $this->insertBefore($path, "    ],\n\n    'icons' => [", "        ['label' => 'dashboard.nav.{$slug}', 'route' => '{$slug}.index', 'active' => '{$slug}.*', 'icon' => 'cube'],\n", "'{$slug}.index'");

        $cube = "        'cube' => '<path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9\" />',\n";
        $contents = $this->files->get(base_path($path));

        if (! str_contains($contents, "'cube' =>")) {
            $position = strrpos($contents, "    ],\n];");
            $this->files->put(base_path($path), substr_replace($contents, $cube, $position, 0));
        }
    }

    /**
     * @param  array<string, string>  $tokens
     */
    private function registerDashboardLang(array $tokens): void
    {
        $slug = $tokens['__slug__'];

        foreach (['en' => $tokens['__Title__'], 'id' => 'Kelola '.$tokens['__IdTitle__']] as $locale => $text) {
            $this->insertBefore("lang/{$locale}/dashboard.php", "    ],\n    'topbar'", "        '{$slug}' => '".($locale === 'en' ? 'Manage '.$text : $text)."',\n", "'{$slug}' =>");
        }
    }
}
