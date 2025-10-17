<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_id' => $this->faker->unique()->uuid,
            'order_id' => \App\Models\Order::factory(),
            'status' => $this->faker->randomElement(['pending', 'successful', 'failed']),
            'payment_method' => $this->faker->randomElement(['credit_card', 'paypal', 'stripe', 'bank_transfer']),
            'gateway_name' => $this->faker->randomElement(['credit_card', 'paypal', 'stripe']),
            'amount' => $this->faker->randomFloat(2, 10, 500),
            'gateway_response' => [
                'gateway' => $this->faker->randomElement(['credit_card', 'paypal', 'stripe']),
                'status' => $this->faker->randomElement(['approved', 'declined']),
                'timestamp' => $this->faker->dateTime()->format('Y-m-d H:i:s')
            ]
        ];
    }
}
