<?php

declare(strict_types=1);

namespace Modules\HR\Services\Portal;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\HR\Models\EmployeeDevice;
use Throwable;

/**
 * إرسال Push عبر Firebase Cloud Messaging (HTTP v1) باستخدام Service Account.
 */
final class FcmPushService
{
    private const TOKEN_CACHE_KEY = 'hr_portal_fcm_access_token';

    public static function isConfigured(): bool
    {
        $path = (string) config('hr.portal.fcm.credentials', '');

        return $path !== '' && is_readable($path);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function sendToEmployee(int $employeeId, string $title, string $body, array $data = []): void
    {
        if (! self::isConfigured()) {
            return;
        }

        $devices = EmployeeDevice::query()
            ->where('employee_id', $employeeId)
            ->where('status', EmployeeDevice::STATUS_APPROVED)
            ->whereNotNull('fcm_token')
            ->get(['id', 'fcm_token']);

        if ($devices->isEmpty()) {
            return;
        }

        $credentials = $this->credentials();
        if ($credentials === null) {
            return;
        }

        $accessToken = $this->accessToken($credentials);
        if ($accessToken === null) {
            return;
        }

        $projectId = (string) (config('hr.portal.fcm.project_id') ?: ($credentials['project_id'] ?? ''));
        if ($projectId === '') {
            return;
        }

        $stringData = [];
        foreach ($data as $key => $value) {
            $stringData[(string) $key] = is_scalar($value) ? (string) $value : (string) json_encode($value);
        }

        foreach ($devices as $device) {
            try {
                $response = Http::withToken($accessToken)
                    ->timeout(10)
                    ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                        'message' => [
                            'token' => $device->fcm_token,
                            'notification' => ['title' => $title, 'body' => $body],
                            'data' => $stringData,
                            'android' => ['priority' => 'high'],
                        ],
                    ]);

                if ($response->status() === 404 || str_contains((string) $response->body(), 'UNREGISTERED')) {
                    $device->forceFill(['fcm_token' => null])->save();
                }
            } catch (Throwable $e) {
                Log::warning('HR portal FCM send failed', ['device' => $device->id, 'error' => $e->getMessage()]);
            }
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function credentials(): ?array
    {
        try {
            $json = json_decode((string) file_get_contents((string) config('hr.portal.fcm.credentials')), true);

            return is_array($json) && isset($json['client_email'], $json['private_key']) ? $json : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function accessToken(array $credentials): ?string
    {
        $cached = Cache::get(self::TOKEN_CACHE_KEY);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $now = time();
        $header = $this->base64Url((string) json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = $this->base64Url((string) json_encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $signature = '';
        if (! openssl_sign($header.'.'.$claims, $signature, (string) $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
            Log::warning('HR portal FCM: unable to sign JWT');

            return null;
        }

        try {
            $response = Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $header.'.'.$claims.'.'.$this->base64Url($signature),
            ]);
        } catch (Throwable $e) {
            Log::warning('HR portal FCM token request failed', ['error' => $e->getMessage()]);

            return null;
        }

        $token = (string) ($response->json('access_token') ?? '');
        if ($token === '') {
            return null;
        }

        Cache::put(self::TOKEN_CACHE_KEY, $token, max(60, (int) $response->json('expires_in', 3600) - 120));

        return $token;
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
