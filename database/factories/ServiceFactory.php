<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'ADHD assessment';

        return [
            'name' => $name,
            'short_name' => $name,
            'description' => 'A complete diagnostic assessment with a consultant psychiatrist.',
            'slug' => Str::lower(Str::random(16)),
            'pathway' => 'adult',
            'duration_minutes' => 90,
            'duration_label' => '75–90 mins',
            'price_pence' => 69500,
            'in_person_price_pence' => null,
            'is_active' => true,
            'sort_order' => 1,
        ];
    }
}
