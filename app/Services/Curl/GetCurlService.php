<?php

namespace App\Services\Curl;

use Exception;

class GetCurlService
{
    protected $url;
    protected $headers = [];

    public function __construct(string $url, array $headers = [])
    {
        $this->url = $url;
        foreach ($headers as $key => $value) {
            $this->headers[] = "$key: $value";
        }
    }

    public function execute(): array
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception('Curl error: ' . curl_error($ch));
        }

        curl_close($ch);

        $result = json_decode($response, true);

        if (!$result) {
            throw new Exception('Invalid JSON response from API: ' . $response);
        }

        if (!isset($result['data'])) {
            throw new Exception('API response does not contain "data" field: ' . json_encode($result));
        }

        return $result['data'];
    }
}
