<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\PaymentGatewayFactory;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class PaymentController extends Controller
{
    public function __construct(protected PaymentService $paymentService)
    {
    }

    public function process(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer|exists:orders,id',
            'payment_method' => 'required|string|in:' . implode(',', PaymentGatewayFactory::supported()),
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            // Lock the order row so concurrent requests for the same order are serialized.
            return DB::transaction(function () use ($request) {
                $order = Order::where('user_id', Auth::id())->lockForUpdate()->find($request->order_id);

                if (!$order) {
                    return response()->json(['error' => 'Order not found'], 404);
                }

                if ($order->status !== Order::STATUS_CONFIRMED) {
                    return response()->json(['error' => 'Payments can only be processed for confirmed orders'], 400);
                }

                if ($order->payments()->whereIn('status', ['successful', 'pending'])->exists()) {
                    return response()->json(['error' => 'Order is already paid or has a payment in progress'], 409);
                }

                $amount = (float) $order->total_amount; // Charging the full amount
                $details = $request->except(['order_id', 'payment_method']);

                $result = $this->paymentService->processPayment($request->payment_method, $amount, $details);

                $payment = Payment::create([
                    'order_id' => $order->id,
                    'transaction_id' => $result['transaction_id'] ?? null,
                    'payment_method' => $request->payment_method,
                    'status' => $result['status'], // pending, successful, failed
                    'amount' => $amount,
                    'currency' => 'USD',
                ]);

                return response()->json($payment, 201);
            });
        } catch (Throwable $e) {
            Log::error('Payment processing failed', ['order_id' => $request->order_id, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Payment processing failed'], 500);
        }
    }

    public function index(Request $request)
    {
        $query = Payment::whereHas('order', function ($q) {
            $q->where('user_id', Auth::id());
        });

        if ($request->has('order_id')) {
            $query->where('order_id', $request->order_id);
        }

        return response()->json($query->paginate(10));
    }

    public function show($id)
    {
        $payment = Payment::whereHas('order', function ($q) {
            $q->where('user_id', Auth::id());
        })->find($id);

        if (!$payment) {
            return response()->json(['error' => 'Payment not found'], 404);
        }

        return response()->json($payment);
    }
}
