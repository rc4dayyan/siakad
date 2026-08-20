<?php

namespace Database\Factories;

use App\Models\PeriodeAkademik;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PeriodeAkademik>
 */
class PeriodeAkademikFactory extends Factory
{
    protected $model = PeriodeAkademik::class;

    public function definition(): array
    {
        $year = fake()->numberBetween(2020, 2040);
        $term = fake()->randomElement([PeriodeAkademik::TERM_GANJIL, PeriodeAkademik::TERM_GENAP]);

        return [
            'name' => "{$year}/".($year + 1).' '.ucfirst($term),
            'code' => fake()->unique()->bothify("{$year}-{$term}-####"),
            'semester' => $term === PeriodeAkademik::TERM_GANJIL ? 1 : 2,
            'year_start' => $year,
            'year_end' => $year + 1,
            'term' => $term,
            'starts_at' => "{$year}-08-01",
            'ends_at' => ($year + 1).'-07-31',
            'status' => PeriodeAkademik::STATUS_DRAFT,
            'is_active' => false,
        ];
    }
}
