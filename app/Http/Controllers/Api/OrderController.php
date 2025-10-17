<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['user', 'payments'])->where('user_id', Auth::id());

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $perPage = $request->get('per_page', 15);
        $orders = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'data' => OrderResource::collection($orders->items()),
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'last_page' => $orders->lastPage(),
                'from' => $orders->firstItem(),
                'to' => $orders->lastItem()
            ]
        ]);
    }

    public function store(CreateOrderRequest $request): JsonResponse
    {

        $items = $request->items;
        $totalAmount = collect($items)->sum(function ($item) {
            return $item['quantity'] * $item['price'];
        });

        $order = Order::create([
            'user_id' => Auth::id(),
            'customer_name' => $request->customer_name,
            'customer_email' => $request->customer_email,
            'customer_address' => $request->customer_address,
            'items' => $items,
            'total_amount' => $totalAmount,
            'status' => 'pending'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data' => new OrderResource($order->load('user'))
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $order = Order::with(['user', 'payments'])->where('user_id', Auth::id())->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order)
        ]);
    }

    public function update(UpdateOrderRequest $request, string $id): JsonResponse
    {
        $order = Order::where('user_id', Auth::id())->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        $updateData = $request->only(['customer_name', 'customer_email', 'customer_address', 'status']);

        if ($request->has('items')) {
            $items = $request->items;
            $totalAmount = collect($items)->sum(function ($item) {
                return $item['quantity'] * $item['price'];
            });
            $updateData['items'] = $items;
            $updateData['total_amount'] = $totalAmount;
        }

        $order->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully',
            'data' => new OrderResource($order->load('user', 'payments'))
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $order = Order::where('user_id', Auth::id())->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        if (!$order->canBeDeleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete order with associated payments'
            ], 422);
        }

        $order->delete();

        return response()->json([
            'success' => true,
            'message' => 'Order deleted successfully'
        ]);
    }
}
