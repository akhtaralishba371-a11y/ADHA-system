<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class SettingsRepository
{
    public const APP_INITIALIZED = 'app.initialized';

    public const APP_NAME = 'app.name';

    public const APP_LOGO_PATH = 'app.logo_path';

    public const MAIL_ENABLED = 'mail.enabled';

    public const MAIL_DRIVER = 'mail.driver';

    public const MAIL_HOST = 'mail.host';

    public const MAIL_PORT = 'mail.port';

    public const MAIL_ENCRYPTION = 'mail.encryption';

    public const MAIL_USERNAME = 'mail.username';

    public const MAIL_PASSWORD = 'mail.password';

    public const MAIL_FROM_ADDRESS = 'mail.from_address';

    public const MAIL_FROM_NAME = 'mail.from_name';

    public const MAIL_REPLY_TO = 'mail.reply_to';

    /**
     * @var list<string>
     */
    public const MAIL_KEYS = [
        self::MAIL_ENABLED,
        self::MAIL_DRIVER,
        self::MAIL_HOST,
        self::MAIL_PORT,
        self::MAIL_ENCRYPTION,
        self::MAIL_USERNAME,
        self::MAIL_PASSWORD,
        self::MAIL_FROM_ADDRESS,
        self::MAIL_FROM_NAME,
        self::MAIL_REPLY_TO,
    ];

    public function get(string $key, mixed $default = null): mixed
    {
        $setting = Setting::query()->where('key', $key)->first();

        if ($setting === null) {
            return $default;
        }

        return $this->castValue($setting);
    }

    public function has(string $key): bool
    {
        return Setting::query()->where('key', $key)->exists();
    }

    public function isInitialized(): bool
    {
        return (bool) $this->get(self::APP_INITIALIZED, false);
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  array<string, bool>  $encryptedKeys
     */
    public function putMany(array $values, array $encryptedKeys = []): void
    {
        foreach ($values as $key => $value) {
            $this->put($key, $value, encrypted: $encryptedKeys[$key] ?? false);
        }
    }

    public function put(string $key, mixed $value, bool $encrypted = false, ?string $type = null): void
    {
        $resolvedType = $type ?? $this->inferType($value);
        $storeValue = $this->serializeValue($value, $resolvedType);

        if ($encrypted && $storeValue !== null && $storeValue !== '') {
            $storeValue = Crypt::encryptString((string) $storeValue);
        }

        Setting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $storeValue,
                'type' => $resolvedType,
                'is_encrypted' => $encrypted,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function mailSettings(): array
    {
        $settings = [];

        foreach (self::MAIL_KEYS as $key) {
            $settings[$key] = $this->get($key);
        }

        return $settings;
    }

    public function isSmtpEnabled(): bool
    {
        return (bool) $this->get(self::MAIL_ENABLED, false);
    }

    /**
     * True when Super Admin SMTP is enabled and fully configured for outbound mail.
     */
    public function hasActiveSmtpConfiguration(): bool
    {
        return $this->isSmtpEnabled()
            && filled($this->get(self::MAIL_HOST))
            && filled($this->get(self::MAIL_PORT))
            && filled($this->get(self::MAIL_FROM_ADDRESS))
            && filled($this->get(self::MAIL_FROM_NAME));
    }

    /**
     * @deprecated Use hasActiveSmtpConfiguration(); kept for existing call sites.
     */
    public function hasDatabaseMailConfiguration(): bool
    {
        return $this->hasActiveSmtpConfiguration();
    }

    /**
     * Public representation of mail settings — never includes secret plaintext.
     *
     * @return array<string, mixed>
     */
    public function mailSettingsForDisplay(): array
    {
        return [
            'enabled' => $this->isSmtpEnabled(),
            'driver' => $this->get(self::MAIL_DRIVER),
            'host' => $this->get(self::MAIL_HOST),
            'port' => $this->get(self::MAIL_PORT),
            'encryption' => $this->get(self::MAIL_ENCRYPTION),
            'username' => $this->get(self::MAIL_USERNAME),
            'has_password' => filled($this->get(self::MAIL_PASSWORD)),
            'from_address' => $this->get(self::MAIL_FROM_ADDRESS),
            'from_name' => $this->get(self::MAIL_FROM_NAME),
            'reply_to' => $this->get(self::MAIL_REPLY_TO),
        ];
    }

    private function castValue(Setting $setting): mixed
    {
        $raw = $setting->value;

        if ($setting->is_encrypted && filled($raw)) {
            try {
                $raw = Crypt::decryptString((string) $raw);
            } catch (Throwable) {
                return null;
            }
        }

        return match ($setting->type) {
            'boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            'integer' => $raw === null || $raw === '' ? null : (int) $raw,
            'json' => $raw === null || $raw === '' ? null : json_decode((string) $raw, true),
            default => $raw,
        };
    }

    private function serializeValue(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string) $value,
        };
    }

    private function inferType(mixed $value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_array($value) => 'json',
            default => 'string',
        };
    }
}
