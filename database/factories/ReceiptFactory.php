<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Receipt>
 */
class ReceiptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10, 1000);
        $taxAmount = $subtotal * 0.1; // 10% tax
        $totalAmount = $subtotal + $taxAmount;

        return [
            'number' => 'RCP-' . fake()->unique()->numerify('######'),
            'member_id' => null,
            'total' => $totalAmount,
            'status' => fake()->randomElement(['pending', 'paid', 'cancelled', 'refunded']),
            'payment_method' => fake()->randomElement(['cash', 'credit_card', 'debit_card', 'paypal', 'bank_transfer']),
            'date' => fake()->dateTimeBetween('-1 year', 'now'),
            'description' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the receipt is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
        ]);
    }

    /**
     * Indicate that the receipt is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }
}
