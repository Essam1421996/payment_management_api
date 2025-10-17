<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = auth('api')->login($this->user);
    }

    public function test_user_can_create_order(): void
    {
        $orderData = [
            'customer_name' => $this->faker->name,
            'customer_email' => $this->faker->email,
            'customer_address' => $this->faker->address,
            'items' => [
                [
                    'product_name' => 'Product 1',
                    'quantity' => 2,
                    'price' => 10.50
                ],
                [
                    'product_name' => 'Product 2',
                    'quantity' => 1,
                    'price' => 25.00
                ]
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/orders', $orderData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'customer_name',
                    'customer_email',
                    'customer_address',
                    'items',
                    'total_amount',
                    'status',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'customer_name' => $orderData['customer_name'],
            'total_amount' => 46.00
        ]);
    }

    public function test_user_can_view_orders(): void
    {
        Order::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'customer_name',
                            'customer_email',
                            'customer_address',
                            'items',
                            'total_amount',
                            'status',
                            'created_at',
                            'updated_at'
                        ]
                    ],
                    'current_page',
                    'per_page',
                    'total'
                ]
            ]);
    }

    public function test_user_can_view_specific_order(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'customer_name',
                    'customer_email',
                    'customer_address',
                    'items',
                    'total_amount',
                    'status',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $order->id
                ]
            ]);
    }

    public function test_user_can_update_order(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);

        $updateData = [
            'customer_name' => 'Updated Name',
            'status' => 'confirmed'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->putJson("/api/orders/{$order->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Order updated successfully'
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'customer_name' => 'Updated Name',
            'status' => 'confirmed'
        ]);
    }

    public function test_user_can_delete_order_without_payments(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Order deleted successfully'
            ]);

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_user_cannot_delete_order_with_payments(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);
        $order->payments()->create([
            'payment_id' => 'test_payment',
            'status' => 'successful',
            'payment_method' => 'credit_card',
            'gateway_name' => 'credit_card',
            'amount' => $order->total_amount
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot delete order with associated payments'
            ]);
    }

    public function test_user_can_filter_orders_by_status(): void
    {
        Order::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);
        Order::factory()->create(['user_id' => $this->user->id, 'status' => 'confirmed']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/orders?status=confirmed');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals('confirmed', $data[0]['status']);
    }

    public function test_validation_errors_for_invalid_order_data(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/orders', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors'
            ]);
    }
}
