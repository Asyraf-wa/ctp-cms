<?php

namespace Database\Factories;

use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Media> */
class MediaFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->slug(2);

        return [
            'disk' => 'media',
            'path' => "2026/10/{$name}.jpg",
            'filename' => "{$name}.jpg",
            'name' => Str::headline($name),
            'mime' => 'image/jpeg',
            'extension' => 'jpg',
            'size' => 12345,
            'width' => 1600,
            'height' => 900,
            'hash' => sha1($name),
        ];
    }
}
