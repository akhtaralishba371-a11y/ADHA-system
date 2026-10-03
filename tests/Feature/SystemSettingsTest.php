<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\AppointmentReminderNotification;
use App\Notifications\BookingConfirmation;
use App\Notifications\TestMailConfiguration;
use App\Services\RuntimeMailConfigurator;
use App\Support\SettingsRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SystemSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RuntimeMailConfigurator::flushBaseline();
    }

    public function test_super_admin_can_view_smtp_settings(): void
    {
        $user = User::factory()->superAdmin()->create();
        app(SettingsRepository::class)->putMany([
            SettingsRepository::MAIL_ENABLED => true,
            SettingsRepository::MAIL_HOST => 'smtp.example.test',
            SettingsRepository::MAIL_PORT => 587,
            SettingsRepository::MAIL_FROM_ADDRESS => 'care@example.test',
            SettingsRepository::MAIL_FROM_NAME => 'Jelly Care',
            SettingsRepository::MAIL_PASSWORD => 'hidden-secret',
        ], [
            SettingsRepository::MAIL_PASSWORD => true,
        ]);

        $this->actingAs($user)
            ->getJson(route('admin.settings.show'))
            ->assertOk()
            ->assertJsonPath('mail.enabled', true)
            ->assertJsonPath('mail.host', 'smtp.example.test')
            ->assertJsonPath('mail.has_password', true)
            ->assertJsonMissing(['password' => 'hidden-secret'])
            ->assertJsonMissing(['hidden-secret']);
    }

    public function test_super_admin_can_update_smtp_settings(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->putJson(route('admin.settings.update'), [
                'clinic_name' => 'Jelly Clinic',
                'mail' => [
                    'enabled' => true,
                    'host' => 'smtp.example.test',
                    'port' => 587,
                    'encryption' => 'tls',
                    'username' => 'mailer@example.test',
                    'password' => 'super-secret-smtp',
                    'from_address' => 'care@example.test',
                    'from_name' => 'Jelly Care',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('mail.enabled', true)
            ->assertJsonPath('mail.host', 'smtp.example.test')
            ->assertJsonPath('mail.has_password', true)
            ->assertJsonMissing(['password' => 'super-secret-smtp'])
            ->assertJsonMissing(['super-secret-smtp']);

        $this->assertTrue(app(SettingsRepository::class)->hasActiveSmtpConfiguration());
    }

    public function test_non_super_admin_roles_are_forbidden_from_system_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->getJson(route('admin.settings.show'))->assertForbidden();
        $this->actingAs($admin)->putJson(route('admin.settings.update'), [
            'clinic_name' => 'Nope',
        ])->assertForbidden();
        $this->actingAs($admin)->postJson(route('admin.settings.test-email'), [
            'email' => 'probe@example.test',
        ])->assertForbidden();
    }

    public function test_smtp_password_is_encrypted_in_storage_and_never_returned(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->putJson(route('admin.settings.update'), [
                'mail' => [
                    'enabled' => true,
                    'host' => 'smtp.example.test',
                    'port' => 587,
                    'encryption' => 'tls',
                    'username' => 'mailer@example.test',
                    'password' => 'super-secret-smtp',
                    'from_address' => 'care@example.test',
                    'from_name' => 'Jelly Care',
                ],
            ])
            ->assertOk()
            ->assertJsonMissing(['password' => 'super-secret-smtp'])
            ->assertJsonMissing(['super-secret-smtp']);

        $raw = Setting::query()->where('key', SettingsRepository::MAIL_PASSWORD)->value('value');
        $this->assertNotSame('super-secret-smtp', $raw);
        $this->assertSame('super-secret-smtp', Crypt::decryptString($raw));
    }

    public function test_blank_password_preserves_existing_encrypted_password(): void
    {
        $user = User::factory()->superAdmin()->create();
        $settings = app(SettingsRepository::class);
        $settings->putMany([
            SettingsRepository::MAIL_ENABLED => true,
            SettingsRepository::MAIL_HOST => 'smtp.example.test',
            SettingsRepository::MAIL_PORT => 587,
            SettingsRepository::MAIL_FROM_ADDRESS => 'care@example.test',
            SettingsRepository::MAIL_FROM_NAME => 'Jelly Care',
            SettingsRepository::MAIL_PASSWORD => 'original-secret',
        ], [
            SettingsRepository::MAIL_PASSWORD => true,
        ]);

        $this->actingAs($user)
            ->putJson(route('admin.settings.update'), [
                'mail' => [
                    'enabled' => true,
                    'host' => 'smtp.updated.test',
                    'port' => 465,
                    'encryption' => 'ssl',
                    'username' => 'mailer@example.test',
                    'password' => '',
                    'from_address' => 'care@example.test',
                    'from_name' => 'Jelly Care',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('mail.host', 'smtp.updated.test')
            ->assertJsonPath('mail.has_password', true);

        $this->assertSame('original-secret', $settings->get(SettingsRepository::MAIL_PASSWORD));
    }

    public function test_enabled_smtp_overrides_env_mail_configuration(): void
    {
        RuntimeMailConfigurator::flushBaseline();
        Config::set('mail.default', 'log');
        Config::set('mail.from.address', 'env@example.test');
        Config::set('mail.mailers.smtp.host', 'env-smtp.test');

        app(SettingsRepository::class)->putMany([
            SettingsRepository::MAIL_ENABLED => true,
            SettingsRepository::MAIL_HOST => 'db-smtp.test',
            SettingsRepository::MAIL_PORT => 2525,
            SettingsRepository::MAIL_ENCRYPTION => 'tls',
            SettingsRepository::MAIL_USERNAME => 'db-user',
            SettingsRepository::MAIL_PASSWORD => 'db-pass',
            SettingsRepository::MAIL_FROM_ADDRESS => 'db-mail@example.test',
            SettingsRepository::MAIL_FROM_NAME => 'DB Mailer',
        ], [
            SettingsRepository::MAIL_PASSWORD => true,
        ]);

        app(RuntimeMailConfigurator::class)->apply();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('db-smtp.test', config('mail.mailers.smtp.host'));
        $this->assertSame(2525, config('mail.mailers.smtp.port'));
        $this->assertSame('db-user', config('mail.mailers.smtp.username'));
        $this->assertSame('db-pass', config('mail.mailers.smtp.password'));
        $this->assertSame('db-mail@example.test', config('mail.from.address'));
        $this->assertSame('DB Mailer', config('mail.from.name'));
    }

    public function test_disabled_smtp_falls_back_to_env_mail_configuration(): void
    {
        RuntimeMailConfigurator::flushBaseline();
        Config::set('mail.default', 'log');
        Config::set('mail.from.address', 'env@example.test');
        Config::set('mail.from.name', 'Env Mailer');
        Config::set('mail.mailers.smtp.host', 'env-smtp.test');
        Config::set('mail.mailers.smtp.port', 1025);
        Config::set('mail.mailers.smtp.username', 'env-user');
        Config::set('mail.mailers.smtp.password', 'env-pass');
        Config::set('mail.mailers.smtp.scheme', null);
        Config::set('mail.mailers.smtp.auto_tls', true);

        $settings = app(SettingsRepository::class);
        $settings->putMany([
            SettingsRepository::MAIL_ENABLED => true,
            SettingsRepository::MAIL_HOST => 'db-smtp.test',
            SettingsRepository::MAIL_PORT => 2525,
            SettingsRepository::MAIL_ENCRYPTION => 'tls',
            SettingsRepository::MAIL_FROM_ADDRESS => 'db-mail@example.test',
            SettingsRepository::MAIL_FROM_NAME => 'DB Mailer',
        ]);

        $configurator = app(RuntimeMailConfigurator::class);
        $configurator->apply();
        $this->assertSame('db-mail@example.test', config('mail.from.address'));

        $settings->put(SettingsRepository::MAIL_ENABLED, false, type: 'boolean');
        $configurator->apply();

        $this->assertSame('log', config('mail.default'));
        $this->assertSame('env@example.test', config('mail.from.address'));
        $this->assertSame('Env Mailer', config('mail.from.name'));
        $this->assertSame('env-smtp.test', config('mail.mailers.smtp.host'));
        $this->assertSame(1025, (int) config('mail.mailers.smtp.port'));
        $this->assertSame('env-user', config('mail.mailers.smtp.username'));
        $this->assertSame('env-pass', config('mail.mailers.smtp.password'));
    }

    public function test_tls_encryption_maps_to_smtp_with_starttls(): void
    {
        $this->seedActiveSmtp(['encryption' => 'tls', 'port' => 587]);
        app(RuntimeMailConfigurator::class)->apply();

        $this->assertSame('smtp', config('mail.mailers.smtp.scheme'));
        $this->assertTrue((bool) config('mail.mailers.smtp.auto_tls'));
    }

    public function test_ssl_encryption_maps_to_smtps(): void
    {
        $this->seedActiveSmtp(['encryption' => 'ssl', 'port' => 465]);
        app(RuntimeMailConfigurator::class)->apply();

        $this->assertSame('smtps', config('mail.mailers.smtp.scheme'));
    }

    public function test_none_encryption_maps_to_smtp_without_automatic_tls(): void
    {
        $this->seedActiveSmtp(['encryption' => null, 'port' => 25]);
        app(RuntimeMailConfigurator::class)->apply();

        $this->assertSame('smtp', config('mail.mailers.smtp.scheme'));
        $this->assertFalse((bool) config('mail.mailers.smtp.auto_tls'));
    }

    public function test_test_email_success_uses_saved_smtp_configuration(): void
    {
        Notification::fake();
        $user = User::factory()->superAdmin()->create();
        $this->seedActiveSmtp();

        $this->actingAs($user)
            ->postJson(route('admin.settings.test-email'), ['email' => 'probe@example.test'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Test email sent successfully.');

        Notification::assertSentOnDemand(TestMailConfiguration::class);
    }

    public function test_test_email_failure_is_sanitized_for_users_and_logs(): void
    {
        $user = User::factory()->superAdmin()->create();
        $this->seedActiveSmtp();

        $warningContext = null;
        Event::listen(MessageLogged::class, function (MessageLogged $event) use (&$warningContext): void {
            if ($event->level === 'warning' && $event->message === 'System test email failed.') {
                $warningContext = $event->context;
            }
        });

        Event::listen(MessageSending::class, function (): void {
            throw new \RuntimeException('SMTP auth failed password=super-secret-smtp', 535);
        });

        $this->actingAs($user)
            ->postJson(route('admin.settings.test-email'), ['email' => 'probe@example.test'])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unable to send the test email. Check your SMTP settings and try again.')
            ->assertJsonMissing(['super-secret-smtp'])
            ->assertJsonMissing(['password=super-secret-smtp']);

        $this->assertIsArray($warningContext);
        $this->assertSame(\RuntimeException::class, $warningContext['exception'] ?? null);
        $this->assertSame(535, $warningContext['code'] ?? null);
        $this->assertArrayNotHasKey('message', $warningContext);
        $this->assertStringNotContainsString('super-secret-smtp', json_encode($warningContext));
    }

    public function test_booking_confirmation_notification_remains_queued(): void
    {
        $this->assertTrue(is_subclass_of(BookingConfirmation::class, ShouldQueue::class));
    }

    public function test_appointment_reminder_notification_remains_queued(): void
    {
        $this->assertTrue(is_subclass_of(AppointmentReminderNotification::class, ShouldQueue::class));
    }

    public function test_queue_worker_receives_current_smtp_configuration_before_sending(): void
    {
        RuntimeMailConfigurator::flushBaseline();
        Config::set('mail.from.address', 'env@example.test');
        Config::set('mail.mailers.smtp.host', 'env-smtp.test');

        $settings = app(SettingsRepository::class);
        $settings->putMany([
            SettingsRepository::MAIL_ENABLED => true,
            SettingsRepository::MAIL_HOST => 'first-smtp.test',
            SettingsRepository::MAIL_PORT => 587,
            SettingsRepository::MAIL_ENCRYPTION => 'tls',
            SettingsRepository::MAIL_FROM_ADDRESS => 'first@example.test',
            SettingsRepository::MAIL_FROM_NAME => 'First',
        ]);

        $configurator = app(RuntimeMailConfigurator::class);
        $configurator->apply();
        $this->assertSame('first-smtp.test', config('mail.mailers.smtp.host'));
        $this->assertSame('first@example.test', config('mail.from.address'));

        $settings->put(SettingsRepository::MAIL_HOST, 'second-smtp.test');
        $settings->put(SettingsRepository::MAIL_FROM_ADDRESS, 'second@example.test');

        // Simulate a long-running worker sending another notification.
        $configurator->apply();
        $this->assertSame('second-smtp.test', config('mail.mailers.smtp.host'));
        $this->assertSame('second@example.test', config('mail.from.address'));
    }

    public function test_appointment_reminder_command_queues_reminder_notification(): void
    {
        Notification::fake();

        $appointment = Appointment::factory()->create([
            'status' => 'confirmed',
            'scheduled_for' => now()->addDays(2),
        ]);

        $this->artisan('app:send-appointment-reminders')->assertSuccessful();

        Notification::assertSentTo($appointment->patient, AppointmentReminderNotification::class);
    }

    public function test_super_admin_can_update_account_without_changing_role(): void
    {
        $user = User::factory()->superAdmin()->create([
            'email' => 'owner@example.test',
            'password' => 'password',
        ]);

        $this->actingAs($user)
            ->putJson(route('admin.account.update'), [
                'name' => 'Owner Updated',
                'email' => 'owner-new@example.test',
            ])
            ->assertOk()
            ->assertJsonPath('email', 'owner-new@example.test')
            ->assertJsonPath('role', 'super_admin');

        $this->assertTrue($user->fresh()->isSuperAdmin());
    }

    public function test_existing_authentication_still_works_for_admin_panel(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.test',
            'password' => 'password',
        ]);

        $this->post(route('staff.login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.panel'));
    }

    /**
     * @param  array{encryption?: ?string, port?: int}  $overrides
     */
    private function seedActiveSmtp(array $overrides = []): void
    {
        app(SettingsRepository::class)->putMany([
            SettingsRepository::MAIL_ENABLED => true,
            SettingsRepository::MAIL_DRIVER => 'smtp',
            SettingsRepository::MAIL_HOST => 'smtp.example.test',
            SettingsRepository::MAIL_PORT => $overrides['port'] ?? 587,
            SettingsRepository::MAIL_ENCRYPTION => array_key_exists('encryption', $overrides)
                ? $overrides['encryption']
                : 'tls',
            SettingsRepository::MAIL_USERNAME => 'mailer@example.test',
            SettingsRepository::MAIL_PASSWORD => 'secret',
            SettingsRepository::MAIL_FROM_ADDRESS => 'care@example.test',
            SettingsRepository::MAIL_FROM_NAME => 'Jelly Care',
        ], [
            SettingsRepository::MAIL_PASSWORD => true,
        ]);
    }
}
