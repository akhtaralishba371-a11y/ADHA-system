<?php

namespace App\Services;

use App\Support\SettingsRepository;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Throwable;

class RuntimeMailConfigurator
{
    /**
     * Process-level snapshot of .env-backed mail config before any database override.
     *
     * @var array{default: mixed, from: mixed, reply_to: mixed, smtp: mixed}|null
     */
    private static ?array $baseline = null;

    public function __construct(private SettingsRepository $settings) {}

    /**
     * Capture the current mail configuration as the .env fallback baseline (once per process).
     */
    public function rememberBaseline(): void
    {
        if (self::$baseline !== null) {
            return;
        }

        self::$baseline = [
            'default' => config('mail.default'),
            'from' => config('mail.from'),
            'reply_to' => config('mail.reply_to'),
            'smtp' => config('mail.mailers.smtp'),
        ];
    }

    /**
     * Reset the baseline so the next remember/apply captures fresh config (tests only).
     */
    public static function flushBaseline(): void
    {
        self::$baseline = null;
    }

    /**
     * Apply database SMTP when enabled and complete; otherwise restore the .env baseline.
     */
    public function apply(): void
    {
        $this->rememberBaseline();

        try {
            if ($this->settings->hasActiveSmtpConfiguration()) {
                $this->applyDatabaseSmtp();
            } else {
                $this->restoreBaseline();
            }

            $this->purgeMailers();
        } catch (Throwable) {
            // Leave existing config in place if settings cannot be applied.
        }
    }

    public function purgeMailers(): void
    {
        foreach (array_keys(config('mail.mailers', [])) as $mailer) {
            Mail::purge($mailer);
        }
    }

    private function applyDatabaseSmtp(): void
    {
        $encryption = $this->normalizeEncryption($this->settings->get(SettingsRepository::MAIL_ENCRYPTION));
        [$scheme, $autoTls] = $this->schemeAndAutoTls($encryption);

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.transport', 'smtp');
        Config::set('mail.mailers.smtp.url', null);
        Config::set('mail.mailers.smtp.host', $this->settings->get(SettingsRepository::MAIL_HOST));
        Config::set('mail.mailers.smtp.port', (int) $this->settings->get(SettingsRepository::MAIL_PORT));
        Config::set('mail.mailers.smtp.username', $this->settings->get(SettingsRepository::MAIL_USERNAME));
        Config::set('mail.mailers.smtp.password', $this->settings->get(SettingsRepository::MAIL_PASSWORD));
        Config::set('mail.mailers.smtp.scheme', $scheme);
        Config::set('mail.mailers.smtp.auto_tls', $autoTls);

        $fromAddress = $this->settings->get(SettingsRepository::MAIL_FROM_ADDRESS);
        $fromName = $this->settings->get(SettingsRepository::MAIL_FROM_NAME);

        Config::set('mail.from.address', $fromAddress);
        Config::set('mail.from.name', filled($fromName) ? $fromName : config('app.name'));

        $replyTo = $this->settings->get(SettingsRepository::MAIL_REPLY_TO);

        if (filled($replyTo)) {
            Config::set('mail.reply_to.address', $replyTo);
            Config::set('mail.reply_to.name', filled($fromName) ? $fromName : config('app.name'));
        }
    }

    private function restoreBaseline(): void
    {
        if (self::$baseline === null) {
            return;
        }

        Config::set('mail.default', self::$baseline['default']);
        Config::set('mail.from', self::$baseline['from']);
        Config::set('mail.reply_to', self::$baseline['reply_to']);
        Config::set('mail.mailers.smtp', self::$baseline['smtp']);
    }

    private function normalizeEncryption(mixed $encryption): ?string
    {
        if (! is_string($encryption)) {
            return null;
        }

        $value = strtolower(trim($encryption));

        if ($value === '' || $value === 'null' || $value === 'none') {
            return null;
        }

        return $value;
    }

    /**
     * Map stored encryption to Symfony SMTP scheme options.
     *
     * TLS → SMTP + STARTTLS (smtp, auto_tls on)
     * SSL → SMTPS (smtps)
     * None → SMTP without automatic TLS (smtp, auto_tls off)
     *
     * @return array{0: string, 1: bool}
     */
    private function schemeAndAutoTls(?string $encryption): array
    {
        return match ($encryption) {
            'ssl' => ['smtps', true],
            'tls' => ['smtp', true],
            default => ['smtp', false],
        };
    }
}
