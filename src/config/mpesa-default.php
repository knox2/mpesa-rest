<?php

return [

    'live_auth' => 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials',

    'sandbox_auth' => 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials',

    'version' => env('MPESA_VERSION', 'v1'),

    'cert_path_live' => __DIR__.'/../cert/saf_prod.cer',

    'cert_path_test' => __DIR__.'/../cert/saf_test.cer',

    'sandbox_root_url' => 'https://sandbox.safaricom.co.ke/mpesa',

    'live_root_url' => 'https://api.safaricom.co.ke/mpesa',

    'b2b_url' => '/b2b/',

    'b2c_url' => '/b2c/',

    'c2b_url' => '/c2b/',

    'proxy' => env('MPESA_PROXY')
];