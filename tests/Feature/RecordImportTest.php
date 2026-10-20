<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Record;
use App\Models\RecordImport;
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

/** Upload a file as a new import and return the created import. */
function uploadImport(string $csv, string $name = 'records.csv', ?User $user = null): array
{
    return test()->actingAs($user ?? test()->user)->postJson('/api/records/imports', [
        'file' => UploadedFile::fake()->createWithContent($name, $csv),
    ])->assertCreated()->json('data');
}

function loadImportValues(int $id, array $mapping = IMPORT_MAPPING)
{
    return test()->actingAs(test()->user)->postJson("/api/records/imports/{$id}/values", ['mapping' => $mapping]);
}

/** Run an import: store the mapping (as the values step does), then import with the given choices. */
function runImport(array $import, array $accounts = [], array $categories = [], array $mapping = IMPORT_MAPPING, array $types = [])
{
    loadImportValues($import['id'], $mapping)->assertOk();

    return test()->actingAs(test()->user)->postJson("/api/records/imports/{$import['id']}/run", [
        'types' => $types,
        'accounts' => $accounts,
        'categories' => $categories,
    ]);
}

it('rejects a guest from every import endpoint', function () {
    $this->getJson('/api/records/imports')->assertStatus(401);
    $this->postJson('/api/records/imports')->assertStatus(401);
    $this->getJson('/api/records/imports/1')->assertStatus(401);
    $this->putJson('/api/records/imports/1')->assertStatus(401);
    $this->postJson('/api/records/imports/1/values')->assertStatus(401);
    $this->postJson('/api/records/imports/1/run')->assertStatus(401);
    $this->deleteJson('/api/records/imports/1')->assertStatus(401);
});

it('renders the import page', function () {
    $this->actingAs($this->user)->get('/records/import')->assertOk();
});

it('stores an upload with its columns, sample rows and a suggested mapping', function () {
    $import = uploadImport(IMPORT_CSV);

    expect($import['row_count'])->toBe(5)
        ->and($import['step'])->toBe('columns')
        ->and($import['filename'])->toBe('records.csv')
        ->and(collect($import['columns'])->pluck('name')->all())->toBe(['Date', 'Type', 'Account', 'To Account', 'Category', 'Amount', 'Note'])
        ->and($import['sample'][0][2])->toBe('Cash')
        ->and($import['mapping'])->toMatchArray(IMPORT_MAPPING);

    expect(RecordImport::count())->toBe(1);
    Storage::disk('local')->assertExists(RecordImport::first()->path);
});

it('rejects a file that is not a spreadsheet', function () {
    $this->actingAs($this->user)->postJson('/api/records/imports', [
        'file' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
    ])->assertStatus(422)->assertJsonValidationErrors('file');
});

it('rejects a file with only a header row', function () {
    $this->actingAs($this->user)->postJson('/api/records/imports', [
        'file' => UploadedFile::fake()->createWithContent('empty.csv', "Date,Amount\n"),
    ])->assertStatus(422)->assertJsonValidationErrors('file');

    expect(RecordImport::count())->toBe(0);
});

it('keeps several unfinished imports open at once, newest work first', function () {
    $first = uploadImport(IMPORT_CSV, 'first.csv');
    $second = uploadImport("Date,Amount\n2026-10-01,100\n", 'second.csv');
    RecordImport::whereKey($first['id'])->update(['updated_at' => now()->addMinute()]);

    $list = $this->actingAs($this->user)->getJson('/api/records/imports')->assertOk()->json('data');

    expect(collect($list)->pluck('id')->all())->toBe([$first['id'], $second['id']])
        ->and($list[0])->toMatchArray(['filename' => 'first.csv', 'step' => 'columns', 'row_count' => 5])
        ->and($list[0])->not->toHaveKey('columns');
});

it('keeps every user\'s imports private', function () {
    $mine = uploadImport(IMPORT_CSV);
    $other = User::factory()->create();

    $this->actingAs($other)->getJson('/api/records/imports')->assertOk()->assertJsonCount(0, 'data');
    $this->actingAs($other)->getJson("/api/records/imports/{$mine['id']}")->assertNotFound();
    $this->actingAs($other)->putJson("/api/records/imports/{$mine['id']}", ['step' => 'columns'])->assertNotFound();
    $this->actingAs($other)->postJson("/api/records/imports/{$mine['id']}/values", ['mapping' => IMPORT_MAPPING])->assertNotFound();
    $this->actingAs($other)->postJson("/api/records/imports/{$mine['id']}/run", ['types' => [], 'accounts' => [], 'categories' => []])->assertNotFound();
    $this->actingAs($other)->deleteJson("/api/records/imports/{$mine['id']}")->assertNotFound();

    expect(RecordImport::count())->toBe(1);
});

it('saves progress and gives it back when the import is opened again', function () {
    $import = uploadImport(IMPORT_CSV);

    $this->actingAs($this->user)->putJson("/api/records/imports/{$import['id']}", [
        'mapping' => [...IMPORT_MAPPING, 'note' => null],
        'choices' => ['account|Cash' => 'unmapped'],
    ])->assertOk();

    $shown = $this->actingAs($this->user)->getJson("/api/records/imports/{$import['id']}")->assertOk()->json('data');

    expect($shown['mapping']['note'])->toBeNull()
        ->and($shown['mapping']['amount'])->toBe(5)
        ->and($shown['choices'])->toBe(['account|Cash' => 'unmapped'])
        ->and($shown['step'])->toBe('columns');
});

it('only moves to the values step once the values are loaded', function () {
    $import = uploadImport(IMPORT_CSV);

    $this->actingAs($this->user)->putJson("/api/records/imports/{$import['id']}", ['step' => 'values'])
        ->assertOk()->assertJsonPath('data.step', 'columns');

    loadImportValues($import['id'])->assertOk()->assertJsonPath('data.step', 'values');

    $this->actingAs($this->user)->putJson("/api/records/imports/{$import['id']}", ['step' => 'columns'])
        ->assertOk()->assertJsonPath('data.step', 'columns');
    $this->actingAs($this->user)->putJson("/api/records/imports/{$import['id']}", ['step' => 'values'])
        ->assertOk()->assertJsonPath('data.step', 'values');
});

it('rejects an unknown step', function () {
    $import = uploadImport(IMPORT_CSV);

    $this->actingAs($this->user)->putJson("/api/records/imports/{$import['id']}", ['step' => 'result'])
        ->assertStatus(422)->assertJsonValidationErrors('step');
});

it('lists the distinct accounts and categories of the mapped columns with suggestions', function () {
    $cash = Account::factory()->create(['name' => 'cash']);
    $food = Category::factory()->create(['name' => 'FOOD']);
    $import = uploadImport(IMPORT_CSV);

    $response = loadImportValues($import['id'])->assertOk();

    $accounts = collect($response->json('data.values.accounts'))->keyBy('value');
    expect($accounts->keys()->all())->toBe(['Cash', 'BCA', 'Wallet'])
        ->and($accounts['Cash']['suggestion'])->toBe(['action' => 'existing', 'id' => $cash->id])
        ->and($accounts['BCA']['suggestion'])->toBe(['action' => 'create']);

    $categories = collect($response->json('data.values.categories'))->keyBy('value');
    expect($categories->keys()->all())->toBe(['Food', 'Gift'])
        ->and($categories['Food']['suggestion'])->toBe(['action' => 'existing', 'id' => $food->id])
        ->and($categories['Gift']['suggestion'])->toBe(['action' => 'create']);
});

it('lists the values of the type column and prefills the ones recognized by name', function () {
    $csv = 'Date,Type,Amount
2026-10-01,Pemasukan,100
2026-10-02,Keluar,200
2026-10-03,Pemasukan,300
2026-10-04,Misc,400
';
    $import = uploadImport($csv);

    $types = loadImportValues($import['id'], ['recorded_at' => 0, 'type' => 1, 'amount' => 2])->assertOk()->json('data.values.types');

    expect($types['found'])->toBe(['Pemasukan', 'Keluar', 'Misc'])
        ->and($types['suggestion'])->toBe(['income' => 'Pemasukan', 'expense' => 'Keluar', 'transfer' => '']);
});

it('takes the values that mean each type from the user, skipping rows that match none', function () {
    $csv = 'Date,Type,Amount
2026-10-01,In-flow,100
2026-10-02,Out-flow,200
2026-10-03,Weird,300
2026-10-04,income,400
2026-10-05,Spend,500
';

    $response = runImport(
        uploadImport($csv),
        mapping: ['recorded_at' => 0, 'type' => 1, 'amount' => 2],
        types: ['income' => 'in-flow', 'expense' => ' OUT-FLOW , Spend', 'transfer' => ''],
    )->assertOk();

    expect($response->json('data.imported'))->toBe(4)
        ->and($response->json('data.skipped'))->toBe(1)
        ->and($response->json('data.errors.0.row'))->toBe(4)
        ->and(Record::where('amount', 100)->value('type')->value)->toBe('income')
        ->and(Record::where('amount', 200)->value('type')->value)->toBe('expense')
        ->and(Record::where('amount', 400)->value('type')->value)->toBe('income')
        ->and(Record::where('amount', 500)->value('type')->value)->toBe('expense');
});

it('requires the date and amount columns to be mapped', function () {
    $import = uploadImport(IMPORT_CSV);

    loadImportValues($import['id'], ['type' => 1])
        ->assertStatus(422)->assertJsonValidationErrors(['mapping.recorded_at', 'mapping.amount']);
});

it('imports records mapping existing, new and unmapped accounts and categories', function () {
    $cash = Account::factory()->create(['name' => 'Cash', 'last_balance' => 0]);
    $food = Category::factory()->create(['name' => 'Food']);
    $import = uploadImport(IMPORT_CSV);

    $response = runImport(
        $import,
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
        ->and($lunch->amount)->toBe(25000)
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
        ->and((int) $bca->refresh()->last_balance)->toBe(1050000)
        ->and((int) $cash->refresh()->last_balance)->toBe(-75000);
});

it('removes the import and its file once imported, leaving other imports alone', function () {
    $done = uploadImport(IMPORT_CSV);
    $other = uploadImport("Date,Amount\n2026-10-01,100\n", 'other.csv');
    $path = RecordImport::findOrFail($done['id'])->path;

    runImport($done)->assertOk();

    Storage::disk('local')->assertMissing($path);
    expect(RecordImport::pluck('id')->all())->toBe([$other['id']]);

    $this->actingAs($this->user)->postJson("/api/records/imports/{$done['id']}/run", ['types' => [], 'accounts' => [], 'categories' => []])->assertNotFound();
});

it('discards an import together with its file', function () {
    $import = uploadImport(IMPORT_CSV);
    $path = RecordImport::findOrFail($import['id'])->path;

    $this->actingAs($this->user)->deleteJson("/api/records/imports/{$import['id']}")->assertNoContent();

    Storage::disk('local')->assertMissing($path);
    expect(RecordImport::count())->toBe(0);
});

it('refuses to run an import whose columns are not mapped yet', function () {
    $import = uploadImport(IMPORT_CSV);
    RecordImport::whereKey($import['id'])->update(['mapping' => json_encode(['recorded_at' => null, 'amount' => null])]);

    $this->actingAs($this->user)->postJson("/api/records/imports/{$import['id']}/run", ['types' => [], 'accounts' => [], 'categories' => []])
        ->assertStatus(422)->assertJsonValidationErrors('import');
});

it('reports an import whose file is gone', function () {
    $import = uploadImport(IMPORT_CSV);
    Storage::disk('local')->delete(RecordImport::findOrFail($import['id'])->path);

    loadImportValues($import['id'])->assertStatus(422)->assertJsonValidationErrors('import');
});

it('drops imports nobody touched for a month when a new file is uploaded', function () {
    $old = uploadImport(IMPORT_CSV, 'old.csv');
    $path = RecordImport::findOrFail($old['id'])->path;
    RecordImport::whereKey($old['id'])->update(['updated_at' => now()->subDays(31)]);

    $fresh = uploadImport(IMPORT_CSV, 'fresh.csv');

    Storage::disk('local')->assertMissing($path);
    expect(RecordImport::pluck('id')->all())->toBe([$fresh['id']]);
});

it('puts every record in the unmapped account when no account column is mapped, and reuses it', function () {
    $csv = "Date,Amount\n2026-10-01,-1500\n2026-10-02,2500\n";
    $mapping = ['recorded_at' => 0, 'amount' => 1];

    foreach ([1, 2] as $run) {
        runImport(uploadImport($csv), mapping: $mapping)->assertOk()->assertJsonPath('data.imported', 2);
    }

    $unmapped = Account::where('name', RecordImportService::UNMAPPED_ACCOUNT)->get();
    expect($unmapped)->toHaveCount(1)
        ->and(Record::count())->toBe(4)
        ->and(Record::where('type', 'expense')->count())->toBe(2)
        ->and(Record::where('type', 'income')->count())->toBe(2)
        ->and(Record::where('type', 'expense')->first()->amount)->toBe(1500);
});

it('creates the categories that do not exist, one per record type', function () {
    $csv = "Date,Type,Amount,Category\n2026-10-01,expense,100,Gift\n2026-10-02,income,200,Gift\n2026-10-03,expense,300,Gift\n2026-10-04,transfer,400,Gift\n";

    $response = runImport(
        uploadImport($csv),
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
    runImport(
        uploadImport("Date,Amount,Category\n2026-10-01,-100,Gift\n"),
        categories: [['value' => 'Gift', 'action' => 'none']],
        mapping: ['recorded_at' => 0, 'amount' => 1, 'category' => 2],
    )->assertOk();

    expect(Record::firstOrFail()->category_id)->toBeNull()->and(Category::count())->toBe(0);
});

it('swaps a mapped category for the same-named one of the record type, creating it when missing', function () {
    $incomeOnly = Category::factory()->income()->create(['name' => 'Bonus']);
    $csv = "Date,Type,Amount,Category\n2026-10-01,expense,100,Bonus\n2026-10-02,income,200,Bonus\n";

    runImport(
        uploadImport($csv),
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

    $response = runImport(
        uploadImport($csv),
        [['value' => 'A', 'action' => 'create'], ['value' => 'B', 'action' => 'create']],
        mapping: ['recorded_at' => 0, 'type' => 1, 'account' => 2, 'to_account' => 3, 'amount' => 4],
    )->assertOk();

    expect($response->json('data.imported'))->toBe(1)->and($response->json('data.skipped'))->toBe(2);
});

it('imports rows whose amount is zero', function () {
    $csv = 'Date,Type,Amount
2026-10-01,expense,0
2026-10-02,income,"0,00"
2026-10-03,expense,abc
';

    $response = runImport(uploadImport($csv), mapping: ['recorded_at' => 0, 'type' => 1, 'amount' => 2])->assertOk();

    expect($response->json('data.imported'))->toBe(2)
        ->and($response->json('data.skipped'))->toBe(1)
        ->and(Record::where('type', 'expense')->value('amount'))->toBe(0)
        ->and(Record::where('type', 'income')->value('amount'))->toBe(0);
});

it('works through a big file in batches, reporting progress and surviving a repeated batch', function () {
    config(['import.batch_size' => 4]);
    $rows = collect(range(1, 10))->map(fn ($i) => "2026-10-01,{$i},Bank ".($i % 3))->implode('
');
    $import = uploadImport("Date,Amount,Account
{$rows}
");
    $mapping = ['recorded_at' => 0, 'amount' => 1, 'account' => 2];
    $user = test()->user;
    $call = fn (string $path, array $body) => test()->actingAs($user)->postJson("/api/records/imports/{$import['id']}/{$path}", $body)->assertOk();

    // Scanning the values: 4 rows per call.
    $first = $call('values', ['mapping' => $mapping, 'offset' => 0]);
    expect($first->json('progress'))->toBe(['offset' => 4, 'total' => 10, 'percent' => 40, 'done' => false])
        ->and($first->json('data.values'))->toBeNull();
    $call('values', ['mapping' => $mapping, 'offset' => 4])->assertJsonPath('progress.offset', 8);
    $last = $call('values', ['mapping' => $mapping, 'offset' => 8]);
    expect($last->json('progress'))->toBe(['offset' => 10, 'total' => 10, 'percent' => 100, 'done' => true])
        ->and(collect($last->json('data.values.accounts'))->pluck('value')->all())->toBe(['Bank 1', 'Bank 2', 'Bank 0'])
        ->and($last->json('data.step'))->toBe('values');

    // Importing: 4 rows per call; repeating a batch that already ran does not run it again.
    $body = ['types' => [], 'accounts' => [['value' => 'Bank 1', 'action' => 'create'], ['value' => 'Bank 2', 'action' => 'create'], ['value' => 'Bank 0', 'action' => 'create']], 'categories' => []];
    $one = $call('run', [...$body, 'offset' => 0]);
    expect($one->json('progress.done'))->toBeFalse()->and($one->json('data.imported'))->toBe(4);
    $again = $call('run', [...$body, 'offset' => 0])->json();
    expect(Record::count())->toBe(4)->and($again['progress']['offset'])->toBe(4);

    $call('run', [...$body, 'offset' => 4])->assertJsonPath('data.imported', 8);
    $done = $call('run', [...$body, 'offset' => 8]);
    expect($done->json('progress'))->toBe(['offset' => 10, 'total' => 10, 'percent' => 100, 'done' => true])
        ->and($done->json('data.imported'))->toBe(10)
        ->and(Record::count())->toBe(10)
        ->and(Account::whereIn('name', ['Bank 0', 'Bank 1', 'Bank 2'])->count())->toBe(3)
        ->and(RecordImport::count())->toBe(0);
});

it('reads amounts written the Indonesian way', function () {
    $csv = "Date,Amount\n2026-10-01,\"Rp 1.250.000,50\"\n2026-10-02,\"-2,500\"\n";

    runImport(uploadImport($csv), mapping: ['recorded_at' => 0, 'amount' => 1])->assertOk();

    expect(Record::where('type', 'income')->value('amount'))->toBe(1250001)
        ->and(Record::where('type', 'expense')->value('amount'))->toBe(2500);
});

it('imports an xlsx file with real date cells', function () {
    $sheet = new Spreadsheet;
    $active = $sheet->getActiveSheet();
    $active->fromArray([['Date', 'Amount', 'Note'], [null, 7500, 'From excel']]);
    $active->setCellValue('A2', 46297.5);
    $active->getStyle('A2')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DATETIME);
    $path = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
    (new Xlsx($sheet))->save($path);

    $import = $this->actingAs($this->user)->postJson('/api/records/imports', [
        'file' => new UploadedFile($path, 'records.xlsx', null, null, true),
    ])->assertCreated()->json('data');

    runImport($import, mapping: ['recorded_at' => 0, 'amount' => 1, 'note' => 2])->assertOk()->assertJsonPath('data.imported', 1);

    $record = Record::firstOrFail();
    expect($record->recorded_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i'))->toBe('2026-10-02 12:00')
        ->and($record->note)->toBe('From excel');

    unlink($path);
});
