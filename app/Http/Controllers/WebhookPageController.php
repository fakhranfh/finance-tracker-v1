<?php

namespace App\Http\Controllers;

use App\Enums\RecordType;
use App\Services\WebhookManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebhookPageController extends Controller
{
    public function index(): View
    {
        $tableHeaders = [
            ['label' => __('webhooks.table.name'), 'sort' => 'name'],
            ['label' => __('webhooks.table.is_active'), 'sort' => 'is_active'],
            ['label' => __('webhooks.table.created_at'), 'sort' => 'created_at'],
            __('webhooks.table.actions'),
        ];

        return view('webhooks.index', ['tableHeaders' => $tableHeaders]);
    }

    public function create(): View
    {
        return view('webhooks.form', ['id' => null, 'recordTypes' => $this->recordTypes()]);
    }

    /**
     * Swagger-style reference for the webhook test endpoint, built from the generated OpenAPI spec
     * (storage/api-docs/api-docs.json). ?webhook={id} preselects a webhook. See .ai/rules/api-docs.md.
     */
    public function apiDocs(Request $request, WebhookManagementService $webhooks): View
    {
        return view('webhooks.api-docs', [
            'operations' => $this->webhookOperations(),
            'types' => collect([RecordType::Expense, RecordType::Income])->mapWithKeys(fn (RecordType $type) => [$type->value => $type->label()])->all(),
            'webhooks' => $webhooks->paginate(perPage: 100, sort: 'name')->getCollection(),
            'selectedWebhookId' => $request->integer('webhook') ?: null,
        ]);
    }

    /**
     * The old test page, now the API docs with this webhook preselected.
     */
    public function test(int $id): RedirectResponse
    {
        return redirect()->route('webhooks.api-docs', ['webhook' => $id]);
    }

    public function show(int $id): View
    {
        $fields = [
            'status' => __('webhooks.show.status'),
            'allowed_ips' => __('webhooks.fields.allowed_ips'),
            'allowed_account_ids' => __('webhooks.fields.allowed_account_ids'),
            'allowed_types' => __('webhooks.fields.allowed_types'),
            'created_at' => __('webhooks.table.created_at'),
        ];

        return view('webhooks.show', ['id' => $id, 'fields' => $fields, 'recordTypes' => $this->recordTypes()]);
    }

    public function logs(Request $request, int $id): View
    {
        $tableHeaders = [
            ['label' => __('webhooks.logs.table.time'), 'sort' => 'created_at'],
            ['label' => __('webhooks.logs.table.status'), 'sort' => 'status_code'],
            __('webhooks.logs.table.message'),
            __('webhooks.logs.table.origin'),
            __('webhooks.logs.table.ip'),
            __('webhooks.logs.table.payload'),
            '',
        ];

        return view('webhooks.logs', ['id' => $id, 'tableHeaders' => $tableHeaders] + $this->backLink($request, $id));
    }

    public function edit(int $id): View
    {
        return view('webhooks.form', ['id' => $id, 'recordTypes' => $this->recordTypes()]);
    }

    /**
     * @return list<array{id: string, method: string, path: string, summary: string, description: string, parameters: list<array<string, mixed>>, body: list<array<string, mixed>>, responses: list<array{code: string, description: string}>}>
     */
    private function webhookOperations(): array
    {
        $file = storage_path('api-docs/api-docs.json');
        $spec = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
        $operations = [];

        foreach ($spec['paths'] ?? [] as $path => $methods) {
            if ($path !== '/webhooks/{id}/test') {
                continue;
            }

            foreach ($methods as $method => $operation) {
                $operations[] = [
                    'id' => $operation['operationId'] ?? md5($method.$path),
                    'method' => strtoupper($method),
                    'path' => $path,
                    'summary' => $this->translatedOperationText($operation['operationId'] ?? null, 'summary', $operation['summary'] ?? ''),
                    'description' => $this->translatedOperationText($operation['operationId'] ?? null, 'description', $operation['description'] ?? ''),
                    'parameters' => array_map(fn (array $parameter) => [
                        'name' => $parameter['name'],
                        'in' => $parameter['in'],
                        'required' => (bool) ($parameter['required'] ?? false),
                        'type' => $parameter['schema']['type'] ?? 'string',
                        'description' => $this->translatedFieldText($parameter['name'], $parameter['description'] ?? ''),
                    ], $operation['parameters'] ?? []),
                    'body' => $this->bodyProperties($operation),
                    'responses' => collect($operation['responses'] ?? [])
                        ->map(fn (array $response, string $code) => ['code' => $code, 'description' => $response['description'] ?? ''])
                        ->values()
                        ->all(),
                ];
            }
        }

        return $operations;
    }

    /**
     * The translated summary or description of an operation, falling back to the spec's English text.
     */
    private function translatedOperationText(?string $operationId, string $field, string $fallback): string
    {
        $key = "webhooks.api_docs.operations.{$operationId}.{$field}";

        return $operationId !== null && __($key) !== $key ? __($key) : $fallback;
    }

    /**
     * The translated description of a parameter or body field, falling back to the spec's English text.
     */
    private function translatedFieldText(string $name, string $fallback): string
    {
        $key = "webhooks.api_docs.fields.{$name}.description";

        return __($key) !== $key ? __($key) : $fallback;
    }

    /**
     * @return list<array{name: string, type: string, description: string, example: mixed, required: bool}>
     */
    private function bodyProperties(array $operation): array
    {
        $schema = $operation['requestBody']['content']['application/json']['schema'] ?? [];
        $required = $schema['required'] ?? [];

        return collect($schema['properties'] ?? [])
            ->map(fn (array $property, string $name) => [
                'name' => $name,
                'type' => $property['type'] ?? 'string',
                'description' => $this->translatedFieldText($name, $property['description'] ?? ''),
                'example' => $property['example'] ?? null,
                'required' => in_array($name, $required, true),
            ])
            ->values()
            ->all();
    }

    /**
     * Where the Back link of the test and logs pages leads: the page they were opened from
     * (?from=show|edit|index), the edit page by default.
     *
     * @return array{from: string, backUrl: string, backLabel: string}
     */
    private function backLink(Request $request, int $id): array
    {
        $from = in_array($request->query('from'), ['show', 'edit', 'index'], true) ? $request->query('from') : 'edit';

        return [
            'from' => $from,
            'backUrl' => match ($from) {
                'show' => route('webhooks.show', $id),
                'index' => route('webhooks.index'),
                default => route('webhooks.edit', $id),
            },
            'backLabel' => __('webhooks.back_to.'.$from),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function recordTypes(): array
    {
        return collect(RecordType::cases())->mapWithKeys(fn (RecordType $type) => [$type->value => $type->label()])->all();
    }
}
