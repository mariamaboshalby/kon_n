<?php

declare(strict_types=1);

namespace Modules\HR\Services\Portal;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * خطأ أعمال في بوابة الموظف — يُعرض للموظف كما هو مع error_type ثابت للتطبيق.
 */
final class PortalException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        string $message,
        private readonly string $errorType,
        private readonly int $status = 422,
        private readonly array $extra = []
    ) {
        parent::__construct($message);
    }

    public function errorType(): string
    {
        return $this->errorType;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function render(): JsonResponse
    {
        return response()->json(array_merge([
            'success' => false,
            'message' => $this->getMessage(),
            'error_type' => $this->errorType,
        ], $this->extra), $this->status);
    }
}
