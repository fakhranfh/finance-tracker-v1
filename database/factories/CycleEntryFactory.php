<?php

namespace Database\Factories;

use App\Models\CycleEntry;
use App\Models\CycleType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;

/**
 * @extends Factory<CycleEntry>
 */
class CycleEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sample = fake()->randomElement(config('cycle.type_samples'));

        return [
            'cycle_type_id' => CycleType::factory(),
            'name' => fake()->randomElement($sample['products']),
            'price' => $sample['price'],
            'bought_at' => fake()->dateTimeBetween('-60 days', '-30 days')->format('Y-m-d'),
            'finished_at' => null,
        ];
    }

    /**
     * A believable purchase history for the item name: `$count` purchases of one product, each bought the day the
     * previous one ran out and lasting about as long as that kind of product does. Only the newest is still in use.
     *
     * @return Collection<int, CycleEntry>
     */
    public static function createChain(CycleType $type, int $count): Collection
    {
        $samples = collect(config('cycle.type_samples'));
        $sample = $samples->firstWhere('name', preg_replace('/ \d+$/', '', $type->name)) ?? $samples->random();
        $product = fake()->randomElement($sample['products']);
        $price = $sample['price'] + fake()->randomElement([0, 0, 1000, 2000]);
        $boughtAt = today()->subDays(fake()->numberBetween(2, $sample['days'] - 3));
        $finishedAt = null;
        $entries = new Collection;

        for ($i = 0; $i < $count; $i++) {
            $entries->push(CycleEntry::factory()->create([
                'cycle_type_id' => $type->id,
                'name' => $product,
                'price' => $price,
                'bought_at' => $boughtAt->toDateString(),
                'finished_at' => $finishedAt?->toDateString(),
            ]));

            $finishedAt = $boughtAt;
            $boughtAt = $finishedAt->copy()->subDays(fake()->numberBetween($sample['days'] - 5, $sample['days'] + 5));
        }

        return $entries;
    }
}
