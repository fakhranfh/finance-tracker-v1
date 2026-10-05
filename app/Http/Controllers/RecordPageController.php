<?php

namespace App\Http\Controllers;

use App\Enums\RecordType;
use App\Models\Account;
use Illuminate\View\View;

class RecordPageController extends Controller
{
    public function index(): View
    {
        return view('records.index', [
            'typeLabels' => $this->typeLabels(),
            'hasAccounts' => Account::query()->exists(),
            'accounts' => Account::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        return view('records.form', $this->formData(null));
    }

    public function edit(int $id): View
    {
        return view('records.form', $this->formData($id));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?int $id): array
    {
        return [
            'id' => $id,
            'typeLabels' => $this->typeLabels(),
            'accounts' => Account::query()->orderBy('name')->get(['id', 'name']),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function typeLabels(): array
    {
        return collect(RecordType::cases())->mapWithKeys(fn (RecordType $type) => [$type->value => $type->label()])->all();
    }
}
