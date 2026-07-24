<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nip' => $this->faker->unique()->numerify('##########'),
            'nama' => $this->faker->name(),
            'tanggal_lahir' => $this->faker->dateTimeBetween('-55 years', '-20 years'),
            'tanggal_bergabung' => $this->faker->dateTimeBetween('-10 years', 'now'),
            'status_kawin' => $this->faker->randomElement(['belum_kawin', 'kawin', 'cerai']),
            'jumlah_tanggungan' => $this->faker->numberBetween(0, 4),
            'status_kepegawaian' => 'tetap',
            'status_aktif' => 'aktif',
        ];
    }
}
