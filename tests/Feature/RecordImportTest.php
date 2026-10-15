<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Record;
use App\Models\User;
use App\Services\RecordImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

const IMPORT_CSV = <<<'CSV'
Date,Type,Account,To Account,Category,Amount,Note
2026-10-01 08:00:00,expense,Cash,,Food,25000,Lunch
2026-10-02 09:00:00,income,BCA,,Gift,1000000,Salary
2026-10-03 10:00:00,transfer,Cash,BCA,,50000,Move
2026-10-04 10:00:00,expense,Wallet,,Food,5000,Snack
not-a-date,expense,Cash,,,100,Bad
CSV;

const IMPORT_MAPPING = ['recorded_at' => 0, 'type' => 1, 'account' => 2, 'to_account' => 3, 'category' => 4, 'amount' => 5, 'note' => 6];

beforeEach(function () {
    Storage::fake('local');
    $this->user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
});

function uploadImport(string $csv, string $name = 'records.csv'): array
{
    $response = test()->actingAs(test()->user)->postJson('/api/records/import/preview', [
        'file' => UploadedFile::fake()->createWithContent($name, $csv),
    ]);

    return $response->assertOk()->json('data');
}

function runImport(string $token, array $accounts = [], array $categories = [], array $mapping = IMPORT_MAPPING)
{
    return test()->actingAs(test()->user)->postJson('/api/records/import', [
        'token' => $token,
        'mapping' => $mapping,
        'accounts' => $accounts,
        'categories' => $categories,
    ]);
}

it('rejects a guest from every import endpoint', function () {
    $this->postJson('/api/records/import/preview')->assertStatus(401);
    $this->postJson('/api/records/import/values')->assertStatus(401);
    $this->postJson('/api/records/import')->assertStatus(401);
});

it('renders the import page', function () {
    $this->actingAs($this->user)->get('/records/import')->assertOk();
});

it('previews an uploaded file with its columns, sample rows and a suggested mapping', function () {
    $preview = uploadImport(IMPORT_CSV);

    expect($preview['row_count'])->toBe(5)
        ->and(collect($preview['columns'])->pluck('name')->all())->toBe(['Date', 'Type', 'Account', 'To Account', 'Category', 'Amount', 'Note'])
        ->and($preview['sample'][0][2])->toBe('Cash')
        ->and($preview['suggested_mapping'])->toMatchArray(IMPORT_MAPPING)
        ->and($preview['token'])->toMatch('/^[0-9a-f-]{36}\.csv$/');
});

it('rejects a file that is not a spreadsheet', function () {
    $this->actingAs($this->user)->postJson('/api/records/import/preview', [
        'file' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
    ])->assertStatus(422)->assertJsonValidationErrors('file');
});

it('rejects a file with only a header row', function () {
    $this->actingAs($this->user)->postJson('/api/records/import/preview', [
        'file' => UploadedFile::fake()->createWithContent('empty.csv', "Date,Amount\n"),
    ])->assertStatus(422)->assertJsonValidationErrors('file');
});

it('lists the distinct accounts and categories of the mapped columns with suggestions', function () {
    $cash = Account::factory()->create(['name' => 'cash']);
    $food = Category::factory()->create(['name' => 'FOOD']);
    $preview = uploadImport(IMPORT_CSV);

    $response = $this->actingAs($this->user)->postJson('/api/records/import/values', [
        'token' => $preview['token'],
        'mapping' => IMPORT_MAPPING,
    ])->assertOk();

    $accounts = collect($response->json('data.accounts'))->keyBy('value');
    expect($accounts->keys()->all())->toBe(['Cash', 'BCA', 'Wallet'])
        ->and($accounts['Cash']['suggestion'])->toBe(['action' => 'existing', 'id' => $cash->id])
        ->and($accounts['BCA']['suggestion'])->toBe(['action' => 'create']);

    $categories = collect($response->json('data.categories'))->keyBy('value');
    expect($categories->keys()->all())->toBe(['Food', 'Gift'])
        ->and($categories['Food']['suggestion'])->toBe(['action' => 'existing', 'id' => $food->id])
        ->and($categories['Gift']['suggestion'])->toBe(['action' => 'create']);
});

it('requires the date and amount columns to be mapped', function () {
    $preview = uploadImport(IMPORT_CSV);

    $this->actingAs($this->user)->postJson('/api/records/import/values', [
        'token' => $preview['token'],
        'mapping' => ['type' => 1],
    ])->assertStatus(422)->assertJsonValidationErrors(['mapping.recorded_at', 'mapping.amount']);
});

it('imports records mapping existing, new and unmapped accounts and categories', function () {
    $cash = Account::factory()->create(['name' => 'Cash', 'last_balance' => 0]);
    $food = Category::factory()->create(['name' => 'Food']);
    $preview = uploadImport(IMPORT_CSV);

    $response = runImport(
        $preview['token'],
        [
            ['value' => 'Cash', 'action' => 'existing', 'id' => $cash->id],
            ['value' => 'BCA', 'action' => 'create'],
            ['value' => 'Wallet', 'action' => 'unmapped'],
        ],
        [
            ['value' => 'Food', 'action' => 'existing', 'id' => $food->id],
            ['value' => 'Gift', 'action' => 'none'],
        ],
    )->assertOk();

    expect($response->json('data.imported'))->toBe(4)
        ->and($response->json('data.skipped'))->toBe(1)
        ->and($response->json('data.errors.0.row'))->toBe(6)
        ->and($response->json('data.created_accounts'))->toContain('BCA', RecordImportService::UNMAPPED_ACCOUNT);

    $bca = Account::where('name', 'BCA')->firstOrFail();
    $unmapped = Account::where('name', RecordImportService::UNMAPPED_ACCOUNT)->firstOrFail();

    $lunch = Record::where('note', 'Lunch')->firstOrFail();
    expect($lunch->account_id)->toBe($cash->id)
        ->and($lunch->category_id)->toBe($food->id)
        ->and($lunch->type->value)->toBe('expense')
        ->and($lunch->amount)->toBe('25000.00')
        ->and($lunch->recorded_at->utc()->format('Y-m-d H:i'))->toBe('2026-10-01 01:00');

    $salary = Record::where('note', 'Salary')->firstOrFail();
    expect($salary->account_id)->toBe($bca->id)->and($salary->category_id)->toBeNull();

    $move = Record::where('note', 'Move')->firstOrFail();
    expect($move->type->value)->toBe('transfer')
        ->and($move->account_id)->toBe($cash->id)
        ->and($move->to_account_id)->toBe($bca->id)
        ->and($move->category_id)->toBeNull();

    $snack = Record::where('note', 'Snack')->firstOrFail();
    expect($snack->account_id)->toBe($unmapped->id);

    expect(Record::count())->toBe(4)
        ->and((float) $bca->refresh()->last_balance)->toBe(1050000.0)
        ->and((float) $cash->refresh()->last_balance)->toBe(-75000.0);
});

it('puts every record in the unmapped account when no account column is mapped, and reuses it', function () {
    $csv = "Date,Amount\n2026-10-01,-1500\n2026-10-02,2500\n";
    $mapping = ['recorded_at' => 0, 'amount' => 1];

    foreach ([1, 2] as $run) {
        $preview = uploadImport($csv);
        runImport($preview['token'], mapping: $mapping)->assertOk()->assertJsonPath('data.imported', 2);
    }

    $unmapped = Account::where('name', RecordImportService::UNMAPPED_ACCOUNT)->get();
    expect($unmapped)->toHaveCount(1)
        ->and(Record::count())->toBe(4)
        ->and(Record::where('type', 'expense')->count())->toBe(2)
        ->and(Record::where('type', 'income')->count())->toBe(2)
        ->and(Record::where('type', 'expense')->first()->amount)->toBe('1500.00');
});

it('creates the categories that do not exist, one per record type', function () {
    $csv = "Date,Type,Amount,Category\n2026-10-01,expense,100,Gift\n2026-10-02,income,200,Gift\n2026-10-03,expense,300,Gift\n2026-10-04,transfer,400,Gift\n";
    $preview = uploadImport($csv);

    $response = runImport(
        $preview['token'],
        categories: [['value' => 'Gift', 'action' => 'create']],
        mapping: ['recorded_at' => 0, 'type' => 1, 'amount' => 2, 'category' => 3],
    )->assertOk();

    $gifts = Category::where('name', 'Gift')->get()->keyBy(fn ($category) => $category->type->value);
    expect($gifts->keys()->sort()->values()->all())->toBe(['expense', 'income'])
        ->and($response->json('data.created_categories'))->toBe(['Gift'])
        ->and(Record::where('type', 'expense')->pluck('category_id')->unique()->all())->toBe([$gifts['expense']->id])
        ->and(Record::where('type', 'income')->value('category_id'))->toBe($gifts['income']->id)
        ->and(Record::where('type', 'transfer')->value('category_id'))->toBeNull();
});

it('leaves a record uncategorized when its category is not mapped', function () {
    $preview = uploadImport("Date,Amount,Category\n2026-10-01,-100,Gift\n");

    runImport($preview['token'], categories: [['value' => 'Gift', 'action' => 'none']], mapping: ['recorded_at' => 0, 'amount' => 1, 'category' => 2])->assertOk();

    expect(Record::firstOrFail()->category_id)->toBeNull()->and(Category::count())->toBe(0);
});

it('swaps a mapped category for the same-named one of the record type, creating it when missing', function () {
    $incomeOnly = Category::factory()->income()->create(['name' => 'Bonus']);
    $csv = "Date,Type,Amount,Category\n2026-10-01,expense,100,Bonus\n2026-10-02,income,200,Bonus\n";
    $preview = uploadImport($csv);

    runImport(
        $preview['token'],
        categories: [['value' => 'Bonus', 'action' => 'existing', 'id' => $incomeOnly->id]],
        mapping: ['recorded_at' => 0, 'type' => 1, 'amount' => 2, 'category' => 3],
    )->assertOk()->assertJsonPath('data.imported', 2);

    $expenseCategory = Record::where('type', 'expense')->first()->category;
    expect($expenseCategory->name)->toBe('Bonus')
        ->and($expenseCategory->type->value)->toBe('expense')
        ->and(Record::where('type', 'income')->first()->category_id)->toBe($incomeOnly->id);
});

it('skips transfers without a distinct destination account', function () {
    $csv = "Date,Type,Account,To,Amount\n2026-10-01,transfer,A,,100\n2026-10-02,transfer,A,A,100\n2026-10-03,transfer,A,B,100\n";
    $preview = uploadImport($csv);

    $response = runImport(
        $preview['token'],
        [['value' => 'A', 'action' => 'create'], ['value' => 'B', 'action' => 'create']],
        mapping: ['recorded_at' => 0, 'type' => 1, 'account' => 2, 'to_account' => 3, 'amount' => 4],
    )->assertOk();

    expect($response->json('data.imported'))->toBe(1)->and($response->json('data.skipped'))->toBe(2);
});

it('reads amounts written the Indonesian way', function () {
    $csv = "Date,Amount\n2026-10-01,\"Rp 1.250.000,50\"\n2026-10-02,\"-2,500\"\n";
    $preview = uploadImport($csv);

    runImport($preview['token'], mapping: ['recorded_at' => 0, 'amount' => 1])->assertOk();

    expect(Record::where('type', 'income')->value('amount'))->toBe('1250000.50')
        ->and(Record::where('type', 'expense')->value('amount'))->toBe('2500.00');
});

it('imports an xlsx file with real date cells', function () {
    $sheet = new Spreadsheet;
    $active = $sheet->getActiveSheet();
    $active->fromArray([['Date', 'Amount', 'Note'], [null, 7500, 'From excel']]);
    $active->setCellValue('A2', 46297.5);
    $active->getStyle('A2')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DATETIME);
    $path = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
    (new Xlsx($sheet))->save($path);

    $preview = $this->actingAs($this->user)->postJson('/api/records/import/preview', [
        'file' => new UploadedFile($path, 'records.xlsx', null, null, true),
    ])->assertOk()->json('data');

    runImport($preview['token'], mapping: ['recorded_at' => 0, 'amount' => 1, 'note' => 2])->assertOk()->assertJsonPath('data.imported', 1);

    $record = Record::firstOrFail();
    expect($record->recorded_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i'))->toBe('2026-10-02 12:00')
        ->and($record->note)->toBe('From excel');

    unlink($path);
});

it('removes the uploaded file once imported', function () {
    $preview = uploadImport(IMPORT_CSV);

    runImport($preview['token'])->assertOk();
    runImport($preview['token'])->assertStatus(422)->assertJsonValidationErrors('token');
});
