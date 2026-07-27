<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->company().' Team',
            'personal_team' => false,
        ];
    }

    public function personal(): static
    {
        return $this->state(fn (array $attributes) => [
            'personal_team' => true,
        ]);
    }

    public function withStripeCustomer(?string $stripeId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'stripe_id' => $stripeId ?? 'cus_'.fake()->unique()->regexify('[A-Za-z0-9]{14}'),
        ]);
    }
}
