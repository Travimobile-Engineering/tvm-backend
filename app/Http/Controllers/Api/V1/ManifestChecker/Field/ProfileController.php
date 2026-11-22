<?php

namespace App\Http\Controllers\Api\V1\ManifestChecker\Field;

use App\Http\Controllers\Api\V1\ManifestChecker\EmfApiController;
use App\Http\Requests\Emf\Field\AppearanceRequest;
use App\Http\Requests\Emf\Field\ChangePasswordRequest;
use App\Http\Requests\Emf\Field\NotificationPreferencesRequest;
use App\Http\Requests\Emf\Field\SecurityRequest;
use App\Http\Requests\Emf\Field\SupportRequest;
use App\Services\Emf\Field\EmfFieldAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends EmfApiController
{
    public function __construct(private readonly EmfFieldAccountService $account) {}

    public function show(Request $request): JsonResponse
    {
        return $this->success($this->account->profile($this->officer($request)), 'Field profile.');
    }

    public function updateSecurity(SecurityRequest $request): JsonResponse
    {
        return $this->success($this->account->updateSecurity($this->officer($request), $request->validated(), $request), 'Security settings updated.');
    }

    public function updateAppearance(AppearanceRequest $request): JsonResponse
    {
        return $this->success($this->account->updateAppearance($this->officer($request), $request->validated(), $request), 'Appearance updated.');
    }

    public function updateNotifications(NotificationPreferencesRequest $request): JsonResponse
    {
        return $this->success([
            'preferences' => $this->account->updateNotifications(
                $this->officer($request),
                (array) $request->input('preferences', []),
                $request,
            ),
        ], 'Notification preferences updated.');
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->account->changePassword(
            $this->officer($request),
            (string) $request->string('currentPassword'),
            (string) $request->string('newPassword'),
            $this->sessionId($request),
            $request,
        );

        return $this->success(null, 'Password updated. Use it next time you sign in.');
    }

    public function sessions(Request $request): JsonResponse
    {
        return $this->success($this->account->sessions($this->officer($request), $this->sessionId($request)), 'Active sessions.');
    }

    public function revokeSession(Request $request, string $session): JsonResponse
    {
        $this->account->revokeSession($this->officer($request), $session, $request);

        return $this->success(null, 'Device signed out.');
    }

    public function revokeOtherSessions(Request $request): JsonResponse
    {
        $count = $this->account->revokeOtherSessions($this->officer($request), $this->sessionId($request), $request);

        return $this->success(['signedOut' => $count], 'Other devices signed out.');
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $count = $this->account->revokeAllSessions($this->officer($request), $request);

        return $this->success(['signedOut' => $count], 'Signed out of all devices.');
    }

    public function activity(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'filter' => ['sometimes', 'in:all,checks,reports,account'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'pageSize' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        return $this->success($this->account->activity(
            $this->officer($request),
            (string) ($validated['filter'] ?? 'all'),
            (int) ($validated['page'] ?? 1),
            (int) ($validated['pageSize'] ?? 25),
        ), 'My activity.');
    }

    public function support(): JsonResponse
    {
        return $this->success($this->account->support(), 'Help and support.');
    }

    public function createSupport(SupportRequest $request): JsonResponse
    {
        $support = $this->account->createSupportRequest($this->officer($request), $request->validated(), $request);

        return $this->success([
            'requestId' => $support->id,
            'type' => $support->request_type,
            'status' => $support->status,
        ], 'Support request sent.', 201);
    }

    public function requestCorrection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'field' => ['required', 'in:full_name,email,nin,phone,rank,division,reporting_line'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $support = $this->account->createSupportRequest($this->officer($request), [
            'type' => 'correction',
            'subject' => 'Profile correction: '.$validated['field'],
            'message' => $validated['message'],
        ], $request);

        return $this->success([
            'requestId' => $support->id,
            'field' => $validated['field'],
            'status' => $support->status,
        ], 'Correction request sent.', 201);
    }
}
