<?php

namespace App\Http\Requests\Emf\Field;

use App\Http\Requests\Emf\EmfRequest;

class SecurityRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'twoFactorEnabled' => ['sometimes', 'boolean'],
            'biometricEnabled' => ['sometimes', 'boolean'],
            'generateBackupCodes' => ['sometimes', 'boolean'],
        ];
    }
}
