<?php

return [

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],

    'ghn' => [
        'base_url'         => env('GHN_BASE_URL', 'https://dev-online-gateway.ghn.vn/shiip/public-api'),
        'token'            => env('GHN_TOKEN'),
        'shop_id'          => env('GHN_SHOP_ID'),
        'verify_ssl'       => env('GHN_VERIFY_SSL', false),
        'from_district_id' => env('GHN_FROM_DISTRICT_ID'),
    ],

    // CẤU HÌNH VÍ MOMO (Đồng bộ bộ Key Sandbox V2 chuẩn của MoMo)
    'momo' => [
        'endpoint'     => 'https://test-payment.momo.vn/v2/gateway/api/create',
        'partner_code' => 'MOMO',
        'access_key'   => 'F8BBA842ECF85',
        'secret_key'   => 'K951B6PE1waDMi640xX08PD3vg6EkVlz',
        'verify_ssl'   => false,
        'redirect_url' => 'https://lar-vidu1-kov7.onrender.com/payment/momo/callback',
        'ipn_url'      => 'https://lar-vidu1-kov7.onrender.com/payment/momo/ipn',
        'request_type' => 'captureWallet',
    ],

];