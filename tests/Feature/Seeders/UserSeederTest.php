<?php

use App\Enums\TenantRole;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantDestination;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\UserSeeder;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(UserSeeder::class);
});

test('owner hanya memegang satu tenant supaya login langsung ke dashboard', function () {
    $owner = User::where('email', 'owner@fluxa.test')->firstOrFail();

    expect(TenantDestination::tenantsOf($owner))->toHaveCount(1);
    expect(TenantDestination::afterLogin($owner))
        ->toBe('http://keluarga-demo.fluxa.test/dashboard');
});

test('tenant kedua membuktikan role tidak bocor antar tenant', function () {
    $admin = User::where('email', 'admin@fluxa.test')->firstOrFail();

    $keluarga = Tenant::where('name', 'Keluarga Demo')->firstOrFail();
    $rt = Tenant::where('name', 'Komunitas RT 05')->firstOrFail();

    expect(TenantDestination::roleIn($keluarga, $admin))->toBe(TenantRole::Admin);
    expect(TenantDestination::roleIn($rt, $admin))->toBe(TenantRole::Owner);
});

test('seeder otoritatif: dijalankan ulang tidak menumpuk keanggotaan', function () {
    $before = DB::table('tenant_user')->count();

    $this->seed(UserSeeder::class);

    expect(DB::table('tenant_user')->count())->toBe($before);
});

test('setiap tenant punya subdomain dan pemilik yang benar', function () {
    expect(Tenant::where('name', 'Keluarga Demo')->firstOrFail()->subdomain())->toBe('keluarga-demo');
    expect(Tenant::where('name', 'Komunitas RT 05')->firstOrFail()->subdomain())->toBe('rt-05');

    expect(Tenant::where('name', 'Komunitas RT 05')->firstOrFail()->owner->email)
        ->toBe('admin@fluxa.test');
});
