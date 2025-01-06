<?php

namespace App\Services;

use App\Models\State;
use App\Trait\HttpResponse;

class OtherService
{
    use HttpResponse;

    public function getStates()
    {
        $states = State::select('id', 'name')->get();

        return $this->success($states, 'States retrieved successfully');
    }
}



