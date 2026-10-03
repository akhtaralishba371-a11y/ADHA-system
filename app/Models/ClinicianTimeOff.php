<?php

namespace App\Models;

use Database\Factories\ClinicianTimeOffFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicianTimeOff extends Model
{
    /** @use HasFactory<ClinicianTimeOffFactory> */
    use HasFactory;

    protected $fillable = ['clinician_id', 'starts_at', 'ends_at', 'reason'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function clinician(): BelongsTo
    {
        return $this->belongsTo(Clinician::class);
    }
}
