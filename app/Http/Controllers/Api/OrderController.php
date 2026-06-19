<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $orderData = DB::transaction(function () use ($request) {
                $items = $request->input('items');
                $orderItemsToCreate = [];
                $totalPrice = 0;

                // 1. Lock and validate all products to prevent negative stocks and deadlocks
                foreach ($items as $item) {
                    $product = Product::lockForUpdate()->find($item['product_id']);

                    if (!$product || $product->stock < $item['quantity']) {
                        throw new \RuntimeException(
                            "Insufficient stock for product " . ($product ? $product->name : 'Unknown')
                        );
                    }

                    // Decrement stock
                    $product->stock -= $item['quantity'];
                    $product->save();

                    $subtotal = $product->price * $item['quantity'];
                    $totalPrice += $subtotal;

                    $orderItemsToCreate[] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'quantity' => $item['quantity'],
                        'price' => $product->price,
                        'subtotal' => $subtotal,
                    ];
                }

                // 2. Generate unique order number (ORD-YYYYMMDD-XXXXXX)
                $date = date('Ymd');
                $randomSuffix = str_pad(random_int(1, 999999), 6, '0', STR_PAD_LEFT);
                $orderNumber = 'ORD-' . $date . '-' . $randomSuffix;

                // 3. Create Order
                $order = Order::create([
                    'order_number' => $orderNumber,
                    'total_price' => $totalPrice,
                ]);

                // 4. Create OrderItems
                foreach ($orderItemsToCreate as $itemData) {
                    $order->orderItems()->create([
                        'product_id' => $itemData['product_id'],
                        'quantity' => $itemData['quantity'],
                        'price' => $itemData['price'],
                        'subtotal' => $itemData['subtotal'],
                    ]);
                }

                return [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'total_price' => $order->total_price,
                    'items' => $orderItemsToCreate,
                ];
            });

            return response()->json([
                'message' => 'Order created successfully',
                'data' => $orderData
            ], 201);

        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 409);
        }
    }

    public function show($id)
    {
        $order = Order::with('orderItems.product')->find($id);

        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Order retrieved successfully',
            'data' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'total_price' => $order->total_price,
                'items' => $order->orderItems->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product ? $item->product->name : 'Unknown',
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'subtotal' => $item->subtotal,
                    ];
                })
            ]
        ], 200);
    }
}
