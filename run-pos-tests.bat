@echo off
php artisan test tests/Feature/POS/RestaurantInvoiceServiceTest.php > test-output.txt 2>&1
type test-output.txt
