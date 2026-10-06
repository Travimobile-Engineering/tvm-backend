<?php

namespace Tests\Feature;

use App\Logging\RedactSensitiveData;
use App\Logging\SensitiveDataProcessor;
use Illuminate\Log\Logger;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger as MonologLogger;
use Monolog\LogRecord;
use Tests\TestCase;

class LogRedactionTest extends TestCase
{
    private function record(string $message, array $context = []): LogRecord
    {
        return new LogRecord(
            new \DateTimeImmutable,
            'testing',
            Level::Info,
            $message,
            $context,
        );
    }

    public function test_it_redacts_sensitive_context_keys(): void
    {
        $processor = new SensitiveDataProcessor;

        $result = $processor($this->record('Login attempt', [
            'email' => 'user@example.com',
            'password' => 'super-secret',
            'phone_number' => '08012345678',
            'txn_pin' => '1234',
            'nin' => '12345678901',
            'nested' => ['next_of_kin_phone_number' => '08099998888'],
            'status' => 'active',
        ]));

        $replacement = config('data-protection.redaction.replacement');

        $this->assertSame($replacement, $result->context['email']);
        $this->assertSame($replacement, $result->context['password']);
        $this->assertSame($replacement, $result->context['phone_number']);
        $this->assertSame($replacement, $result->context['txn_pin']);
        $this->assertSame($replacement, $result->context['nin']);
        $this->assertSame($replacement, $result->context['nested']['next_of_kin_phone_number']);
        $this->assertSame('active', $result->context['status']);
    }

    public function test_it_masks_email_addresses_in_messages(): void
    {
        $processor = new SensitiveDataProcessor;

        $result = $processor($this->record('Password reset for user@example.com'));

        $this->assertStringNotContainsString('user@example.com', $result->message);
        $this->assertStringContainsString('[REDACTED]', $result->message);
    }

    public function test_it_does_not_redact_unrelated_keys(): void
    {
        $processor = new SensitiveDataProcessor;

        $result = $processor($this->record('Shipping update', [
            'shipping_address' => 'keep this?',
            'shipping' => 'standard',
        ]));

        $this->assertSame('standard', $result->context['shipping']);
    }

    public function test_the_log_tap_attaches_the_processor_to_monolog(): void
    {
        $monolog = new MonologLogger('testing');
        $monolog->pushHandler($handler = new TestHandler);
        $logger = new Logger($monolog);

        (new RedactSensitiveData)($logger);

        $logger->info('Login for user@example.com', ['password' => 'secret']);

        $this->assertTrue($handler->hasInfoRecords());

        $record = $handler->getRecords()[0];

        $this->assertSame('[REDACTED]', $record['context']['password']);
        $this->assertStringNotContainsString('user@example.com', $record['message']);
    }
}
