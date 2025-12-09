<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Order::with('items')->where('user_id', Auth::id());

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->paginate(10);

        return response()->json($orders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            DB::beginTransaction();

            $totalAmount = 0;
            foreach ($request->items as $item) {
                $totalAmount += $item['quantity'] * $item['price'];
            }

            $order = Order::create([
                'user_id' => Auth::id(),
                'total_amount' => $totalAmount,
                'status' => 'pending',
            ]);

            foreach ($request->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }

            DB::commit();

            return response()->json($order->load('items'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create order'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $order = Order::with(['items', 'payments'])->where('user_id', Auth::id())->find($id);

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        return response()->json($order);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $order = Order::where('user_id', Auth::id())->find($id);

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        // Only allow updating details if not confirmed/cancelled? 
        // User didn't specify logic but typically modifications are restricted.
        // Assuming we can update status or items.
        // "Update Order: Modify existing order details."

        $validator = Validator::make($request->all(), [
            'status' => 'in:pending,confirmed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $order->update($request->only('status'));

        return response()->json($order);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $order = Order::where('user_id', Auth::id())->find($id);

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        if ($order->payments()->exists()) {
            return response()->json(['error' => 'Cannot delete order with associated payments'], 400);
        }

        $order->delete();

        return response()->json(['message' => 'Order deleted successfully']);
    }
}
