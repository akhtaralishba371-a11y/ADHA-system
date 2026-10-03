<?php

namespace App\Models;

use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'short_name',
        'description',
        'slug',
        'pathway',
        'duration_minutes',
        'duration_label',
        'price_pence',
        'in_person_price_pence',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'duration_minutes' => 'integer',
            'price_pence' => 'integer',
            'in_person_price_pence' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function formatPricePence(?int $pence = null): string
    {
        $amount = $pence ?? $this->price_pence;
        $pounds = $amount / 100;

        return '£'.number_format($pounds, fmod($pounds, 1.0) === 0.0 ? 0 : 2);
    }

    public function durationDisplay(): string
    {
        return filled($this->duration_label)
            ? (string) $this->duration_label
            : $this->duration_minutes.' mins';
    }

    public function cardLabel(?bool $inPerson = false): string
    {
        $pence = ($inPerson && $this->in_person_price_pence)
            ? $this->in_person_price_pence
            : $this->price_pence;

        return 'From '.$this->formatPricePence($pence).' · '.$this->durationDisplay();
    }

    public function displayName(): string
    {
        return filled($this->short_name) ? (string) $this->short_name : $this->name;
    }
}
