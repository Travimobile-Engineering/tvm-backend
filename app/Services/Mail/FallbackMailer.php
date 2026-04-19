<?php

namespace App\Services\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class FallbackMailer
{
    /**
     * Ordered list of mailer names to try (from config/mail.php mailers key).
     * Pulled from MAIL_FALLBACK_ORDER env, e.g. "ses,mailgun,smtp"
     */
    protected array $mailers;

    public function __construct()
    {
        $order = config('mail.fallback_order', []);

        // Support both array (from config) and comma-separated string (from env)
        $this->mailers = is_array($order)
            ? $order
            : array_filter(array_map('trim', explode(',', $order)));
    }

    /**
     * Send a Mailable through the first available provider.
     * Silently fails if all providers are down.
     *
     * @return bool true if sent successfully, false if all providers failed
     */
    public function send(string|array $to, Mailable $mailable): bool
    {
        if (empty($this->mailers)) {
            Log::error('[FallbackMailer] No mailers configured in mail.fallback_order.');

            return false;
        }

        foreach ($this->mailers as $mailerName) {
            try {
                Mail::mailer($mailerName)->to($to)->send(clone $mailable);

                Log::info("[FallbackMailer] Mail sent successfully via '{$mailerName}'.", [
                    'to' => $to,
                    'subject' => $this->resolveSubject($mailable),
                ]);

                return true;

            } catch (Throwable $e) {
                Log::warning("[FallbackMailer] Mailer '{$mailerName}' failed. Trying next.", [
                    'error' => $e->getMessage(),
                    'to' => $to,
                    'subject' => $this->resolveSubject($mailable),
                ]);
            }
        }

        // All providers exhausted — fail silently but log it
        Log::error('[FallbackMailer] All mail providers failed. Email was NOT delivered.', [
            'to' => $to,
            'subject' => $this->resolveSubject($mailable),
            'tried' => $this->mailers,
        ]);

        return false;
    }

    /**
     * Queue the mailable through the first available provider.
     * Uses the same fallback logic but dispatches to the queue.
     */
    public function queue(string|array $to, Mailable $mailable): bool
    {
        foreach ($this->mailers as $mailerName) {
            try {
                Mail::mailer($mailerName)->to($to)->queue(clone $mailable);

                Log::info("[FallbackMailer] Mail queued via '{$mailerName}'.", [
                    'to' => $to,
                    'subject' => $this->resolveSubject($mailable),
                ]);

                return true;

            } catch (Throwable $e) {
                Log::warning("[FallbackMailer] Mailer '{$mailerName}' failed to queue. Trying next.", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::error('[FallbackMailer] All mail providers failed to queue. Email was NOT queued.', [
            'to' => $to,
            'tried' => $this->mailers,
        ]);

        return false;
    }

    /**
     * Return the list of configured mailers (useful for health checks).
     */
    public function getMailers(): array
    {
        return $this->mailers;
    }

    private function resolveSubject(Mailable $mailable): string
    {
        return $mailable->subject ?? class_basename($mailable);
    }
}
