<?php

declare(strict_types=1);

namespace Modules\HR\Services\Portal;

use Illuminate\Support\Facades\Storage;

/**
 * شعار الشركة لتطبيق بوابة الموظف (tenant-scoped) — يُعرض قبل تسجيل الدخول.
 */
final class PortalBrandingService
{
    private const MAX_LOGO_BYTES = 1572864;

    private const LOGO_MIMES = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
    ];

    public function logoHash(): string
    {
        $logo = $this->logoFile();

        return $logo === null ? '' : $logo['hash'];
    }

    /**
     * @return array{mime: string, hash: string, data: string}|null
     */
    public function logo(): ?array
    {
        $logo = $this->logoFile();
        if ($logo === null) {
            return null;
        }

        $contents = Storage::disk('public')->get($logo['path']);
        if (! is_string($contents) || $contents === '') {
            return null;
        }

        return [
            'mime' => $logo['mime'],
            'hash' => $logo['hash'],
            'data' => base64_encode($contents),
        ];
    }

    /**
     * @return array{path: string, mime: string, hash: string}|null
     */
    private function logoFile(): ?array
    {
        $path = public_storage_relative_path((string) setting('company_logo', ''));
        if ($path === '') {
            return null;
        }

        $mime = self::LOGO_MIMES[strtolower(pathinfo($path, PATHINFO_EXTENSION))] ?? null;
        if ($mime === null) {
            return null;
        }

        try {
            $disk = Storage::disk('public');
            if (! $disk->exists($path)) {
                return null;
            }
            $size = (int) $disk->size($path);
            if ($size <= 0 || $size > self::MAX_LOGO_BYTES) {
                return null;
            }
            $modified = (int) $disk->lastModified($path);
        } catch (\Throwable) {
            return null;
        }

        return [
            'path' => $path,
            'mime' => $mime,
            'hash' => sha1($path.'|'.$size.'|'.$modified),
        ];
    }
}
