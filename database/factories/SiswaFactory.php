<?php

namespace Database\Factories;

use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;

class SiswaFactory extends Factory
{
    protected $model = Siswa::class;

    public function definition(): array
    {
        return [
            'nipd' => $this->faker->unique()->numerify('#####'),
            'nama_siswa' => $this->faker->name(),
            'jenis_kelamin' => $this->faker->randomElement(['L', 'P']),
            'angkatan' => $this->faker->numberBetween(2020, 2025),
            'status_siswa' => 'aktif',
        ];
    }
}
