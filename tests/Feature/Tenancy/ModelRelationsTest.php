<?php

use App\Models\Domain;
use Database\Seeders\PermissionSeeder;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

test('domain redirecting menyaring domain pengalihan saja', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('utama-uji');

    $tenant->domains()->create([
        'domain' => 'lama-uji',
        'is_primary' => false,
        'redirects_to' => 'utama-uji',
    ]);
    $tenant->domains()->create([
        'domain' => 'lain-uji',
        'is_primary' => false,
    ]);

    $redirecting = Domain::query()
        ->forTenant($tenant)
        ->redirecting()
        ->pluck('domain')
        ->all();

    expect($redirecting)->toBe(['lama-uji']);

    $primary = Domain::query()->forTenant($tenant)->primary()->firstOrFail();

    expect($primary->domain)->toBe('utama-uji');
});

test('relasi tenants memuat keanggotaan user melalui pivot', function () {
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant('utama-uji');

    $tenant->members()->attach($owner->getKey(), ['joined_at' => now()]);

    expect($owner->tenants->pluck('id')->all())->toBe([$tenant->getKey()]);
    expect($owner->tenants()->first()->getKey())->toBe($tenant->getKey());
});
