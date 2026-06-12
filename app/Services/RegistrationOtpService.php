<?php

namespace App\Services;

use App\Notifications\RegistrationOtpNotification;
use Illuminate\Support\Facades\Notification;

class RegistrationOtpService
{
    private const SESSION_PENDING = 'registration.pending';

    private const SESSION_OTP_HASH = 'registration.otp_hash';

    private const SESSION_OTP_EXPIRES = 'registration.otp_expires_at';

    private const TTL_MINUTES = 10;

    /**
     * @param  array{name: string, company: string, email: string, phone: string, password: string}  $data
     */
    public function storePending(array $data): void
    {
        session([self::SESSION_PENDING => $data]);
        $this->sendOtp($data['email']);
    }

    public function resend(): bool
    {
        $pending = $this->pending();

        if ($pending === null) {
            return false;
        }

        $this->sendOtp($pending['email']);

        return true;
    }

    public function verify(string $otp): bool
    {
        $hash = session(self::SESSION_OTP_HASH);
        $expires = session(self::SESSION_OTP_EXPIRES);

        if (! is_string($hash) || ! is_int($expires) || now()->timestamp > $expires) {
            return false;
        }

        return hash_equals($hash, hash('sha256', trim($otp)));
    }

    /**
     * @return array{name: string, company: string, email: string, phone: string, password: string}|null
     */
    public function pending(): ?array
    {
        $pending = session(self::SESSION_PENDING);

        return is_array($pending) ? $pending : null;
    }

    public function clear(): void
    {
        session()->forget([
            self::SESSION_PENDING,
            self::SESSION_OTP_HASH,
            self::SESSION_OTP_EXPIRES,
        ]);
    }

    public static function maskEmail(string $email): string
    {
        if (! str_contains($email, '@')) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);
        $visible = mb_substr($local, 0, 1);
        $maskedLocal = strlen($local) > 1 ? $visible.str_repeat('*', min(3, strlen($local) - 1)) : $visible.'*';

        return $maskedLocal.'@'.$domain;
    }

    private function sendOtp(string $email): void
    {
        $otp = (string) random_int(100000, 999999);

        session([
            self::SESSION_OTP_HASH => hash('sha256', $otp),
            self::SESSION_OTP_EXPIRES => now()->addMinutes(self::TTL_MINUTES)->timestamp,
        ]);

        Notification::route('mail', $email)->notify(new RegistrationOtpNotification($otp));
    }
}
