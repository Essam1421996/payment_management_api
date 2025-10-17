<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    private PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function index(Request $request): JsonResponse
    {
        $query = Payment::with(['order.user'])->whereHas('order', function ($q) {
            $q->where('user_id', Auth::id());
        });

        if ($request->has('order_id')) {
            $query->where('order_id', $request->order_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $perPage = $request->get('per_page', 15);
        $payments = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'data' => PaymentResource::collection($payments->items()),
                'current_page' => $payments->currentPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
                'last_page' => $payments->lastPage(),
                'from' => $payments->firstItem(),
                'to' => $payments->lastItem()
            ]
        ]);
    }

    public function store(ProcessPaymentRequest $request): JsonResponse
    {

        $order = Order::where('user_id', Auth::id())->find($request->order_id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        if (!$order->canProcessPayment()) {
            return response()->json([
                'success' => false,
                'message' => 'Order must be in confirmed status to process payment'
            ], 422);
        }

        $paymentData = $request->only([
            'card_number', 'cvv', 'expiry_date',
            'paypal_email', 'stripe_token'
        ]);

        $result = $this->paymentService->processPayment(
            $order,
            $request->payment_method,
            $paymentData
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => new PaymentResource($result['payment']->load('order'))
            ], 201);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message']
        ], 422);
    }

    public function show(string $id): JsonResponse
    {
        $payment = Payment::with(['order.user'])
            ->whereHas('order', function ($q) {
                $q->where('user_id', Auth::id());
            })
            ->find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new PaymentResource($payment)
        ]);
    }

    public function getAvailableGateways(): JsonResponse
    {
        $gateways = $this->paymentService->getAvailableGateways();

        return response()->json([
            'success' => true,
            'data' => $gateways
        ]);
    }
}
