<?php

namespace App\Models;

use Database\Factories\ClinicianFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Clinician extends Model
{
    /** @use HasFactory<ClinicianFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'public_id', 'display_name', 'professional_title', 'specialty', 'location', 'years_experience', 'bio', 'photo_path', 'is_active', 'is_public'];

    protected $hidden = ['photo_path'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_public' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Clinician $clinician): void {
            $clinician->public_id ??= (string) Str::uuid();
        });
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    #[Scope]
    protected function publicDirectory(Builder $query): Builder
    {
        return $query->active()->where('is_public', true);
    }

    protected function photoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->photo_path === null
            ? null
            : Storage::disk('public')->url($this->photo_path));
    }

    protected function initials(): Attribute
    {
        return Attribute::get(fn (): string => Str::of($this->display_name)
            ->replaceMatches('/^Dr\.?\s+/i', '')
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
            ->implode(''));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(ClinicianAvailability::class);
    }

    public function timeOff(): HasMany
    {
        return $this->hasMany(ClinicianTimeOff::class);
    }
}
