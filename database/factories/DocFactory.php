<?php

namespace Database\Factories;

use App\Models\Doc;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Doc>
 */
class DocFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'attachment' => null,
        ];
    }
}
