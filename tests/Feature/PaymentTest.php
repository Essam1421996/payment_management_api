<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PaymentTest extends TestCase
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

    public function test_user_can_process_credit_card_payment(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'confirmed',
            'total_amount' => 100.00
        ]);

        $paymentData = [
            'order_id' => $order->id,
            'payment_method' => 'credit_card',
            'card_number' => '4111111111111111',
            'cvv' => '123',
            'expiry_date' => '12/25'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/payments', $paymentData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'payment_id',
                    'order_id',
                    'status',
                    'payment_method',
                    'gateway_name',
                    'amount'
                ]
            ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'payment_method' => 'credit_card',
            'gateway_name' => 'credit_card'
        ]);
    }

    public function test_user_can_process_paypal_payment(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'confirmed',
            'total_amount' => 50.00
        ]);

        $paymentData = [
            'order_id' => $order->id,
            'payment_method' => 'paypal',
            'paypal_email' => 'test@example.com'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/payments', $paymentData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'payment_id',
                    'order_id',
                    'status',
                    'payment_method',
                    'gateway_name',
                    'amount'
                ]
            ]);
    }

    public function test_user_can_view_payments(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);
        Payment::factory()->count(2)->create(['order_id' => $order->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/payments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'payment_id',
                            'order_id',
                            'status',
                            'payment_method',
                            'gateway_name',
                            'amount',
                            'gateway_response',
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

    public function test_user_can_view_specific_payment(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);
        $payment = Payment::factory()->create(['order_id' => $order->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson("/api/payments/{$payment->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $payment->id,
                    'order_id' => $order->id
                ]
            ]);
    }

    public function test_user_can_filter_payments_by_order(): void
    {
        $order1 = Order::factory()->create(['user_id' => $this->user->id]);
        $order2 = Order::factory()->create(['user_id' => $this->user->id]);
        
        Payment::factory()->create(['order_id' => $order1->id]);
        Payment::factory()->create(['order_id' => $order2->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson("/api/payments?order_id={$order1->id}");

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals($order1->id, $data[0]['order_id']);
    }

    public function test_user_can_filter_payments_by_status(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);
        Payment::factory()->create(['order_id' => $order->id, 'status' => 'successful']);
        Payment::factory()->create(['order_id' => $order->id, 'status' => 'failed']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/payments?status=successful');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals('successful', $data[0]['status']);
    }

    public function test_cannot_process_payment_for_pending_order(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending'
        ]);

        $paymentData = [
            'order_id' => $order->id,
            'payment_method' => 'credit_card',
            'card_number' => '4111111111111112',
            'cvv' => '123',
            'expiry_date' => '12/25'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/payments', $paymentData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Order must be in confirmed status to process payment'
            ]);
    }

    public function test_can_get_available_payment_gateways(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/payment-gateways');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'name',
                        'display_name',
                        'gateway_name'
                    ]
                ]
            ]);
    }

    public function test_validation_errors_for_invalid_payment_data(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/payments', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors'
            ]);
    }
}
