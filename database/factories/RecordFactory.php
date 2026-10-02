<?php

namespace Database\Factories;

use App\Enums\RecordType;
use App\Models\Account;
use App\Models\Record;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Record>
 */
class RecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => RecordType::Expense,
            'account_id' => Account::factory(),
            'to_account_id' => null,
            'amount' => fake()->numberBetween(1, 500) * 1000,
            'note' => fake()->randomElement(['Makan siang', 'Bensin motor', 'Gaji bulanan', 'Belanja bulanan', 'Listrik', 'Internet', 'Kopi', 'Parkir', 'Transportasi online', 'Bonus proyek', 'Top up e-wallet', 'Nonton bioskop']),
            'recorded_at' => fake()->dateTimeBetween('-1 month'),
        ];
    }
}
