<?php

namespace App\Models;

use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    protected $fillable = [
        'booking_reference',
        'patient_id',
        'clinician_id',
        'service_id',
        'scheduled_for',
        'format',
        'location',
        'duration_minutes',
        'price_pence',
        'status',
        'source',
        'confirmed_at',
        'cancelled_at',
        'active_slot_key',
    ];

    protected function casts(): array
    {
        return ['scheduled_for' => 'datetime', 'confirmed_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saving(function (Appointment $appointment): void {
            $appointment->syncActiveSlotKey();
        });
    }

    public function syncActiveSlotKey(): void
    {
        if ($this->status === 'cancelled' || $this->clinician_id === null || $this->scheduled_for === null) {
            $this->active_slot_key = null;

            return;
        }

        $this->active_slot_key = $this->clinician_id.'|'.$this->scheduled_for->format('Y-m-d H:i:s');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function clinician(): BelongsTo
    {
        return $this->belongsTo(Clinician::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(BookingConsent::class);
    }

    public function careTasks(): HasMany
    {
        return $this->hasMany(CareTask::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(AppointmentReminder::class);
    }
}
