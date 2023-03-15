<?php

return [

    'env' => env('MPESA_ENV', 'test'),

    'consumer_key' => env('MPESA_CONSUMER_KEY', ''),

    'consumer_secret' => env('MPESA_CONSUMER_SECRET', ''),

    'initiator_name' => env('MPESA_INITIATOR_NAME', ''),

    'initiator_password' => env('MPESA_INITIATOR_PASSWORD', ''),

    'short_code' => env('MPESA_SHORT_CODE', ''),

    'passkey' => env('MPESA_PASSKEY', ''),

    'partner_id' => env('MPESA_PARTNER_ID'),

    'partner_password' => env('MPESA_PARTNER_PASSWORD'),

    'b2c_timeout_url' => env('MPESA_B2C_TIMEOUT_URL', ''),

    'b2c_result_url' => env('MPESA_B2C_RESULT_URL',''),

    'b2b_timeout_url' => env('MPESA_B2B_TIMEOUT_URL', ''),

    'b2b_result_url' => env('MPESA_B2B_RESULT_URL',''),

    'c2b_validation_url' => env('MPESA_C2B_VALIDATION_URL', ''),

    'c2b_confirmation_url' => env('MPESA_C2B_CONFIRMATION_URL',''),

    'account_balance_timeout_url' => env('MPESA_ACCOUNT_BALANCE_TIMEOUT_URL', ''),

    'account_balance_result_url' => env('MPESA_ACCOUNT_BALANCE_CONFIRMATION_URL',''),

    'reversal_timeout_url' => env('MPESA_REVERSAL_TIMEOUT_URL', ''),

    'reversal_result_url' => env('MPESA_REVERSAL_CONFIRMATION_URL',''),

    'stk_callback_url' => env('MPESA_STK_CALLBACK_URL',''),

    'transaction_status_timeout_url' => env('MPESA_TRANSACTION_STATUS_TIMEOUT_URL', ''),

    'transaction_status_result_url' => env('MPESA_TRANSACTION_STATUS_CONFIRMATION_URL',''),

    'identity_callback_url' => env('MPESA_IDENTITY_CALLBACK_URL',''),

    'response_type' => env('MPESA_RESPONSE_TYPE', 'Completed') // Valid Values are Completed and Cancelled

];