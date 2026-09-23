<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway
    |--------------------------------------------------------------------------
    |
    | Provider yang dipakai billing: 'xendit' atau 'log' (tanpa provider,
    | untuk lokal). Ganti provider = ganti nilai ini + isi kuncinya, tanpa
    | menyentuh action, controller, atau halaman. Adapter baru (misal
    | midtrans, duitku) didaftarkan di AppServiceProvider dengan namanya.
    |
    */

    'gateway' => env('BILLING_GATEWAY', 'log'),

    'gateways' => [
        'xendit' => [
            'secret_key' => env('XENDIT_SECRET_KEY'),
            'callback_token' => env('XENDIT_CALLBACK_TOKEN'),
            'base_url' => env('XENDIT_BASE_URL', 'https://api.xendit.co'),
        ],
        'log' => [],
    ],

];
