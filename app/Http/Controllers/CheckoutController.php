<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Enums\IdentityType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Event;
use App\Models\Order;
use App\Models\TicketType;
use App\Payments\PaymentGateway;
use App\Payments\PaymentGatewayException;
use App\Rules\IndonesianPhone;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
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

        return redirect()->route('checkout.details', $order);
    }

    /** Step 2: buyer and per-ticket holder data, with the reservation countdown. Reference: 05-checkout-detail-pesanan.png */
    public function details(Request $request, Order $order, OrderService $orders): View|RedirectResponse
    {
        if ($redirect = $this->guardPending($request, $order, $orders)) {
            return $redirect;
        }

        $order->load('items.ticketType', 'event');

        return view('checkout.details', ['order' => $order, 'seats' => $order->seats(), 'user' => $request->user()]);
    }

    public function saveDetails(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        if ($redirect = $this->guardPending($request, $order, $orders)) {
            return $redirect;
        }

        $order->load('items.ticketType');
        $seatCount = count($order->seats());

        $data = $request->validate([
            ...$this->personRules('buyer', $request->input('buyer.id_type')),
            'attendees' => ['required', 'array', 'size:'.$seatCount],
            ...collect(range(0, $seatCount - 1))->flatMap(fn (int $i) => [
                ...$this->personRules("attendees.{$i}", $request->input("attendees.{$i}.id_type")),
                "attendees.{$i}.gender" => ['required', Rule::in(['L', 'P'])],
            ])->all(),
        ], [], $this->attributeNames($seatCount));

        $normalize = fn (array $person) => ['phone' => IndonesianPhone::normalize($person['phone'])] + $person;
        $buyer = $normalize($data['buyer']);

        $order->update([
            'buyer_name' => $buyer['name'],
            'buyer_id_type' => $buyer['id_type'],
            'buyer_id_number' => $buyer['id_number'],
            'buyer_email' => $buyer['email'],
            'buyer_phone' => $buyer['phone'],
            'attendees' => array_map($normalize, array_values($data['attendees'])),
        ]);

        return redirect()->route('checkout.payment', $order);
    }

    /** Step 3: choose the payment method group. No dedicated reference yet: follows the step 2 panels. */
    public function payment(Request $request, Order $order, OrderService $orders): View|RedirectResponse
    {
        if ($redirect = $this->guardPending($request, $order, $orders)) {
            return $redirect;
        }

        if (blank($order->buyer_name)) {
            return redirect()->route('checkout.details', $order);
        }

        $order->load('items.ticketType', 'event');

        return view('checkout.payment', ['order' => $order]);
    }

    public function savePayment(Request $request, Order $order, OrderService $orders, PaymentGateway $gateway): RedirectResponse
    {
        if ($redirect = $this->guardPending($request, $order, $orders)) {
            return $redirect;
        }

        // The Midtrans transaction is created once; the method is fixed after that (no second charge on refresh or back).
        if ($order->snap_token) {
            return redirect()->route('orders.show', $order);
        }

        $data = $request->validate(['payment_method' => ['required', Rule::enum(PaymentMethod::class)]], [], ['payment_method' => 'metode pembayaran']);
        $order->update(['payment_method' => $data['payment_method']]);

        try {
            $order->update(['snap_token' => $gateway->createCharge($order)]);
        } catch (PaymentGatewayException $e) {
            Log::error($e->getMessage(), ['order' => $order->code]);

            return back()->withErrors(['payment' => 'Pembayaran sedang tidak tersedia. Coba lagi sebentar lagi.']);
        }

        return redirect()->route('orders.show', $order);
    }

    /** "Back" from step 2: give the seats back and return to step 1. */
    public function cancel(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        abort_unless($request->user()->is($order->user), 403);

        $orders->transition($order, OrderStatus::Cancelled);

        return redirect()->route('checkout.tickets', $order->event);
    }

    /** Only the buyer may continue, and only while the reservation is still running. */
    private function guardPending(Request $request, Order $order, OrderService $orders): ?RedirectResponse
    {
        abort_unless($request->user()->is($order->user), 403);

        if ($order->status !== OrderStatus::Pending) {
            return redirect()->route('orders.show', $order);
        }

        if ($order->expires_at->isPast()) {
            $orders->transition($order, OrderStatus::Expired);

            return redirect()->route('checkout.tickets', $order->event)
                ->withErrors(['quantities' => 'Waktu pemesanan habis dan tiket sudah dilepas. Silakan pilih ulang.']);
        }

        return null;
    }

    private function personRules(string $prefix, ?string $idType): array
    {
        $type = IdentityType::tryFrom((string) $idType);

        return [
            "{$prefix}.name" => ['required', 'string', 'max:255'],
            "{$prefix}.id_type" => ['required', Rule::enum(IdentityType::class)],
            "{$prefix}.id_number" => ['required', 'string', 'max:20', ...($type ? [$type->numberRule()] : [])],
            "{$prefix}.email" => ['required', 'email', 'max:255'],
            "{$prefix}.phone" => ['required', 'string', new IndonesianPhone],
        ];
    }

    private function attributeNames(int $seatCount): array
    {
        $fields = ['name' => 'nama lengkap', 'id_type' => 'tipe identitas', 'id_number' => 'nomor identitas', 'email' => 'email', 'phone' => 'No. WhatsApp', 'gender' => 'jenis kelamin'];
        $names = [];
        foreach ($fields as $key => $label) {
            $names["buyer.{$key}"] = $label;
            foreach (range(0, $seatCount - 1) as $i) {
                $names["attendees.{$i}.{$key}"] = $label.' (Detail Pesanan '.($i + 1).')';
            }
        }

        return $names;
    }
}
