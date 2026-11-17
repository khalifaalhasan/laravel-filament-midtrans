<?php

namespace App\Payments;

use App\Models\Order;

/** Provider-agnostic payment API. Business code depends on this, never on a provider SDK. */
interface PaymentGateway
{
    /** Start a payment for the order and return the token the browser uses to pay. */
    public function createCharge(Order $order): string;

    /** Parse a webhook payload; null when the signature is invalid. */
    public function verifyWebhook(array $payload): ?PaymentUpdate;

    /** Ask the provider for the current status; null when it has no transaction for this order yet. */
    public function getStatus(Order $order): ?PaymentUpdate;

    /** Stop the current attempt so its payment instructions can no longer be paid. No-op if the gateway never saw it. */
    public function cancelCharge(Order $order): void;

    /** Refund the full order amount. Throws PaymentGatewayException on failure. */
    public function refund(Order $order, string $reason): PaymentUpdate;
}
