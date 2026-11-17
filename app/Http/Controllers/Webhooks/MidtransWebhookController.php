<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentNotification;
use App\Payments\PaymentGateway;
use App\Services\OrderService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MidtransWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentGateway $gateway, OrderService $orders): JsonResponse
    {
        $update = $gateway->verifyWebhook($request->all());

        if ($update === null) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        if ($update->transactionId === null) {
            return response()->json(['message' => 'Missing transaction_id'], 422);
        }

        try {
            // Record and apply together: if applying fails, the record rolls back and the gateway retry is processed.
            DB::transaction(function () use ($request, $update, $orders) {
                PaymentNotification::create([
                    'order_id' => Order::where('code', $update->orderCode)->value('id'),
                    'transaction_id' => $update->transactionId,
                    'transaction_status' => $update->transactionStatus,
                    'payload' => $request->all(),
                ]);

                $orders->applyUpdate($update);
            });
        } catch (UniqueConstraintViolationException) {
            return response()->json(['message' => 'Duplicate ignored']);
        }

        return response()->json(['message' => 'OK']);
    }
}
