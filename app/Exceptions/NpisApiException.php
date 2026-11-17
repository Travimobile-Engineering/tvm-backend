<?php

namespace App\Exceptions;

use Exception;

class NpisApiException extends Exception
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        string $message,
        public readonly array $details = [],
        public readonly ?int $retryAfterSeconds = null,
    ) {
        parent::__construct($message);
    }

    /**
     * @param  array<string, mixed>  $details
     */
    public static function make(int $status, string $code, string $message, array $details = [], ?int $retryAfterSeconds = null): self
    {
        return new self($status, $code, $message, $details, $retryAfterSeconds);
    }

    /**
     * @param  array<string, string>  $fields
     */
    public static function validation(string $message = 'Some details need attention.', array $fields = []): self
    {
        return new self(422, 'VALIDATION_FAILED', $message, ['fields' => $fields]);
    }
}
