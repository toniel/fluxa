<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reserved Subdomains
    |--------------------------------------------------------------------------
    |
    | Dipakai oleh generator subdomain maupun validasi custom subdomain milik
    | subscriber. Selain nama infrastruktur, daftar ini juga memblokir kata
    | yang bisa dipakai untuk phishing internal (login, secure, verify).
    |
    */

    'reserved_subdomains' => [
        'www', 'api', 'admin', 'app', 'mail', 'billing',
        'static', 'assets', 'cdn', 'help', 'support', 'status',
        'central', 'dashboard', 'login', 'secure', 'account', 'verify',
    ],

    /*
    |--------------------------------------------------------------------------
    | Kategori Default
    |--------------------------------------------------------------------------
    |
    | Di-seed per tenant baru lewat SeedDefaultCategories. Row per tenant,
    | bukan shared global, supaya tenant bebas edit/hapus tanpa memengaruhi
    | tenant lain. "Lainnya" muncul di kedua tipe — itu sebabnya unique key
    | tabel categories menyertakan kolom type.
    |
    */

    'default_categories' => [
        ['name' => 'Gaji', 'type' => 'income', 'icon' => 'banknote'],
        ['name' => 'Lainnya', 'type' => 'income', 'icon' => 'circle-plus'],
        ['name' => 'Makan', 'type' => 'expense', 'icon' => 'utensils'],
        ['name' => 'Transport', 'type' => 'expense', 'icon' => 'car'],
        ['name' => 'Belanja', 'type' => 'expense', 'icon' => 'shopping-bag'],
        ['name' => 'Tagihan', 'type' => 'expense', 'icon' => 'receipt'],
        ['name' => 'Lainnya', 'type' => 'expense', 'icon' => 'ellipsis'],
    ],

];
