<?php

namespace App\Services\Curl;

use App\Trait\HttpResponse;

class GetCurlService
{
    use HttpResponse;

    protected $url;

    protected $headers = [];

    public function __construct(string $url, array $headers = [])
    {
        $this->url = $url;
        foreach ($headers as $key => $value) {
            $this->headers[] = "$key: $value";
        }
    }

    public function execute()
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            return $this->error(null, 'Curl error: '.curl_error($ch), 400);
        }

        curl_close($ch);

        $result = json_decode($response, true);

        if (! $result) {
            return $this->error(null, 'Invalid JSON response from API: '.json_encode($response), 400);
        }

        if (! isset($result['data'])) {
            return $this->error(null, $result['message'] ?? 'An error occurred while processing your request.', 400);
        }

        return $result['data'];
    }
}
