<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Enums\OrderStatus;
use App\Models\Event;
use App\Models\Order;
use App\Models\TicketType;
use App\Payments\PaymentGateway;
use App\Payments\PaymentGatewayException;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    /** Step 1: pick ticket categories. Reference: docs/design/reference/04-checkout-pilih-tiket.png */
    public function tickets(Event $event): View
    {
        abort_unless($event->status === EventStatus::Published, 404);

        // On-sale categories first, sold-out / closed ones after, each in admin order.
        $ticketTypes = $event->ticketTypes()->orderBy('id')->get()
            ->sortBy(fn (TicketType $t) => $t->isOnSale() && $t->availableUnits() > 0 ? 0 : 1)
            ->values();

        return view('checkout.tickets', compact('event', 'ticketTypes'));
    }

    public function store(Request $request, Event $event, OrderService $orders, PaymentGateway $gateway): RedirectResponse
    {
        $data = $request->validate([
            'quantities' => ['required', 'array'],
            'quantities.*' => ['integer', 'min:0', 'max:'.Order::MAX_TICKETS],
        ]);

        // Only this event's ticket types may be ordered from this event's page.
        abort_if(array_diff(array_keys($data['quantities']), $event->ticketTypes()->pluck('id')->all()) !== [], 404);

        $order = $orders->reserve($request->user(), $data['quantities']);

        // shortcut: charges right after step 1 until steps 2-3 (#86, #87) collect buyer data and the payment method.
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
