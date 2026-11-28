<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers\Api\Portal;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Modules\HR\Http\Requests\Portal\PortalLoginRequest;
use Modules\HR\Services\Portal\PortalAuthService;
use Modules\HR\Services\Portal\PortalBrandingService;
use Modules\HR\Services\Portal\PortalEmployeePresenter;
use Modules\HR\Services\Portal\PortalSelfService;
use Modules\HR\Services\Portal\PortalSettings;
use Throwable;

class PortalAuthController extends PortalBaseController
{
    public function __construct(
        private readonly PortalAuthService $auth,
        private readonly PortalEmployeePresenter $presenter,
        private readonly PortalSelfService $selfService,
        private readonly PortalBrandingService $branding
    ) {
    }

    public function ping(): JsonResponse
    {
        $companyName = null;
        try {
            $companyName = setting('campany_name') ?: Setting::query()->value('company_name');
        } catch (Throwable) {
        }

        return response()->json([
            'success' => true,
            'app' => 'urca-employee-portal',
            'api_version' => 1,
            'server_time' => now()->toIso8601String(),
            'portal_enabled' => PortalSettings::current()->enabled(),
            'company_name' => $companyName ?: (string) (tenant('id') ?? ''),
            'logo_hash' => $this->branding->logoHash(),
        ]);
    }

    public function brandingLogo(): JsonResponse
    {
        $logo = $this->branding->logo();
        if ($logo === null) {
            return response()->json(['success' => true, 'logo' => null]);
        }

        return response()->json(['success' => true, 'logo' => $logo]);
    }

    public function login(PortalLoginRequest $request): JsonResponse
    {
        $locale = (string) $request->header('X-App-Locale', '');
        if (in_array($locale, ['ar', 'en'], true)) {
            app()->setLocale($locale);
        }

        $data = $request->validated();
        $result = $this->auth->login(
            (string) $data['login'],
            (string) $data['password'],
            $data['device'],
            (string) $request->ip()
        );

        return response()->json(array_merge(['success' => true, 'message' => __('hr::portal.login_success')], $result));
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $this->token($request);
        if ($token) {
            $this->auth->logout($token);
        }

        return response()->json(['success' => true]);
    }

    public function me(Request $request): JsonResponse
    {
        $employee = $this->employee($request);

        return response()->json([
            'success' => true,
            'employee' => $this->presenter->profile($employee, $this->device($request)),
            'unread_notifications' => $this->selfService->unreadCount($employee),
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::min(6)],
        ], [], [
            'current_password' => __('hr::portal.field_current_password'),
            'password' => __('hr::portal.field_new_password'),
        ]);

        $this->auth->changePassword(
            $this->employee($request),
            (string) $data['current_password'],
            (string) $data['password'],
            $this->token($request)
        );

        return response()->json(['success' => true, 'message' => __('hr::portal.password_changed')]);
    }

    public function fcmToken(Request $request): JsonResponse
    {
        $data = $request->validate(['fcm_token' => ['nullable', 'string', 'max:255']]);

        $device = $this->device($request);
        $device?->forceFill(['fcm_token' => $data['fcm_token'] ?? null])->save();

        return response()->json(['success' => true]);
    }
}
