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

    public function qrSvg(int $size = 200): string
    {
        return (string) QrCode::format('svg')->size($size)->margin(1)->generate($this->code);
    }
}
