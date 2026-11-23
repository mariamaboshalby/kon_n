<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Services\TelegramService;
use Illuminate\Auth\Events\Login;

class SendTelegramLoginNotification
{
    public function __construct(private readonly TelegramService $telegram) {}

    /**
     * Handle the Login event.
     */
    public function handle(Login $event): void
    {
        // منع إرسال الرسالة مرتين لنفس الـ session
        $cacheKey = 'telegram_login_notified_' . session()->getId();
        if (cache()->has($cacheKey)) {
            return;
        }
        cache()->put($cacheKey, true, now()->addMinutes(1));

        $user = $event->user;
        $ip   = request()->ip() ?? 'unknown';
        $time = now()->format('Y-m-d H:i:s');

        $message = "🔐 <b>تسجيل دخول جديد</b>\n\n"
            . "👤 <b>المستخدم:</b> {$user->name}\n"
            . "📧 <b>البريد:</b> {$user->email}\n"
            . "🌐 <b>IP:</b> {$ip}\n"
            . "🕐 <b>الوقت:</b> {$time}";

        $this->telegram->send($message);
    }
}
