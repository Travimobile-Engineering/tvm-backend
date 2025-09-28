<?php

namespace App\Services\Client;

class RequestOptions
{
    public function __construct(
        public readonly array $data = [],
        public readonly ?string $token = null,
        public readonly int $timeout = 20,
        public readonly int $connectTimeout = 5,
        public readonly int $retries = 1,
        public readonly int $retryDelay = 200,
        public readonly array $headers = []
    ) {
        $this->validate();
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getConnectTimeout(): int
    {
        return $this->connectTimeout;
    }

    public function getRetries(): int
    {
        return $this->retries;
    }

    public function getRetryDelay(): int
    {
        return $this->retryDelay;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    private function validate(): void
    {
        if ($this->timeout <= 0) {
            throw new \InvalidArgumentException('Timeout must be positive');
        }

        if ($this->connectTimeout <= 0) {
            throw new \InvalidArgumentException('Connect timeout must be positive');
        }

        if ($this->retries < 0) {
            throw new \InvalidArgumentException('Retries cannot be negative');
        }

        if ($this->retryDelay < 0) {
            throw new \InvalidArgumentException('Retry delay cannot be negative');
        }

        if ($this->connectTimeout > $this->timeout) {
            throw new \InvalidArgumentException('Connect timeout cannot be greater than total timeout');
        }
    }

    public function withData(array $data): self
    {
        return new self(
            $data,
            $this->token,
            $this->timeout,
            $this->connectTimeout,
            $this->retries,
            $this->retryDelay,
            $this->headers
        );
    }

    public function withTimeout(int $timeout): self
    {
        $new = new self(
            $this->data,
            $this->token,
            $timeout,
            $this->connectTimeout,
            $this->retries,
            $this->retryDelay,
            $this->headers
        );

        $new->validate();

        return $new;
    }
}
