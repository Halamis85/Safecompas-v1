<?php
// database/factories/UserFactory.php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * Statické heslo - hashujeme jen jednou pro výkon testů.
     */
    protected static ?string $password = null;

    /**
     * Default state - kompletní uživatel splňující všechny NOT NULL constraints
     * v migracích users + RBAC update.
     */
    public function definition(): array
    {
        $firstname = fake()->firstName();
        $lastname  = fake()->lastName();

        return [
            // Sloupec `name` zůstal v migraci po Laravel skeletu - používáme
            // ho jako "full name" pro konzistenci s UserController::store
            'name'              => trim("{$firstname} {$lastname}"),
            'firstname'         => $firstname,
            'lastname'          => $lastname,
            'alias'             => $firstname,
            'username'          => $this->uniqueUsername($firstname, $lastname),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'remember_token'    => Str::random(10),
            'is_active'         => true,
            'last_login'        => null,
            'preferences'       => null,
            // Starý enum sloupec `role` (před RBAC) - držíme default,
            // dokud nebude odstraněn samostatnou migrací (#2 z analýzy).
            'role'              => 'user',
        ];
    }

    /**
     * Sestaví username, který v testech zůstane jednoznačný i při hodně factory voláních.
     */
    protected function uniqueUsername(string $firstname, string $lastname): string
    {
        $base = strtolower(Str::ascii(substr($firstname, 0, 1) . $lastname));
        $base = preg_replace('/[^a-z0-9]/', '', $base) ?: 'user';

        // Faker unique by mohl po mnoha pokusech selhat - přidáme jistotu vlastním sufixem.
        return $base . fake()->unique()->numberBetween(1000, 999999);
    }

    /**
     * State: neaktivní účet.
     */
    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    /**
     * State: neověřený email.
     */
    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    /**
     * State: vlastní heslo (užitečné pro auth testy, kde testujeme přihlášení).
     */
    public function withPassword(string $plain): static
    {
        return $this->state(fn () => ['password' => Hash::make($plain)]);
    }

    /**
     * After-create hook: po vytvoření uživatele mu přiřadit roli.
     * Použití: User::factory()->withRole('admin')->create();
     *
     * Roli musí seedovat RolePermissionSeeder předtím (testy to dělají v setUp).
     */
    public function withRole(string $roleName): static
    {
        return $this->afterCreating(function (User $user) use ($roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }
        });
    }
}
