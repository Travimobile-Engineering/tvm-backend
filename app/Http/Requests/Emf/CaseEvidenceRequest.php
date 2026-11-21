<?php

namespace App\Http\Requests\Emf;

class CaseEvidenceRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:40'],
            'label' => ['required', 'string', 'max:200'],
            'fileUrl' => ['nullable', 'url', 'max:2048'],
            'file' => ['nullable', 'file', 'max:10240'],
            'fileSize' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
