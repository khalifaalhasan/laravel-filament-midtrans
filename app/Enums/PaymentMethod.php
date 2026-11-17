<?php

namespace App\Enums;

/** Payment method groups offered at checkout step 3. Gateway adapters map these to provider codes. */
enum PaymentMethod: string
{
    case VirtualAccount = 'virtual-account';
    case Qris = 'qris';
    case EWallet = 'e-wallet';
    case Card = 'card';

    public function label(): string
    {
        return match ($this) {
            self::VirtualAccount => 'Virtual Account',
            self::Qris => 'QRIS',
            self::EWallet => 'E-Wallet',
            self::Card => 'Kartu Kredit / Debit',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::VirtualAccount => 'Transfer lewat ATM, m-banking, atau internet banking.',
            self::Qris => 'Scan QR dengan aplikasi bank atau e-wallet apa pun.',
            self::EWallet => 'Bayar langsung dari aplikasi e-wallet.',
            self::Card => 'Visa, Mastercard, JCB.',
        };
    }

    /** Short names shown as chips under each option. */
    public function brands(): array
    {
        return match ($this) {
            self::VirtualAccount => ['BCA', 'BNI', 'BRI', 'Mandiri', 'Permata'],
            self::Qris => ['QRIS'],
            self::EWallet => ['GoPay', 'ShopeePay'],
            self::Card => ['Visa', 'Mastercard', 'JCB'],
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::VirtualAccount => 'heroicon-s-building-library',
            self::Qris => 'heroicon-s-qr-code',
            self::EWallet => 'heroicon-s-device-phone-mobile',
            self::Card => 'heroicon-s-credit-card',
        };
    }
}
