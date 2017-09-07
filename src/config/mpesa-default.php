<?php

return [

    'live_auth' => 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials',

    'sandbox_auth' => 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials',

    'cert_path' => __DIR__.'/../cert/saf.cer',

    'version' => 'v1',

    'sandbox_root_url' => 'https://sandbox.safaricom.co.ke/mpesa',

    'live_root_url' => 'https://api.safaricom.co.ke/mpesa',

    'b2b_url' => '/b2b/',

    'b2c_url' => '/b2c/',

    'c2b_url' => '/c2b/'
];