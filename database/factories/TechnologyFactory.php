<?php

namespace Database\Factories;

use App\Models\Technology;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Technology> */
class TechnologyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'icon_type' => 'lucide',
            'icon' => 'code-xml',
        ];
    }

    public function brand(string $slug, string $name): static
    {
        return $this->state(['name' => $name, 'icon_type' => 'simple', 'icon' => $slug]);
    }
}
