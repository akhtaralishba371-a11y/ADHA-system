<?php

namespace App\Models;

use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['public_id', 'first_name', 'last_name', 'email', 'phone', 'date_of_birth', 'address_line', 'city', 'postcode', 'pathway', 'status'];

    protected function casts(): array
    {
        return [
            'first_name' => 'encrypted',
            'last_name' => 'encrypted',
            'email' => 'encrypted',
            'phone' => 'encrypted',
            'date_of_birth' => 'encrypted',
            'address_line' => 'encrypted',
            'city' => 'encrypted',
            'postcode' => 'encrypted',
        ];
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function careTasks(): HasMany
    {
        return $this->hasMany(CareTask::class);
    }

    public function routeNotificationForMail(): string
    {
        return $this->email;
    }
}
