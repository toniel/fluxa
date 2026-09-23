<?php

use App\Enums\TenantRole;
use App\Support\TenantDestination;
use Database\Seeders\PermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

test('owner bisa mengubah role anggota dan berlaku segera', function () {
    ['user' => $owner, 'tenant' => $tenant] = memberTenant('keluarga-uji');
    $member = categoryUser($tenant, TenantRole::Member, 'Rudi');
    attachMember($tenant, $member, TenantRole::Member);

    $this->actingAs($owner)
        ->patch(
            categoryBaseUrl('keluarga-uji').'/members/'.$member->getKey(),
            ['role' => TenantRole::Admin->value],
        )
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(TenantDestination::roleIn($tenant, $member))->toBe(TenantRole::Admin);
});

test('member dan admin ditolak mengubah role', function () {
    ['user' => $owner, 'tenant' => $tenant] = memberTenant('keluarga-uji');
    $admin = categoryUser($tenant, TenantRole::Admin, 'Sinta');
    attachMember($tenant, $admin, TenantRole::Admin);
    $member = categoryUser($tenant, TenantRole::Member, 'Rudi');
    attachMember($tenant, $member, TenantRole::Member);
    $other = categoryUser($tenant, TenantRole::Member, 'Dono');
    attachMember($tenant, $other, TenantRole::Member);

    $this->actingAs($admin)
        ->patch(
            categoryBaseUrl('keluarga-uji').'/members/'.$other->getKey(),
            ['role' => TenantRole::Admin->value],
        )
        ->assertForbidden();

    $this->actingAs($member)
        ->patch(
            categoryBaseUrl('keluarga-uji').'/members/'.$other->getKey(),
            ['role' => TenantRole::Admin->value],
        )
        ->assertForbidden();

    expect(TenantDestination::roleIn($tenant, $other))->toBe(TenantRole::Member);
});

test('role owner tidak bisa diberikan atau diubah lewat sini', function () {
    ['user' => $owner, 'tenant' => $tenant] = memberTenant('keluarga-uji');
    $member = categoryUser($tenant, TenantRole::Member, 'Rudi');
    attachMember($tenant, $member, TenantRole::Member);
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->patch($url.'/members/'.$member->getKey(), ['role' => TenantRole::Owner->value])
        ->assertStatus(422);

    $this->actingAs($owner)
        ->patch($url.'/members/'.$owner->getKey(), ['role' => TenantRole::Admin->value])
        ->assertForbidden();

    expect(TenantDestination::roleIn($tenant, $member))->toBe(TenantRole::Member);
});

test('owner dan admin bisa mengeluarkan anggota, kecuali owner', function () {
    ['user' => $owner, 'tenant' => $tenant] = memberTenant('keluarga-uji');
    $admin = categoryUser($tenant, TenantRole::Admin, 'Sinta');
    attachMember($tenant, $admin, TenantRole::Admin);
    $member = categoryUser($tenant, TenantRole::Member, 'Rudi');
    attachMember($tenant, $member, TenantRole::Member);
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($admin)
        ->delete($url.'/members/'.$member->getKey())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($tenant->members()->whereKey($member->getKey())->exists())->toBeFalse()
        ->and(TenantDestination::roleIn($tenant, $member))->toBeNull();

    // Admin tidak bisa mengeluarkan owner.
    $this->actingAs($admin)
        ->delete($url.'/members/'.$owner->getKey())
        ->assertForbidden();

    // Owner tidak bisa keluar sendiri tanpa transfer ownership.
    $this->actingAs($owner)
        ->delete($url.'/members/'.$owner->getKey())
        ->assertForbidden();

    expect($tenant->members()->whereKey($owner->getKey())->exists())->toBeTrue();
});

test('member ditolak mengeluarkan anggota lain', function () {
    ['user' => $owner, 'tenant' => $tenant] = memberTenant('keluarga-uji');
    $member = categoryUser($tenant, TenantRole::Member, 'Rudi');
    attachMember($tenant, $member, TenantRole::Member);
    $other = categoryUser($tenant, TenantRole::Member, 'Dono');
    attachMember($tenant, $other, TenantRole::Member);

    $this->actingAs($member)
        ->delete(categoryBaseUrl('keluarga-uji').'/members/'.$other->getKey())
        ->assertForbidden();

    expect($tenant->members()->whereKey($other->getKey())->exists())->toBeTrue();
});

test('ubah dan hapus bukan anggota menjawab 404', function () {
    ['user' => $owner, 'tenant' => $tenant] = memberTenant('keluarga-uji');
    $outsider = categoryUser($tenant, TenantRole::Member, 'Asing');
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->patch($url.'/members/'.$outsider->getKey(), ['role' => TenantRole::Admin->value])
        ->assertNotFound();

    $this->actingAs($owner)
        ->delete($url.'/members/'.$outsider->getKey())
        ->assertNotFound();
});

test('daftar anggota menampilkan role dan tombol sesuai izin', function () {
    ['user' => $owner, 'tenant' => $tenant] = memberTenant('keluarga-uji');
    // Nama pemilik dibuat paling akhir alfabet supaya urutan daftar
    // deterministik: Rudi dulu, pemilik sesudahnya.
    $owner->update(['name' => 'Zulkifli Owner']);
    $member = categoryUser($tenant, TenantRole::Member, 'Rudi');
    attachMember($tenant, $member, TenantRole::Member);

    $this->actingAs($owner)
        ->get(categoryBaseUrl('keluarga-uji').'/members')
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('members/Index')
                ->has('members', 2)
                ->where('members.0.role', 'member')
                ->where('members.0.can_remove', true)
                ->where('members.1.role', 'owner')
                ->where('members.1.can_remove', false)
                ->where('members.1.can_change_role', false),
        );
});
