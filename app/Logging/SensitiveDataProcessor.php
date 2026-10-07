<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Redacts sensitive data from log records before they are written.
 *
 * Context/extra keys matching the configured sensitive keys (see
 * config/data-protection.php) are replaced with a placeholder, and email
 * addresses found inside log messages are masked. This prevents PII such as
 * passwords, NINs, transaction PINs, phone numbers and emails from leaking
 * into log files or external channels such as Slack.
 */
class SensitiveDataProcessor implements ProcessorInterface
{
    private const DEFAULT_REPLACEMENT = '[REDACTED]';

    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            message: $this->redactText($record->message),
            context: $this->redactArray($record->context),
            extra: $this->redactArray($record->extra),
        );
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    private function redactArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->redactArray($value);
            } elseif ($this->isSensitiveKey((string) $key)) {
                $data[$key] = $this->replacement();
            } elseif (is_string($value)) {
                $data[$key] = $this->redactText($value);
            }
        }

        return $data;
    }

    private function redactText(string $value): string
    {
        return (string) preg_replace(
            '/[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}/',
            $this->replacement(),
            $value
        );
    }

    /**
     * Match a key as a whole segment so short tokens (e.g. "pin") do not
     * redact unrelated keys such as "shipping".
     */
    private function isSensitiveKey(string $key): bool
    {
        $key = strtolower($key);

        foreach ($this->keys() as $sensitive) {
            if ($sensitive === '') {
                continue;
            }

            if (preg_match('/(^|[^a-z0-9])'.preg_quote($sensitive, '/').'([^a-z0-9]|$)/', $key) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function keys(): array
    {
        return array_values(array_map(
            'strtolower',
            array_filter((array) config('data-protection.redaction.keys', []), 'is_string')
        ));
    }

    private function replacement(): string
    {
        return (string) config('data-protection.redaction.replacement', self::DEFAULT_REPLACEMENT);
    }
}
