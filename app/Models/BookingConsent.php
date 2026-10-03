<?php

namespace App\Models;

use Database\Factories\BookingConsentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingConsent extends Model
{
    /** @use HasFactory<BookingConsentFactory> */
    use HasFactory;

    protected $fillable = ['appointment_id', 'consent_type', 'accepted_at'];

    protected function casts(): array
    {
        return ['accepted_at' => 'datetime'];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
