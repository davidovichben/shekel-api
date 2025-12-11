<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Member>
 */
class MemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'mobile' => fake()->optional()->phoneNumber(),
            'phone' => fake()->optional()->phoneNumber(),
            'email' => fake()->optional()->unique()->safeEmail(),
            'address' => fake()->optional()->streetAddress(),
            'address_2' => fake()->optional()->secondaryAddress(),
            'city' => fake()->optional()->city(),
            'country' => fake()->optional()->country(),
            'zipcode' => fake()->optional()->postcode(),
            'gregorian_birth_date' => fake()->optional()->dateTimeBetween('-80 years', '-18 years'),
            'hebrew_birth_date' => fake()->optional()->randomElement([
                '15 Tishrei 5743',
                '22 Adar 5755',
                '3 Elul 5760',
                '18 Iyar 5770',
            ]),
            'gregorian_wedding_date' => fake()->optional()->dateTimeBetween('-40 years', 'now'),
            'hebrew_wedding_date' => fake()->optional()->randomElement([
                '7 Av 5765',
                '14 Shevat 5772',
                '29 Sivan 5780',
            ]),
            'gregorian_death_date' => fake()->optional(0.1)->dateTimeBetween('-10 years', 'now'),
            'hebrew_death_date' => fake()->optional(0.1)->randomElement([
                '11 Kislev 5783',
                '20 Tammuz 5784',
            ]),
            'contact_person' => fake()->optional()->name(),
            'contact_person_type' => fake()->optional()->randomElement([
                'child',
                'parent',
                'sibling',
                'spouse',
                'brother-in-law',
                'grandparent',
                'son-in-law',
                'guest',
                'phone_operator',
                'other'
            ]),
            'tag' => fake()->optional()->words(3, true),
            'title' => fake()->optional()->randomElement(['Mr.', 'Mrs.', 'Ms.', 'Dr.', 'Rabbi']),
            'type' => fake()->randomElement([
                'permanent',
                'family_member',
                'guest',
                'supplier',
                'other',
                'primary_admin',
                'secondary_admin'
            ]),
            'member_number' => 'MEM-' . fake()->unique()->numerify('######'),
            'has_website_account' => fake()->boolean(30), // 30% chance of having website account
            'should_mail' => fake()->boolean(80), // 80% chance of receiving mail
        ];
    }

    /**
     * Indicate that the member is a permanent member.
     */
    public function permanent(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'permanent',
            'has_website_account' => true,
        ]);
    }

    /**
     * Indicate that the member is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'primary_admin',
            'has_website_account' => true,
        ]);
    }

    /**
     * Indicate that the member is a guest.
     */
    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'guest',
            'has_website_account' => false,
        ]);
    }

    /**
     * Indicate that the member has a website account.
     */
    public function withWebsiteAccount(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_website_account' => true,
        ]);
    }

    /**
     * Indicate that the member should not receive mail.
     */
    public function noMail(): static
    {
        return $this->state(fn (array $attributes) => [
            'should_mail' => false,
        ]);
    }
}
