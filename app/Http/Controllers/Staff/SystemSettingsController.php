<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendTestEmailRequest;
use App\Http\Requests\UpdateSystemSettingsRequest;
use App\Notifications\TestMailConfiguration;
use App\Services\RuntimeMailConfigurator;
use App\Support\SettingsRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SystemSettingsController extends Controller
{
    public function show(SettingsRepository $settings): JsonResponse
    {
        return response()->json([
            'general' => [
                'clinic_name' => $settings->get(SettingsRepository::APP_NAME),
                'logo_path' => $settings->get(SettingsRepository::APP_LOGO_PATH),
                'initialized' => $settings->isInitialized(),
            ],
            'mail' => $settings->mailSettingsForDisplay(),
        ]);
    }

    public function update(
        UpdateSystemSettingsRequest $request,
        SettingsRepository $settings,
        RuntimeMailConfigurator $mailConfigurator,
    ): JsonResponse {
        $data = $request->validated();

        if (array_key_exists('clinic_name', $data)) {
            $settings->put(SettingsRepository::APP_NAME, $data['clinic_name']);
        }

        if (array_key_exists('logo_path', $data)) {
            $settings->put(SettingsRepository::APP_LOGO_PATH, $data['logo_path']);
        }

        if (isset($data['mail']) && is_array($data['mail'])) {
            $mail = $data['mail'];

            if (array_key_exists('enabled', $mail)) {
                $settings->put(SettingsRepository::MAIL_ENABLED, (bool) $mail['enabled'], type: 'boolean');
            }

            $map = [
                'host' => SettingsRepository::MAIL_HOST,
                'port' => SettingsRepository::MAIL_PORT,
                'encryption' => SettingsRepository::MAIL_ENCRYPTION,
                'username' => SettingsRepository::MAIL_USERNAME,
                'from_address' => SettingsRepository::MAIL_FROM_ADDRESS,
                'from_name' => SettingsRepository::MAIL_FROM_NAME,
                'reply_to' => SettingsRepository::MAIL_REPLY_TO,
            ];

            foreach ($map as $input => $key) {
                if (array_key_exists($input, $mail)) {
                    $value = $mail[$input];
                    $settings->put($key, $value, encrypted: false, type: $input === 'port' ? 'integer' : null);
                }
            }

            if (($mail['enabled'] ?? $settings->isSmtpEnabled()) === true) {
                $settings->put(SettingsRepository::MAIL_DRIVER, 'smtp');
            }

            if (array_key_exists('password', $mail) && filled($mail['password'])) {
                $settings->put(SettingsRepository::MAIL_PASSWORD, $mail['password'], encrypted: true);
            }
        }

        $mailConfigurator->purgeMailers();
        $mailConfigurator->apply();

        return response()->json([
            'general' => [
                'clinic_name' => $settings->get(SettingsRepository::APP_NAME),
                'logo_path' => $settings->get(SettingsRepository::APP_LOGO_PATH),
                'initialized' => $settings->isInitialized(),
            ],
            'mail' => $settings->mailSettingsForDisplay(),
        ]);
    }

    public function sendTest(
        SendTestEmailRequest $request,
        RuntimeMailConfigurator $mailConfigurator,
        SettingsRepository $settings,
    ): JsonResponse {
        if (! $settings->hasActiveSmtpConfiguration()) {
            return response()->json([
                'success' => false,
                'message' => 'Enable SMTP and save a valid host, port, from email, and from name before sending a test email.',
            ], 422);
        }

        $mailConfigurator->apply();

        try {
            Notification::route('mail', $request->string('email')->toString())
                ->notifyNow(new TestMailConfiguration);

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully.',
            ]);
        } catch (Throwable $exception) {
            Log::warning('System test email failed.', [
                'exception' => $exception::class,
                'code' => $exception->getCode(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to send the test email. Check your SMTP settings and try again.',
            ], 422);
        }
    }
}
