<?php

namespace Database\Factories;

use App\Models\Debt;
use App\Models\SubDebt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubDebt>
 */
class SubDebtFactory extends Factory
{
    private const NAMES = ['Cicilan bulan ini', 'Cicilan bulan depan', 'Uang muka', 'Pelunasan akhir', 'Cicilan ke-2', 'Cicilan ke-3'];

    private const NOTES = ['Transfer lewat BCA', 'Dibayar tunai', 'Menunggu gajian', 'Dicicil dua kali'];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->numberBetween(5, 100) * 10_000;

        return [
            'debt_id' => Debt::factory(),
            'name' => fake()->randomElement(self::NAMES),
            'amount' => $amount,
            'due_date' => fake()->dateTimeBetween('-1 month', '+6 months')->format('Y-m-d'),
            'note' => fake()->boolean(50) ? fake()->randomElement(self::NOTES) : null,
        ];
    }
}
