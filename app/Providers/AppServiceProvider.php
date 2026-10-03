<?php

namespace App\Providers;

use App\Listeners\ApplyRuntimeMailConfiguration;
use App\Services\RuntimeMailConfigurator;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('staff-login', function (Request $request): Limit {
            return Limit::perMinute(5)->by(Str::lower($request->string('email')->toString()).'|'.$request->ip());
        });

        RateLimiter::for('public-booking', function (Request $request): Limit {
            return Limit::perHour(10)->by(Str::lower($request->string('patient.email')->toString()).'|'.$request->ip());
        });

        RateLimiter::for('public-availability', function (Request $request): Limit {
            return Limit::perMinute(120)->by($request->ip());
        });

        RateLimiter::for('setup', function (Request $request): Limit {
            return Limit::perMinute(5)->by($request->ip());
        });

        $this->app->make(RuntimeMailConfigurator::class)->rememberBaseline();

        Event::listen(NotificationSending::class, [ApplyRuntimeMailConfiguration::class, 'handleNotificationSending']);
        Event::listen(MessageSending::class, [ApplyRuntimeMailConfiguration::class, 'handleMessageSending']);
    }
}
