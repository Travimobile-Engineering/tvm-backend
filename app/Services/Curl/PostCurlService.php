<?php

namespace App\Services\Curl;

use Exception;
use Illuminate\Support\Facades\Http;

class PostCurlService
{
    public function __construct(
        protected string $url,
        protected array $headers = [],
        protected array $fields = []
    ) {}

    public function execute()
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->timeout(60)
                ->asForm()
                ->post($this->url, $this->fields);

            if ($response->failed()) {
                throw new Exception('HTTP request failed: '.$response->body());
            }

            $result = $response->json();

            if (! is_array($result)) {
                throw new Exception('Invalid JSON response from API.');
            }

            return $result['data'] ?? $result;

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
