<?php

namespace Database\Factories;

use App\Models\Series;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Series> */
class SeriesFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => ucwords(fake()->unique()->words(2, true)),
        ];
    }
}
