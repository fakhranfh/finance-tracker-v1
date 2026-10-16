<?php

namespace App\Traits\Webhook;

use App\Models\Webhook;
use Illuminate\Support\Collection;

/**
 * Dev-only sample-data generator for the webhooks list page.
 * Per .ai/rules/dev-autofill.md.
 */
trait GeneratesSampleData
{
    /**
     * Create sample webhooks backed by real database records.
     *
     * @return Collection<int, Webhook>
     */
    protected function generateSampleWebhooks(int $count = 10): Collection
    {
        return Webhook::factory()->count($count)->create();
    }
}
