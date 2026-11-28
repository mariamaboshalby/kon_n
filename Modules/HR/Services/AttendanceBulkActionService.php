<?php

declare(strict_types=1);

namespace Modules\HR\Services;

use Illuminate\Support\Facades\DB;
use Modules\HR\Models\Attendance;

class AttendanceBulkActionService
{
    private const CHUNK_SIZE = 500;

    /**
     * @param  list<int>  $ids
     * @return array{processed: int, skipped: int, errors: list<string>, message: string}
     */
    public function execute(string $action, array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));

        if ($ids === []) {
            return [
                'processed' => 0,
                'skipped' => 0,
                'errors' => [],
                'message' => __('hr::hr.bulk_action_none_processed'),
            ];
        }

        return DB::transaction(function () use ($action, $ids): array {
            return match ($action) {
                'approve' => $this->approve($ids),
                'reject' => $this->reject($ids),
                'delete' => $this->delete($ids),
                default => [
                    'processed' => 0,
                    'skipped' => count($ids),
                    'errors' => [],
                    'message' => __('hr::hr.bulk_action_none_processed'),
                ],
            };
        });
    }

    /**
     * @param  list<int>  $ids
     * @return array{processed: int, skipped: int, errors: list<string>, message: string}
     */
    private function approve(array $ids): array
    {
        $processed = 0;
        $skipped = 0;
        $errors = [];
        $found = 0;

        foreach (array_chunk($ids, self::CHUNK_SIZE) as $chunk) {
            $rows = Attendance::query()->whereIn('id', $chunk)->get();
            $found += $rows->count();

            foreach ($rows as $attendance) {
                if ($attendance->status === 'approved') {
                    $skipped++;
                    continue;
                }

                if (! in_array($attendance->status, ['pending', 'rejected'], true)) {
                    $skipped++;
                    $errors[] = '#'.$attendance->id.': '.__('hr::hr.bulk_attendance_skip_status');
                    continue;
                }

                $attendance->update(['status' => 'approved']);
                $processed++;
            }
        }

        $skipped += count($ids) - $found;

        return $this->result('approve', $processed, $skipped, $errors);
    }

    /**
     * @param  list<int>  $ids
     * @return array{processed: int, skipped: int, errors: list<string>, message: string}
     */
    private function reject(array $ids): array
    {
        $processed = 0;
        $skipped = 0;
        $errors = [];
        $found = 0;

        foreach (array_chunk($ids, self::CHUNK_SIZE) as $chunk) {
            $rows = Attendance::query()->whereIn('id', $chunk)->get();
            $found += $rows->count();

            foreach ($rows as $attendance) {
                if ($attendance->status !== 'pending') {
                    $skipped++;
                    continue;
                }

                $attendance->update(['status' => 'rejected']);
                $processed++;
            }
        }

        $skipped += count($ids) - $found;

        return $this->result('reject', $processed, $skipped, $errors);
    }

    /**
     * @param  list<int>  $ids
     * @return array{processed: int, skipped: int, errors: list<string>, message: string}
     */
    private function delete(array $ids): array
    {
        $processed = 0;
        $skipped = 0;
        $errors = [];
        $found = 0;

        foreach (array_chunk($ids, self::CHUNK_SIZE) as $chunk) {
            $rows = Attendance::query()->whereIn('id', $chunk)->get();
            $found += $rows->count();

            foreach ($rows as $attendance) {
                $attendance->delete();
                $processed++;
            }
        }

        $skipped += count($ids) - $found;

        return $this->result('delete', $processed, $skipped, $errors);
    }

    /**
     * @param  list<string>  $errors
     * @return array{processed: int, skipped: int, errors: list<string>, message: string}
     */
    private function result(string $action, int $processed, int $skipped, array $errors): array
    {
        $messageKey = match ($action) {
            'approve' => 'hr::hr.bulk_approve_success',
            'reject' => 'hr::hr.bulk_reject_success',
            'delete' => 'hr::hr.bulk_delete_success',
            default => 'hr::hr.bulk_action_none_processed',
        };

        $message = $processed > 0
            ? __($messageKey, ['count' => $processed])
            : __('hr::hr.bulk_action_none_processed');

        if ($skipped > 0 && $processed > 0) {
            $message .= ' '.__('hr::hr.bulk_action_skipped', ['count' => $skipped]);
        }

        return [
            'processed' => $processed,
            'skipped' => $skipped,
            'errors' => $errors,
            'message' => $message,
        ];
    }
}
