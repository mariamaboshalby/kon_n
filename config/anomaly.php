<?php

return [
    'enabled' => (bool) env('ANOMALY_DETECTION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Rules
    |--------------------------------------------------------------------------
    | Each rule class must implement:
    | - App\Services\AnomalyDetection\Contracts\AnomalyRule
    */
    'rules' => [
        \App\Services\AnomalyDetection\Rules\OperHeadInvalidCurrencyRule::class,
        \App\Services\AnomalyDetection\Rules\OperHeadTotalsMismatchRule::class,
        \App\Services\AnomalyDetection\Rules\OperationItemInvalidQuantitiesRule::class,
        \App\Services\AnomalyDetection\Rules\OperationItemNegativeValuesRule::class,
        \App\Services\AnomalyDetection\Rules\AccHeadSuspiciousBalanceRule::class,
    ],
];

