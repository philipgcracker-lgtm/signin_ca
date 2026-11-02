<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ConsentText>
 */
class ConsentTextFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'version' => 'test-'.fake()->unique()->numerify('####'),
            'body' => 'I agree to receive a text from {agent_name} ({brokerage}).',
            'effective_from' => now()->subDay(),
        ];
    }
}
