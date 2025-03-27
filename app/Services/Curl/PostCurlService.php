<?php

namespace App\Services\Curl;

use Exception;

class PostCurlService
{
    protected $url;
    protected $headers = [];
    protected $fields;

    public function __construct(string $url, array $headers = [], array $fields = [])
    {
        $this->url = $url;
        foreach ($headers as $key => $value) {
            $this->headers[] = "$key: $value";
        }
        $this->fields = $fields;
    }

    public function execute()
    {
        $fields_string = http_build_query($this->fields);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            throw new Exception("cURL error: $error_msg");
        }

        $result = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from API: ' . json_last_error_msg());
        }

        if (!isset($result['data'])) {
            return $result;
        }

        return $result['data'];
    }
}
