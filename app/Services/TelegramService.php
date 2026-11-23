<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private string $token;
    private string $chatId;

    public function __construct()
    {
        $setting = Setting::first();
        $this->token  = (string) ($setting?->telegram_bot_token ?? '');
        $this->chatId = (string) ($setting?->telegram_chat_id ?? '');
    }

    /**
     * Send a message to the configured Telegram chat.
     *
     * @param string $message HTML-formatted message
     */
    public function send(string $message): void
    {
        if ($this->token === '' || $this->chatId === '') {
            return;
        }

        try {
            $response = Http::post("https://api.telegram.org/bot{$this->token}/sendMessage", [
                'chat_id'    => $this->chatId,
                'text'       => $message,
                'parse_mode' => 'HTML',
            ]);

            if (! $response->successful()) {
                Log::error('TelegramService: failed to send message', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('TelegramService: exception', ['message' => $e->getMessage()]);
        }
    }
}
