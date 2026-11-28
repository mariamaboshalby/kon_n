<?php

declare(strict_types=1);

namespace Modules\HR\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\HR\Models\EmployeePortalToken;
use Modules\HR\Services\Portal\PortalAuthService;
use Modules\HR\Services\Portal\PortalException;
use Modules\HR\Services\Portal\PortalSettings;
use Symfony\Component\HttpFoundation\Response;

/**
 * مصادقة موظف البوابة بـ Bearer token من جدول employee_portal_tokens (منفصل عن Sanctum/User).
 *
 * الاستخدام: employee.portal (يتطلب جهازاً معتمداً وموظفاً نشطاً) · employee.portal:allow_pending · employee.portal:allow_pending,allow_inactive
 */
class AuthenticatePortalEmployee
{
    public function __construct(private readonly PortalAuthService $auth)
    {
    }

    public function handle(Request $request, Closure $next, string ...$modes): Response
    {
        $locale = (string) $request->header('X-App-Locale', '');
        if (in_array($locale, ['ar', 'en'], true)) {
            app()->setLocale($locale);
        }

        $token = EmployeePortalToken::findByPlain((string) $request->bearerToken());

        if (! $token || $token->isExpired()) {
            $token?->delete();

            return (new PortalException(__('hr::portal.unauthenticated'), 'unauthenticated', 401))->render();
        }

        $token->loadMissing(['employee', 'device']);
        $employee = $token->employee;

        if (! $employee) {
            $token->delete();

            return (new PortalException(__('hr::portal.unauthenticated'), 'unauthenticated', 401))->render();
        }

        $settings = PortalSettings::current();
        if (! $settings->enabled()) {
            return (new PortalException(__('hr::portal.portal_disabled'), 'portal_disabled', 403))->render();
        }

        try {
            $this->auth->assertEmployeeCanUsePortal($employee, allowInactive: true);
        } catch (PortalException $e) {
            $this->auth->revokeAllTokens($employee);

            return $e->render();
        }

        if (! in_array('allow_inactive', $modes, true) && PortalAuthService::isEmployeeInactive($employee)) {
            return (new PortalException(__('hr::portal.account_disabled'), 'account_disabled', 403))->render();
        }

        $device = $token->device;
        if ($device && $device->isRevoked()) {
            $token->delete();

            return (new PortalException(__('hr::portal.device_revoked'), 'device_revoked', 401))->render();
        }

        if (! in_array('allow_pending', $modes, true) && $settings->deviceBinding() && $device && $device->isPending()) {
            return (new PortalException(__('hr::portal.device_pending'), 'device_pending', 403))->render();
        }

        if ($token->last_used_at === null || $token->last_used_at->lt(now()->subMinutes(5))) {
            $token->forceFill(['last_used_at' => now()])->save();
            $device?->forceFill(['last_seen_at' => now()])->save();
        }

        $request->attributes->set('portal_employee', $employee);
        $request->attributes->set('portal_token', $token);
        $request->attributes->set('portal_device', $device);

        return $next($request);
    }
}
