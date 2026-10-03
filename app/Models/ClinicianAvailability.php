<?php

namespace App\Models;

use Database\Factories\ClinicianAvailabilityFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicianAvailability extends Model
{
    /** @use HasFactory<ClinicianAvailabilityFactory> */
    use HasFactory;

    protected $fillable = ['clinician_id', 'day_of_week', 'starts_at', 'ends_at', 'format', 'location', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected function startsAt(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): ?string => $this->formatClock($value),
            set: fn (mixed $value): mixed => $this->normalizeClock($value),
        );
    }

    protected function endsAt(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): ?string => $this->formatClock($value),
            set: fn (mixed $value): mixed => $this->normalizeClock($value),
        );
    }

    public function clinician(): BelongsTo
    {
        return $this->belongsTo(Clinician::class);
    }

    private function formatClock(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return substr((string) $value, 0, 5);
    }

    private function normalizeClock(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        return preg_match('/^\d{2}:\d{2}:\d{2}$/', $value) === 1
            ? substr($value, 0, 5)
            : $value;
    }
}
