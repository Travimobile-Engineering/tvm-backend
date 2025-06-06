<?php

namespace App\Services\Curl;

class BulkCurlService
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
        $url = "https://api.paystack.co/transfer/bulk";

        $fields_string = http_build_query($this->fields);

        $ch = curl_init();

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            return [
                'status' => false,
                'message' => curl_error($ch),
                'data' => null,
            ];
        }

        curl_close($ch);
        return json_decode($response, true);
    }
}




