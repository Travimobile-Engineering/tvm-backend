<?php

namespace App\Http\Controllers\Api\V1\ManifestChecker;

use App\Exceptions\NpisApiException;
use App\Http\Controllers\Controller;
use App\Models\Emf\Officer;
use App\Trait\HttpResponse;
use Illuminate\Http\Request;

abstract class EmfApiController extends Controller
{
    use HttpResponse;

    protected function officer(Request $request): Officer
    {
        $officer = $request->attributes->get('emf_officer');

        if (! $officer) {
            throw NpisApiException::make(401, 'UNAUTHENTICATED', 'Sign in to the Manifest Checker.');
        }

        return $officer;
    }

    /**
     * @return array<string, mixed>
     */
    protected function pagination(int $page, int $pageSize, int $total): array
    {
        return [
            'page' => $page,
            'pageSize' => $pageSize,
            'totalItems' => $total,
            'totalPages' => max(1, (int) ceil($total / $pageSize)),
        ];
    }

    protected function guardState(?string $stateCode, Officer $officer): void
    {
        if ($stateCode !== null && $stateCode !== $officer->state_code) {
            throw NpisApiException::make(404, 'NOT_FOUND', 'This record is outside your state command.');
        }
    }
}
