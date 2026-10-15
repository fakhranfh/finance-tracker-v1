<?php

namespace App\Services;

use App\Enums\RecordImportStep;
use App\Enums\RecordType;
use App\Imports\SpreadsheetReader;
use App\Imports\TooManyRowsException;
use App\Models\RecordImport;
use App\Repositories\Account\AccountRepositoryInterface;
use App\Repositories\Category\CategoryRepositoryInterface;
use App\Repositories\Record\RecordRepositoryInterface;
use App\Repositories\RecordImport\RecordImportRepositoryInterface;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Throwable;

/**
 * Imports records from an uploaded spreadsheet in three steps: upload (preview the columns),
 * map the columns to record fields and list the accounts and categories found in them, then
 * import with those accounts and categories mapped. Each upload is a stored RecordImport that keeps
 * its progress until it is imported or discarded, so the user can leave, come back, and run several at once.
 */
class RecordImportService
{
    /** The account that takes every record whose account is not mapped. */
    public const UNMAPPED_ACCOUNT = 'Unmapped';

    public const MAX_ROWS = 5000;

    private const DIRECTORY = 'record-imports';

    private const SAMPLE_ROWS = 5;

    private const STALE_AFTER_DAYS = 30;

    private const MAX_REPORTED_ERRORS = 50;

    /** Record fields a column can be mapped to, in the order they are matched against header names. */
    private const SYNONYMS = [
        'recorded_at' => ['date', 'tanggal', 'tgl', 'waktu', 'time', 'datetime', 'recorded_at'],
        'amount' => ['amount', 'jumlah', 'nominal', 'total', 'nilai', 'value'],
        'type' => ['type', 'jenis', 'tipe'],
        'to_account' => ['to account', 'to_account', 'ke akun', 'destination', 'tujuan'],
        'account' => ['account', 'akun', 'from account', 'dari akun', 'rekening', 'dompet', 'wallet'],
        'category' => ['category', 'kategori'],
        'note' => ['note', 'notes', 'catatan', 'keterangan', 'description', 'deskripsi', 'memo'],
    ];

    private const TYPE_ALIASES = [
        'income' => ['in', 'masuk'],
        'expense' => ['out', 'keluar'],
        'transfer' => [],
    ];

    private const DATE_FORMATS = [
        'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d',
        'd/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y',
        'd-m-Y H:i:s', 'd-m-Y H:i', 'd-m-Y',
    ];

    public function __construct(
        protected SpreadsheetReader $reader,
        protected RecordManagementService $recordManagementService,
        protected AccountManagementService $accountManagementService,
        protected CategoryManagementService $categoryManagementService,
        protected RecordRepositoryInterface $recordRepository,
        protected AccountRepositoryInterface $accountRepository,
        protected CategoryRepositoryInterface $categoryRepository,
        protected RecordImportRepositoryInterface $recordImportRepository,
    ) {}

    /**
     * A user's unfinished imports, most recently worked on first.
     *
     * @return Collection<int, RecordImport>
     */
    public function list(int $userId): Collection
    {
        return $this->recordImportRepository->forUser($userId);
    }

    /**
     * Find one of the user's unfinished imports.
     */
    public function find(int $userId, int $id): ?RecordImport
    {
        return $this->recordImportRepository->find($userId, $id);
    }

    /**
     * Keep the uploaded file as a new import and describe it: its columns, a few sample rows and
     * the column each record field most likely comes from. Any number of imports can be open at once.
     */
    public function upload(int $userId, UploadedFile $file): RecordImport
    {
        $this->deleteStale();

        $path = Storage::disk('local')->putFileAs(self::DIRECTORY, $file, Str::uuid().'.'.strtolower($file->getClientOriginalExtension()));

        try {
            $sheet = $this->readFile($path, 'file');
        } catch (ValidationException $e) {
            Storage::disk('local')->delete($path);

            throw $e;
        }

        return $this->recordImportRepository->create([
            'user_id' => $userId,
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'row_count' => count($sheet['rows']),
            'step' => RecordImportStep::Columns->value,
            'columns' => collect($sheet['header'])->map(fn (string $name, int $index) => ['index' => $index, 'name' => $name])->all(),
            'sample' => array_slice($sheet['rows'], 0, self::SAMPLE_ROWS),
            'mapping' => $this->suggestMapping($sheet['header']),
        ]);
    }

    /**
     * Remember the progress made: the step, the column mapping and the choices for accounts and categories.
     * The values step only counts once the values have been loaded.
     *
     * @param  array{step?: string, mapping?: array<string, int|null>, choices?: array<string, string>}  $data
     */
    public function save(RecordImport $import, array $data): RecordImport
    {
        if (($data['step'] ?? null) === RecordImportStep::Values->value && $import->values === null) {
            unset($data['step']);
        }

        if (isset($data['choices'])) {
            $data['choices'] = array_filter($data['choices'], 'is_string');
        }

        return $this->recordImportRepository->update($import, Arr::only($data, ['step', 'mapping', 'choices']));
    }

    /**
     * Store the column mapping, find the distinct accounts and categories in the mapped columns (each
     * with the existing one it most likely stands for, plus what exists to choose from) and move to
     * the values step.
     *
     * @param  array<string, int|null>  $mapping
     */
    public function loadValues(RecordImport $import, array $mapping): RecordImport
    {
        $rows = $this->readFile($import->path, 'import')['rows'];
        $accounts = $this->accountRepository->allNamed();
        $categories = $this->categoryRepository->allNamed();
        $accountIds = $accounts->mapWithKeys(fn ($account) => [$this->key($account->name) => $account->id]);
        $categoryIds = $categories->reverse()->mapWithKeys(fn ($category) => [$this->key($category->name) => $category->id]);

        $typeValues = $this->distinct($rows, [$mapping['type'] ?? null]);
        $accountValues = $this->distinct($rows, [$mapping['account'] ?? null, $mapping['to_account'] ?? null]);
        $categoryValues = $this->distinct($rows, [$mapping['category'] ?? null]);

        return $this->recordImportRepository->update($import, [
            'mapping' => $mapping,
            'step' => RecordImportStep::Values->value,
            'choices' => [],
            'values' => [
                'types' => [
                    'found' => $typeValues->values()->all(),
                    'suggestion' => $this->suggestTypeTexts($typeValues->values()->all()),
                ],
                'accounts' => $accountValues->map(fn (string $value, string $key) => [
                    'value' => $value,
                    'suggestion' => $accountIds->has($key) ? ['action' => 'existing', 'id' => $accountIds[$key]] : ['action' => 'create'],
                ])->values()->all(),
                'categories' => $categoryValues->map(fn (string $value, string $key) => [
                    'value' => $value,
                    'suggestion' => $categoryIds->has($key) ? ['action' => 'existing', 'id' => $categoryIds[$key]] : ['action' => 'create'],
                ])->values()->all(),
                'existing_accounts' => $accounts->map->only(['id', 'name'])->values()->all(),
                'existing_categories' => $categories->map(fn ($category) => ['id' => $category->id, 'name' => $category->name, 'type' => $category->type->value])->values()->all(),
            ],
        ]);
    }

    /**
     * Throw the import away together with its file.
     */
    public function discard(RecordImport $import): void
    {
        Storage::disk('local')->delete($import->path);
        $this->recordImportRepository->delete($import);
    }

    /**
     * Create a record for every valid row; invalid rows are skipped and reported. Accounts are
     * mapped to an existing account, created, or sent to the "Unmapped" account, which also takes
     * rows without an account. Categories that are not mapped (or do not fit the record's type)
     * leave the record uncategorized.
     *
     * @param  array<string, string|null>  $types  The values of the type column that mean each record type, separated by commas (e.g. income => "Pemasukan, Masuk"). Values left out are recognized by name.
     * @param  array<int, array{value: string, action: string, id?: int|null, name?: string|null}>  $accounts
     * @param  array<int, array{value: string, action: string, id?: int|null, name?: string|null}>  $categories
     * @return array{imported: int, skipped: int, errors: array<int, array{row: int, message: string}>, created_accounts: array<int, string>, created_categories: array<int, string>}
     */
    public function import(RecordImport $import, array $types, array $accounts, array $categories, string $timezone): array
    {
        $mapping = $import->mapping ?? [];

        if (! isset($mapping['recorded_at'], $mapping['amount'])) {
            throw ValidationException::withMessages(['import' => __('records.import.unmapped_columns')]);
        }

        $rows = $this->readFile($import->path, 'import')['rows'];
        $accountMap = collect($accounts)->mapWithKeys(fn (array $entry) => [$this->key($entry['value']) => $entry]);
        $typeMap = $this->typeMap($types);
        $categoryMap = collect($categories)->mapWithKeys(fn (array $entry) => [$this->key($entry['value']) => $entry]);
        $accountIds = $this->accountRepository->allNamed()->mapWithKeys(fn ($account) => [$this->key($account->name) => $account->id])->all();
        $categoriesById = $this->categoryRepository->allNamed()->keyBy('id');
        $categoryIdsByNameAndType = $categoriesById->mapWithKeys(fn ($category) => [$this->key($category->name).'|'.$category->type->value => $category->id])->all();
        $createdCategories = [];

        $created = [];
        $errors = [];
        $imported = 0;
        $skipped = 0;

        $resolveAccount = function (?string $value) use ($accountMap, &$accountIds, &$created): int {
            $entry = $value === null ? null : $accountMap->get($this->key($value));

            if ($entry !== null && $entry['action'] === 'existing') {
                return (int) $entry['id'];
            }

            $name = $entry !== null && $entry['action'] === 'create'
                ? (trim((string) ($entry['name'] ?? '')) ?: $value)
                : self::UNMAPPED_ACCOUNT;

            if (! isset($accountIds[$this->key($name)])) {
                $accountIds[$this->key($name)] = $this->accountManagementService->create(['name' => $name, 'balance' => 0])->id;
                $created[] = $name;
            }

            return $accountIds[$this->key($name)];
        };

        // An existing category fits when it has the record's type (or a same-named one does); a new one is made per name and type.
        $resolveCategory = function (?string $value, RecordType $type) use ($categoryMap, $categoriesById, &$categoryIdsByNameAndType, &$createdCategories): ?int {
            $entry = $value === null ? null : $categoryMap->get($this->key($value));

            if ($entry === null || $entry['action'] === 'none' || $type === RecordType::Transfer) {
                return null;
            }

            $name = $entry['action'] === 'existing' && $categoriesById->has($entry['id'] ?? 0)
                ? $categoriesById[$entry['id']]->name
                : (trim((string) ($entry['name'] ?? '')) ?: $value);
            $key = $this->key($name).'|'.$type->value;

            if (! isset($categoryIdsByNameAndType[$key])) {
                $categoryIdsByNameAndType[$key] = $this->categoryManagementService->create(['name' => $name, 'type' => $type->value])->id;
                $createdCategories[] = $name;
            }

            return $categoryIdsByNameAndType[$key];
        };

        $this->recordRepository->transaction(function () use ($rows, $mapping, $typeMap, $timezone, $resolveCategory, $resolveAccount, &$errors, &$imported, &$skipped): void {
            foreach ($rows as $rowNumber => $row) {
                $cell = fn (string $field) => isset($mapping[$field]) ? ($row[$mapping[$field]] ?? null) : null;

                $recordedAt = $this->parseDate($cell('recorded_at'), $timezone);
                $amount = $this->parseAmount($cell('amount'));
                $typeText = $this->text($cell('type'));
                $type = isset($mapping['type'])
                    ? ($typeText === null ? null : ($typeMap[$this->key($typeText)] ?? $this->parseType($typeText)))
                    : ($amount === null ? null : ($amount < 0 ? RecordType::Expense : RecordType::Income));
                $accountValue = $this->text($cell('account'));
                $toAccountValue = $this->text($cell('to_account'));

                $message = match (true) {
                    $recordedAt === null => 'invalid_date',
                    $amount === null || abs($amount) < 0.01 || abs($amount) > 9999999999.99 => 'invalid_amount',
                    $type === null => 'invalid_type',
                    $type === RecordType::Transfer && $toAccountValue === null => 'missing_destination',
                    $type === RecordType::Transfer && $this->key($toAccountValue) === $this->key($accountValue ?? '') => 'same_account',
                    default => null,
                };

                if ($message !== null) {
                    $skipped++;
                    $errors[] = ['row' => $rowNumber, 'message' => __("records.import.errors.{$message}")];

                    continue;
                }

                $this->recordManagementService->create([
                    'type' => $type->value,
                    'account_id' => $resolveAccount($accountValue),
                    'to_account_id' => $type === RecordType::Transfer ? $resolveAccount($toAccountValue) : null,
                    'category_id' => $resolveCategory($this->text($cell('category')), $type),
                    'amount' => number_format(abs($amount), 2, '.', ''),
                    'note' => $this->text($cell('note')),
                    'recorded_at' => $recordedAt,
                ]);
                $imported++;
            }
        });

        $this->discard($import);

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => array_slice($errors, 0, self::MAX_REPORTED_ERRORS),
            'created_accounts' => array_values(array_unique($created)),
            'created_categories' => array_values(array_unique($createdCategories)),
        ];
    }

    /**
     * @return array{header: array<int, string>, rows: array<int, array<int, string|int|float|null>>}
     */
    private function readFile(string $path, string $errorField): array
    {
        $disk = Storage::disk('local');

        if (! $disk->exists($path)) {
            throw ValidationException::withMessages([$errorField => __('records.import.expired')]);
        }

        try {
            $sheet = $this->reader->read($disk->path($path), self::MAX_ROWS);
        } catch (TooManyRowsException) {
            throw ValidationException::withMessages([$errorField => __('records.import.too_many_rows', ['max' => self::MAX_ROWS])]);
        } catch (Throwable) {
            throw ValidationException::withMessages([$errorField => __('records.import.unreadable')]);
        }

        if ($sheet['rows'] === []) {
            throw ValidationException::withMessages([$errorField => __('records.import.empty')]);
        }

        return $sheet;
    }

    /**
     * Match header names to record fields: exact names first, then names that contain a known word.
     *
     * @param  array<int, string>  $header
     * @return array<string, int|null>
     */
    private function suggestMapping(array $header): array
    {
        $names = array_map(fn (string $name) => mb_strtolower($name), $header);
        $mapping = array_fill_keys(array_keys(self::SYNONYMS), null);

        foreach ([fn (string $name, string $word) => $name === $word, fn (string $name, string $word) => str_contains($name, $word)] as $matches) {
            foreach (self::SYNONYMS as $field => $words) {
                if ($mapping[$field] !== null) {
                    continue;
                }

                foreach ($names as $index => $name) {
                    if (! in_array($index, $mapping, true) && collect($words)->contains(fn (string $word) => $matches($name, $word))) {
                        $mapping[$field] = $index;

                        break;
                    }
                }
            }
        }

        return $mapping;
    }

    /**
     * Distinct non-empty texts of the given columns, keyed by their comparison key (first spelling wins).
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<int, int|null>  $columns
     * @return Collection<string, string>
     */
    private function distinct(array $rows, array $columns): Collection
    {
        $values = collect();

        foreach ($rows as $row) {
            foreach (array_filter($columns, fn ($column) => $column !== null) as $column) {
                $text = $this->text($row[$column] ?? null);

                if ($text !== null && ! $values->has($this->key($text))) {
                    $values->put($this->key($text), $text);
                }
            }
        }

        return $values;
    }

    private function text(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    /** Names are matched ignoring case and surrounding spaces. */
    private function key(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    /** Imports nobody has touched for a month are dropped with their files. */
    private function deleteStale(): void
    {
        foreach ($this->recordImportRepository->deleteOlderThan(now()->subDays(self::STALE_AFTER_DAYS)) as $path) {
            Storage::disk('local')->delete($path);
        }
    }

    /**
     * @param  array<string, string|null>  $types
     * @return array<string, RecordType> Each typed value, by comparison key.
     */
    private function typeMap(array $types): array
    {
        $map = [];

        foreach (RecordType::cases() as $type) {
            foreach (explode(',', (string) ($types[$type->value] ?? '')) as $part) {
                if ($this->key($part) !== '') {
                    $map[$this->key($part)] = $type;
                }
            }
        }

        return $map;
    }

    /**
     * Prefill for the type inputs: the found values that are recognized by name, per type.
     *
     * @param  array<int, string>  $found
     * @return array<string, string>
     */
    private function suggestTypeTexts(array $found): array
    {
        $texts = array_fill_keys(array_map(fn (RecordType $type) => $type->value, RecordType::cases()), []);

        foreach ($found as $value) {
            $type = $this->parseType($value);

            if ($type !== null) {
                $texts[$type->value][] = $value;
            }
        }

        return array_map(fn (array $values) => implode(', ', $values), $texts);
    }

    private function parseType(mixed $value): ?RecordType
    {
        $needle = $this->key((string) $value);

        foreach (RecordType::cases() as $type) {
            $names = [
                $type->value,
                $this->key(__("records.types.{$type->value}", [], 'en')),
                $this->key(__("records.types.{$type->value}", [], 'id')),
                ...self::TYPE_ALIASES[$type->value],
            ];

            if (in_array($needle, $names, true)) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Read an amount from a number or from text like "Rp 1.250.000,50", "1,250.50" or "(500)". A
     * lone separator followed by exactly three digits is a thousands separator.
     */
    private function parseAmount(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $text = $this->text($value);

        if ($text === null) {
            return null;
        }

        $negative = str_contains($text, '-') || (str_starts_with($text, '(') && str_ends_with($text, ')'));
        $digits = preg_replace('/[^\d.,]/', '', $text);

        if ($digits === '' || ! preg_match('/\d/', $digits)) {
            return null;
        }

        $lastDot = strrpos($digits, '.');
        $lastComma = strrpos($digits, ',');

        if ($lastDot !== false && $lastComma !== false) {
            $decimalAt = max($lastDot, $lastComma);
        } else {
            $separator = $lastDot !== false ? '.' : ($lastComma !== false ? ',' : null);
            $decimalAt = $separator !== null && substr_count($digits, $separator) === 1 && strlen($digits) - strrpos($digits, $separator) - 1 !== 3
                ? strrpos($digits, $separator)
                : null;
        }

        $whole = preg_replace('/[.,]/', '', $decimalAt === null ? $digits : substr($digits, 0, $decimalAt));
        $fraction = $decimalAt === null ? '0' : substr($digits, $decimalAt + 1);

        return (float) (($negative ? '-' : '').($whole === '' ? '0' : $whole).'.'.($fraction === '' ? '0' : $fraction));
    }

    /**
     * Read a date from a spreadsheet serial number or text, as wall-clock time in the user's
     * timezone, and return it in the application timezone.
     */
    private function parseDate(mixed $value, string $timezone): ?CarbonImmutable
    {
        try {
            if (is_int($value) || is_float($value)) {
                if ($value < 1 || $value > 2958465) {
                    return null;
                }

                $value = Date::excelToDateTimeObject($value)->format('Y-m-d H:i:s');
            }

            $text = $this->text($value);

            if ($text === null) {
                return null;
            }

            $zone = new \DateTimeZone($timezone);
            $date = null;

            foreach (self::DATE_FORMATS as $format) {
                $candidate = DateTimeImmutable::createFromFormat('!'.$format, $text, $zone);
                $problems = DateTimeImmutable::getLastErrors();

                if ($candidate !== false && ($problems === false || ($problems['warning_count'] === 0 && $problems['error_count'] === 0))) {
                    $date = $candidate;

                    break;
                }
            }

            $date ??= new DateTimeImmutable($text, $zone);

            return CarbonImmutable::instance($date)->setTimezone(config('app.timezone'));
        } catch (Throwable) {
            return null;
        }
    }
}
