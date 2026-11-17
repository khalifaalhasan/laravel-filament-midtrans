<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class Ticket extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['checked_in_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** Identity numbers are personal data: staff screens only show the last 4 characters. */
    public function maskedIdNumber(): ?string
    {
        return $this->holder_id_number ? str_repeat('•', max(0, strlen($this->holder_id_number) - 4)).substr($this->holder_id_number, -4) : null;
    }

    public function qrSvg(int $size = 200): string
    {
        return (string) QrCode::format('svg')->size($size)->margin(1)->generate($this->code);
    }
}
