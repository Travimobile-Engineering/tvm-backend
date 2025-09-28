<?php

namespace App\Services\SMS;

use App\Models\SmsLog;

class LogService
{
    public function __construct(
        protected $phone,
        protected $request,
        protected $response,
        protected $provider,
        protected $status
    ) {}

    public function run()
    {
        $log = new SmsLog;
        $log->phone_number = $this->phone;
        $log->request = $this->request;
        $log->response = $this->response;
        $log->provider = $this->provider;
        $log->status = $this->status;
        $log->save();

        return $log;
    }
}
