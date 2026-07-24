<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_unit' => $this->faker->unique()->randomElement([
                'Sumber Daya Manusia',
                'Keuangan',
                'Teknologi Informasi',
                'Pemasaran',
                'Operasional',
                'Layanan Pelanggan',
                'Riset & Pengembangan',
                'Logistik',
            ]),
        ];
    }
}
