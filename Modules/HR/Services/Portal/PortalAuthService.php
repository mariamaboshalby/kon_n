<?php

declare(strict_types=1);

namespace Modules\HR\Services\Portal;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Modules\HR\Models\Employee;
use Modules\HR\Models\EmployeeDevice;
use Modules\HR\Models\EmployeePortalToken;

final class PortalAuthService
{
    private const MAX_ATTEMPTS_PER_LOGIN = 5;

    /** Failed attempts only — a whole branch can share one public IP. */
    private const MAX_ATTEMPTS_PER_IP = 60;

    private const LOCK_SECONDS = 60;

    public function __construct(
        private readonly PortalEmployeePresenter $presenter,
        private readonly PortalNotificationService $notifications
    ) {
    }

    /**
     * @param  array{uuid: string, platform?: string|null, model?: string|null, os_version?: string|null, app_version?: string|null}  $device
     * @return array<string, mixed>
     */
    public function login(string $login, string $password, array $device, string $ip): array
    {
        $settings = PortalSettings::current();
        if (! $settings->enabled()) {
            throw new PortalException(__('hr::portal.portal_disabled'), 'portal_disabled', 403);
        }

        $login = trim($login);
        $tenantKey = (string) (tenant('id') ?? 'central');
        $loginKey = 'hr-portal-login:'.$tenantKey.':'.mb_strtolower($login);
        $ipKey = 'hr-portal-login-ip:'.$tenantKey.':'.$ip;

        if (RateLimiter::tooManyAttempts($loginKey, self::MAX_ATTEMPTS_PER_LOGIN)
            || RateLimiter::tooManyAttempts($ipKey, self::MAX_ATTEMPTS_PER_IP)) {
            $seconds = max(RateLimiter::availableIn($loginKey), RateLimiter::availableIn($ipKey));

            throw new PortalException(
                __('hr::portal.too_many_attempts', ['seconds' => $seconds]),
                'too_many_attempts',
                429
            );
        }

        $employee = $this->findEmployee($login);

        if (! $employee || ! $employee->password || ! Hash::check($password, (string) $employee->password)) {
            RateLimiter::hit($loginKey, self::LOCK_SECONDS);
            RateLimiter::hit($ipKey, self::LOCK_SECONDS);

            throw new PortalException(__('hr::portal.invalid_credentials'), 'invalid_credentials', 401);
        }

        $this->assertEmployeeCanUsePortal($employee, allowInactive: true);

        RateLimiter::clear($loginKey);

        return DB::transaction(function () use ($employee, $device, $settings): array {
            $deviceModel = $this->registerDevice($employee, $device, $settings);

            if ($deviceModel->isRevoked()) {
                throw new PortalException(__('hr::portal.device_revoked'), 'device_revoked', 403);
            }

            EmployeePortalToken::query()->where('employee_device_id', $deviceModel->id)->delete();

            $plain = Str::random(64);
            $ttlDays = max(1, (int) config('hr.portal.token_ttl_days', 30));

            $token = EmployeePortalToken::create([
                'employee_id' => $employee->id,
                'employee_device_id' => $deviceModel->id,
                'token_hash' => EmployeePortalToken::hashToken($plain),
                'name' => Str::limit((string) ($deviceModel->model ?? 'device'), 95, ''),
                'last_used_at' => now(),
                'expires_at' => now()->addDays($ttlDays),
            ]);

            return [
                'token' => $plain,
                'expires_at' => $token->expires_at?->toIso8601String(),
                'device_status' => $deviceModel->status,
                'employee' => $this->presenter->profile($employee, $deviceModel),
            ];
        });
    }

    /**
     * @param  bool  $allowInactive  الموظف غير النشط يدخل ويرى شاشة «الحساب متوقف» فقط (me · logout)
     */
    public function assertEmployeeCanUsePortal(Employee $employee, bool $allowInactive = false): void
    {
        if (! $allowInactive && self::isEmployeeInactive($employee)) {
            throw new PortalException(__('hr::portal.account_disabled'), 'account_disabled', 403);
        }

        if (! $employee->portal_enabled) {
            throw new PortalException(__('hr::portal.portal_not_enabled_for_employee'), 'portal_not_enabled', 403);
        }
    }

    public static function isEmployeeInactive(Employee $employee): bool
    {
        return ($employee->getAttributes()['status'] ?? null) === 'خارج الشركة' || $employee->date_of_fire !== null;
    }

    public function logout(EmployeePortalToken $token): void
    {
        $token->delete();
    }

    public function revokeAllTokens(Employee $employee): void
    {
        EmployeePortalToken::query()->where('employee_id', $employee->id)->delete();
    }

    public function changePassword(Employee $employee, string $current, string $new, ?EmployeePortalToken $keepToken): void
    {
        if (! Hash::check($current, (string) $employee->password)) {
            throw new PortalException(__('hr::portal.current_password_wrong'), 'current_password_wrong', 422);
        }

        DB::transaction(function () use ($employee, $new, $keepToken): void {
            $employee->forceFill([
                'password' => $new,
                'portal_password_changed_at' => now(),
            ])->save();

            EmployeePortalToken::query()
                ->where('employee_id', $employee->id)
                ->when($keepToken, fn ($q) => $q->where('id', '!=', $keepToken->id))
                ->delete();
        });
    }

    /**
     * رقم البصمة أولاً، ثم رقم الموظف لمن ليس له رقم بصمة، ثم الجوال.
     */
    private function findEmployee(string $login): ?Employee
    {
        if ($login === '') {
            return null;
        }

        if (ctype_digit($login) && strlen($login) <= 9) {
            $byFinger = Employee::query()->where('finger_print_id', (int) $login)->first();
            if ($byFinger) {
                return $byFinger;
            }

            $byId = Employee::query()->whereKey((int) $login)->whereNull('finger_print_id')->first();
            if ($byId) {
                return $byId;
            }
        }

        $byPhone = Employee::query()->where('phone', $login)->limit(2)->get();

        return $byPhone->count() === 1 ? $byPhone->first() : null;
    }

    /**
     * @param  array{uuid: string, platform?: string|null, model?: string|null, os_version?: string|null, app_version?: string|null}  $device
     */
    private function registerDevice(Employee $employee, array $device, PortalSettings $settings): EmployeeDevice
    {
        $model = EmployeeDevice::query()->firstOrNew([
            'employee_id' => $employee->id,
            'device_uuid' => Str::limit($device['uuid'], 100, ''),
        ]);

        $model->fill([
            'platform' => $device['platform'] ?? null,
            'model' => isset($device['model']) ? Str::limit((string) $device['model'], 120, '') : null,
            'os_version' => isset($device['os_version']) ? Str::limit((string) $device['os_version'], 40, '') : null,
            'app_version' => isset($device['app_version']) ? Str::limit((string) $device['app_version'], 20, '') : null,
            'last_seen_at' => now(),
        ]);

        if (! $model->exists) {
            $model->status = $this->initialDeviceStatus($employee, $settings);
            if ($model->status === EmployeeDevice::STATUS_APPROVED) {
                $model->approved_at = now();
            }
        } elseif (! $settings->deviceBinding() && $model->isPending()) {
            $model->status = EmployeeDevice::STATUS_APPROVED;
            $model->approved_at = now();
        }

        $model->save();

        if ($model->wasRecentlyCreated && $model->isPending()) {
            $this->notifications->notifyHrNewDevice($employee, $model);
        }

        return $model;
    }

    /**
     * أول جهاز للموظف يُعتمد تلقائياً؛ الأجهزة التالية تنتظر HR حين يكون ربط الجهاز مفعّلاً.
     */
    private function initialDeviceStatus(Employee $employee, PortalSettings $settings): string
    {
        if (! $settings->deviceBinding()) {
            return EmployeeDevice::STATUS_APPROVED;
        }

        $hasAnyDevice = EmployeeDevice::query()->where('employee_id', $employee->id)->exists();
        if (! $hasAnyDevice) {
            return EmployeeDevice::STATUS_APPROVED;
        }

        return EmployeeDevice::STATUS_PENDING;
    }
}
