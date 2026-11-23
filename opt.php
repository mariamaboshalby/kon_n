<?php
// هذا الكود سيمسح كل شيء يمنع الكود الجديد من الظهور
$paths = [
    '../storage/framework/views',
    '../storage/framework/cache/data',
    '../storage/bootstrap/cache',
];

foreach ($paths as $path) {
    if (is_dir($path)) {
        $files = glob($path . '/*'); 
        foreach ($files as $file) {
            if (is_file($file)) @unlink($file);
        }
        echo "تم تنظيف: $path <br>";
    }
}

// تنفيذ أوامر لارافل برمجياً
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->call('optimize:clear');
$kernel->call('view:clear');

echo "<h1>تم بنجاح! جرب فتح الصفحة الآن</h1>";