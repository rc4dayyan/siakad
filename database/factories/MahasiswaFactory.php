<?php

namespace Database\Factories;

use App\Models\Mahasiswa;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Mahasiswa>
 */
class MahasiswaFactory extends Factory
{
    protected $model = Mahasiswa::class;

    public function definition(): array
    {
        $code = fake()->unique()->numerify('MHS########');

        return [
            'taka_id' => 0,
            'years_id' => 0,
            'class_id' => 0,
            'mhs_stat' => 1,
            'mhs_nim' => fake()->unique()->numerify('20########'),
            'mhs_name' => fake()->name(),
            'mhs_code' => $code,
            'mhs_user' => strtolower($code),
            'password' => Hash::make('password'),
            'mhs_mail' => fake()->unique()->safeEmail(),
            'mhs_phone' => fake()->unique()->numerify('08##########'),
        ];
    }
}
