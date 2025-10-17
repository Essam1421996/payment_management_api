<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $items = [
            [
                'product_name' => $this->faker->word,
                'quantity' => $this->faker->numberBetween(1, 5),
                'price' => $this->faker->randomFloat(2, 10, 100)
            ],
            [
                'product_name' => $this->faker->word,
                'quantity' => $this->faker->numberBetween(1, 3),
                'price' => $this->faker->randomFloat(2, 5, 50)
            ]
        ];

        $totalAmount = collect($items)->sum(function ($item) {
            return $item['quantity'] * $item['price'];
        });

        return [
            'user_id' => \App\Models\User::factory(),
            'customer_name' => $this->faker->name,
            'customer_email' => $this->faker->email,
            'customer_address' => $this->faker->address,
            'items' => $items,
            'total_amount' => $totalAmount,
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'cancelled'])
        ];
    }
}
