<?php

namespace Database\Factories;

use App\Enums\DebtType;
use App\Models\Debt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Debt>
 */
class DebtFactory extends Factory
{
    private const NAMES = ['Budi Santoso', 'Siti Rahmawati', 'Agus Pratama', 'Dewi Lestari', 'Rizky Ramadhan', 'Putri Anggraini', 'Hendra Wijaya', 'Nur Aisyah', 'Dimas Saputra', 'Rina Marlina', 'Bank BCA', 'Koperasi Sejahtera'];

    private const NOTES = [
        'debt' => ['Pinjaman untuk bayar kuliah', 'Pinjam untuk biaya berobat', 'Talangan bayar kontrakan', 'Cicilan motor bulan ini', 'Pinjaman modal usaha', 'Pinjam uang untuk servis mobil'],
        'receivable' => ['Dipinjam untuk biaya sekolah anak', 'Talangan makan bareng teman kantor', 'Dipinjam untuk bayar kontrakan', 'Pinjaman modal jualan online', 'Dipinjam buat beli tiket mudik', 'Patungan hadiah belum diganti'],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(DebtType::cases());
        $amount = fake()->numberBetween(5, 500) * 100_000 / 10;
        $isPaid = fake()->boolean(30);

        return [
            'type' => $type,
            'name' => fake()->randomElement(self::NAMES),
            'amount' => $amount,
            'paid_amount' => $isPaid ? $amount : fake()->numberBetween(0, 8) * $amount / 10,
            'due_date' => fake()->dateTimeBetween('-1 month', '+6 months')->format('Y-m-d'),
            'notes' => fake()->boolean(70) ? fake()->randomElement(self::NOTES[$type->value]) : null,
            'is_paid' => $isPaid,
        ];
    }
}
