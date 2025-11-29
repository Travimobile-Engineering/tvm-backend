<?php

namespace App\Services\Client;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class HttpService
{
    public function request(string $method, string $endpoint, RequestOptions|array|null $options = null)
    {
        if (is_array($options)) {
            $options = new RequestOptions(data: $options);
        }

        $time = time();
        $apiKey = config('services.payment.api_key');
        $apiSecret = config('services.payment.api_secret');
        $body = json_encode($options->getData()).$time;
        $signature = hash_hmac('sha256', $body, $apiSecret);

        $options ??= new RequestOptions;

        try {
            $client = Http::withHeaders(array_merge([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-API-KEY' => $apiKey,
                'X-SIGNATURE' => $signature,
                'X-TIMESTAMP' => $time,
                'X-PAYMENT-AUTH' => config('services.payment.header_value'),
            ], $options->getHeaders()))
                ->timeout($options->getTimeout())
                ->connectTimeout($options->getConnectTimeout())
                ->retry(
                    $options->getRetries(),
                    $options->getRetryDelay(),
                    fn ($exception) => $this->shouldRetry($exception),
                );

            if ($options->getToken()) {
                $client = $client->withToken($options->getToken());
            }

            $response = match (strtolower($method)) {
                'get' => $client->get($endpoint, $options->getData()),
                'post' => $client->post($endpoint, $options->getData()),
                'put' => $client->put($endpoint, $options->getData()),
                'patch' => $client->patch($endpoint, $options->getData()),
                'delete' => $client->delete($endpoint, $options->getData()),
                default => throw new \InvalidArgumentException("Unsupported method [$method]"),
            };

            if ($response->failed()) {
                throw new RequestException($response);
            }

            return $response;

        } catch (ConnectionException $e) {
            logger()->error("HTTP Request Timeout: {$method} {$endpoint}", [
                'error' => $e->getMessage(),
                'timeout' => $options->getTimeout(),
            ]);

            throw new \Exception("Request timeout after {$options->getTimeout()} seconds");
        }
    }

    private function shouldRetry(\Exception $exception): bool
    {
        return $exception instanceof ConnectionException ||
               ($exception->getCode() >= 500) ||
               $exception->getCode() === 429;
    }

    public function get(string $endpoint, RequestOptions|array|null $options = null)
    {
        return $this->request('GET', $endpoint, $options);
    }

    public function post(string $endpoint, RequestOptions|array|null $options = null)
    {
        return $this->request('POST', $endpoint, $options);
    }

    public function put(string $endpoint, RequestOptions|array|null $options = null)
    {
        return $this->request('PUT', $endpoint, $options);
    }

    public function delete(string $endpoint, RequestOptions|array|null $options = null)
    {
        return $this->request('DELETE', $endpoint, $options);
    }
}
