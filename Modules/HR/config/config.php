<?php

return [
    'name' => 'HR',

    'portal' => [
        'token_ttl_days' => (int) env('HR_PORTAL_TOKEN_TTL_DAYS', 30),
        'attendance_min_interval_seconds' => 60,
        'sequence_window_hours' => 18,
        'max_attachment_kb' => 5120,
        'fcm' => [
            // مسار ملف Service Account (JSON) من Firebase — يُترك فارغاً لتعطيل Push
            'credentials' => env('HR_PORTAL_FCM_CREDENTIALS'),
            'project_id' => env('HR_PORTAL_FCM_PROJECT_ID'),
        ],
    ],
];
