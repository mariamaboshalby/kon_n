<?php

declare(strict_types=1);

return [
    // General
    'currencies' => 'Currencies',
    'currency' => 'Currency',
    'add_currency' => 'Add Currency',
    'edit_currency' => 'Edit Currency',
    'currency_details' => 'Currency Details',
    'no_currencies' => 'No currencies found',
    'create_new_currency' => 'Create New Currency',
    'manage_currencies' => 'Manage Currencies and Rates',

    // Fields
    'name' => 'Name',
    'name_placeholder' => 'Example: US Dollar',
    'name_required' => 'Currency name is required',
    'name_unique' => 'Currency name already exists',

    'code' => 'Code',
    'code_help' => 'International currency code (3 letters, e.g.: USD, EUR, EGP)',
    'code_required' => 'Currency code is required',
    'code_size' => 'Currency code must be exactly 3 characters',
    'code_unique' => 'Currency code already exists',

    'symbol' => 'Symbol',
    'decimal_places' => 'Decimal Places',
    'decimal_places_required' => 'Decimal places is required',

    'rate_mode' => 'Rate Mode',
    'rate_mode_automatic' => 'Automatic',
    'rate_mode_manual' => 'Manual',
    'rate_mode_required' => 'Rate mode is required',

    'current_rate' => 'Current Rate',
    'exchange_rate_history' => 'Exchange Rate History',
    'date' => 'Date',
    'rate' => 'Rate',
    'showing_latest_rates' => 'Showing latest :count rates',
    'no_exchange_rates' => 'No exchange rates recorded for this currency',

    'set_as_default' => 'Set as Default Currency',
    'default' => 'Default',

    // Messages
    'created_successfully' => 'Currency created successfully',
    'updated_successfully' => 'Currency updated successfully',
    'deleted_successfully' => 'Currency deleted successfully',
    'rate_updated_successfully' => 'Exchange rate updated successfully',

    // Errors
    'inactive_currency' => 'Cannot use an inactive currency',
    'invalid_exchange_rate' => 'Invalid exchange rate',

    // Currency Conversion
    'convert' => 'Convert Currency',
    'from_currency' => 'From Currency',
    'to_currency' => 'To Currency',
    'amount' => 'Amount',
    'converted_amount' => 'Converted Amount',
    'conversion_rate' => 'Conversion Rate',
    'currency_conversion' => 'Currency Conversion',

    // Cash Box Revaluation
    'cash_box_revaluation' => 'Cash Box Revaluation',
    'revaluation_date' => 'Revaluation Date',
    'revalue' => 'Revalue',
    'revaluation_history' => 'Revaluation History',
    'cash_balance' => 'Cash Balance',
    'previous_rate' => 'Previous Rate',
    'rate_change' => 'Rate Change',
    'gain_loss' => 'Gain/Loss',
    'gain' => 'Gain',
    'loss' => 'Loss',
    'neutral' => 'Neutral',
    'total_gain_loss' => 'Total Gain/Loss',
];
