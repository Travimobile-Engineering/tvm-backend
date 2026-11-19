<?php

namespace App\Http\Requests\Emf;

use Illuminate\Foundation\Http\FormRequest;

abstract class EmfRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
}
