<?php

namespace Database\Factories;

use App\Models\State;
use App\Models\TransitCompanyUnion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TransitCompany>
 */
class TransitCompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->company,
            'short_name' => fake()->word,
            'reg_no' => fake()->unique()->numerify('TRC-####'),
            'url' => fake()->url,
            'email' => fake()->unique()->safeEmail,
            'union_id' => TransitCompanyUnion::factory(),
            'union_states_chapter' => State::factory(),
            'ver_code' => fake()->numerify('####'),
            'type' => fake()->randomElement(['bus', 'boat', 'train']),
        ];
    }

    public function forUser(User $user)
    {
        return $this->state([
            'user_id' => $user->id,
        ]);
    }

    public function forUnion(TransitCompanyUnion $union)
    {
        return $this->state([
            'union_id' => $union->id,
        ]);
    }

    public function forState(State $state)
    {
        return $this->state([
            'union_states_chapter' => $state->id,
        ]);
    }

}
