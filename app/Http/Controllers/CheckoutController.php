<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Event;
use App\Payments\PaymentGateway;
use App\Payments\PaymentGatewayException;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public const MAX_QUANTITY = 5;

    public function store(Request $request, Event $event, OrderService $orders, PaymentGateway $gateway): RedirectResponse
    {
        $data = $request->validate([
            'ticket_type_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.self::MAX_QUANTITY],
        ]);

        $ticketType = $event->ticketTypes()->findOrFail($data['ticket_type_id']);
        $order = $orders->reserve($request->user(), $ticketType, (int) $data['quantity']);

        try {
            $order->update(['snap_token' => $gateway->createCharge($order)]);
        } catch (PaymentGatewayException $e) {
            Log::error($e->getMessage(), ['order' => $order->code]);
            $orders->transition($order, OrderStatus::Cancelled);

            return back()->withErrors(['payment' => 'Pembayaran sedang tidak tersedia. Coba lagi nanti.']);
        }

        return redirect()->route('orders.show', $order);
    }
}
