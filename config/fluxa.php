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

    /*
    |--------------------------------------------------------------------------
    | Ikon Kategori
    |--------------------------------------------------------------------------
    |
    | Nama yang boleh dipilih untuk field ikon di form kategori. Pemetaan nama
    | ke komponen ikonnya hidup di resources/js/lib/categoryIcons.ts, jadi
    | daftar ini dan map itu harus dijaga selaras.
    |
    */

    'category_icons' => [
        'baby',
        'banknote',
        'briefcase',
        'broom',
        'bus',
        'candy',
        'car',
        'circle-dollar-sign',
        'circle-plus',
        'coffee',
        'coins',
        'credit-card',
        'cup-soda',
        'droplets',
        'dumbbell',
        'ellipsis',
        'gamepad-2',
        'gift',
        'globe',
        'graduation-cap',
        'hand-coins',
        'heart-pulse',
        'house',
        'key',
        'landmark',
        'laptop',
        'milk',
        'paw-print',
        'piggy-bank',
        'plane',
        'plug',
        'receipt',
        'shirt',
        'shopping-bag',
        'shopping-basket',
        'shopping-cart',
        'signal',
        'smartphone',
        'soap-dispenser-droplet',
        'stethoscope',
        'utensils',
        'wallet',
        'wifi',
        'wrench',
    ],

    /*
    |--------------------------------------------------------------------------
    | Warna Kategori
    |--------------------------------------------------------------------------
    |
    | Slot palet kategorikal lima warna dari DESIGN.md. Disimpan sebagai nama
    | slot ("cat-1".."cat-5"), dan frontend me-resolve var CSS yang sama dengan
    | donat dashboard supaya warnanya ikut terang/gelap. Sama dengan palet di
    | resources/css/app.css harus dijaga selaras.
    |
    */

    'category_colors' => [
        'cat-1',
        'cat-2',
        'cat-3',
        'cat-4',
        'cat-5',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ikon Kantong
    |--------------------------------------------------------------------------
    |
    | Nama yang boleh dipilih untuk ikon kantong di form. Pemetaan nama ke
    | komponen ikonnya hidup di resources/js/lib/accountIcons.ts.
    |
    */

    'account_icons' => [
        'banknote',
        'car',
        'circle-dollar-sign',
        'coins',
        'credit-card',
        'ellipsis',
        'gift',
        'hand-coins',
        'house',
        'landmark',
        'piggy-bank',
        'plane',
        'receipt',
        'shopping-bag',
        'smartphone',
        'wallet',
        'wrench',
    ],

];
