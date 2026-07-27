<?php

namespace Database\Factories;

use App\Models\User;
use App\Support\Locale\Locales;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            // Explicit, not left to the database default: a freshly created
            // model does not re-read the row, so these would be null in
            // memory — and the activity log would then record a change from
            // null to the real value, which never happened.
            'locale' => Locales::FALLBACK,
            'timezone' => 'Asia/Almaty',
        ];
    }

    /**
     * Administrators may manage the panel.
     *
     * afterCreating, not a state: a role is a relation, and the row must
     * exist before it can be attached.
     */
    public function admin(): static
    {
        return $this->afterCreating(
            fn (User $user) => $user->assignRole(RoleSeeder::ADMIN),
        );
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
