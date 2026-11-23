<?php
$src = __DIR__ . '/Modules/POS/public/js/restaurant-pos.js';
$dst = __DIR__ . '/public/modules/pos/js/restaurant-pos.js';
if (copy($src, $dst)) {
    echo "Copied successfully\n";
} else {
    echo "Failed\n";
}
