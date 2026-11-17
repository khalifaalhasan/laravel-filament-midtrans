<?php

namespace App\Payments;

use App\Enums\OrderStatus;

/** A trusted status reported by the gateway (verified webhook or server-side API call). */
final readonly class PaymentUpdate
{
    public function __construct(
        public string $orderCode,
        public OrderStatus $status,
        public int $grossAmount,
        public ?string $transactionId = null,
        public ?string $transactionStatus = null,
        public ?string $paymentType = null,
    ) {}
}
