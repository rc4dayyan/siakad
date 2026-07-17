<?php

namespace Database\Factories;

use App\Models\TahunAkademik;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TahunAkademik>
 */
class TahunAkademikFactory extends Factory
{
    protected $model = TahunAkademik::class;

    public function definition(): array
    {
        $year = fake()->numberBetween(2020, 2040);
        $term = fake()->randomElement([TahunAkademik::TERM_GANJIL, TahunAkademik::TERM_GENAP]);

        return [
            'name' => "Tahun Akademik {$year}/".($year + 1).' '.ucfirst($term),
            'code' => fake()->unique()->bothify("{$year}-{$term}-####"),
            'semester' => $term === TahunAkademik::TERM_GANJIL ? 1 : 2,
            'year_start' => $year,
            'year_end' => $year + 1,
            'term' => $term,
            'starts_at' => "{$year}-08-01",
            'ends_at' => ($year + 1).'-07-31',
            'status' => TahunAkademik::STATUS_DRAFT,
            'is_active' => false,
        ];
    }
}
