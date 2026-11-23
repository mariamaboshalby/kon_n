# All Installments Page Feature

## Overview
This feature provides a comprehensive view of all installment payments across all clients in the system.

## Features

### 1. Filters
- **Client Search**: Live search with 500ms debounce for better performance
- **Status Filter**: Filter by payment status (Paid, Pending, Overdue)
- **Plan Number**: Filter by specific installment plan ID
- **Date Range**: Filter by due date range (From/To)

### 2. Summary Cards
- **Total Installments**: Total count of all installments
- **Total Paid**: Sum of all paid amounts
- **Total Pending**: Sum of all pending amounts
- **Total Overdue**: Sum of all overdue amounts

### 3. Data Table
Displays all installments with the following columns:
- ID
- Plan Number (clickable link to plan details)
- Client Name
- Installment Number
- Amount Due
- Amount Paid
- Due Date
- Payment Date
- Status (with color-coded badges)
- Actions (View Plan button)

### 4. Pagination
- 20 records per page
- Query string preserved across pages

## Route
```php
Route::get('/installments/payments/all', [InstallmentController::class, 'allPayments'])
    ->name('installments.payments.all')
    ->middleware('can:view Installment Plans');
```

## Controller Method
```php
public function allPayments(Request $request)
```

## View File
`Modules/Installments/Resources/views/all_payments.blade.php`

## Sidebar Integration
Added to the installments sidebar menu with icon `las la-money-bill-wave`

## Permissions
Requires: `view Installment Plans` permission

## Translation Keys
- `all_installments`
- `total_installments`
- `total_paid`
- `total_pending`
- `total_overdue`

## Best Practices Applied
- ✅ Alpine.js for client-side interactions (debounced search)
- ✅ Bootstrap 5 for UI components
- ✅ Proper localization (all text uses `__()` helper)
- ✅ Type safety (`declare(strict_types=1);`)
- ✅ Eager loading to prevent N+1 queries
- ✅ Pagination for performance
- ✅ Query string preservation
- ✅ Proper authorization checks
- ✅ Responsive design
