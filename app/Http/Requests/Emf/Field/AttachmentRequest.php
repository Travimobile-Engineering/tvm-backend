<?php

namespace App\Http\Requests\Emf\Field;

use App\Http\Requests\Emf\EmfRequest;

class AttachmentRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'in:photo,voice_note,document'],
            'fileUrl' => ['nullable', 'url', 'max:2048'],
            'file' => ['nullable', 'file', 'max:10240'],
            'fileSize' => ['nullable', 'integer', 'min:1'],
            'durationSeconds' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
