<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$translator = app('translator');
$loader = $translator->getLoader();

$loaded = $loader->load('ar', 'hr', null);
var_dump(array_key_exists('on_leave', $loaded));
var_dump(array_key_exists('absent', $loaded));
var_dump(array_key_exists('daily_employee_report', $loaded));
echo count($loaded) . " keys total\n";
