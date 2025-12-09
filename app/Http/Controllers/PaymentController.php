<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function process(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'payment_method' => 'required|string', // credit_card, paypal
            // 'amount' could be passed or taken from order total
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $order = Order::where('user_id', Auth::id())->find($request->order_id);

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        if ($order->status !== 'confirmed') {
            return response()->json(['error' => 'Payments can only be processed for confirmed orders'], 400);
        }

        // Check if fully paid? (Optional, but good practice. For now assuming multiple payments allowed or just one)

        try {
            $amount = $order->total_amount; // Charging the full amount
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
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
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

        $payments = $query->paginate(10);

        return response()->json($payments);
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
