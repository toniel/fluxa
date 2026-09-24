<?php

use App\Enums\TenantRole;
use App\Models\Account;
use App\Models\TenantInvitation;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PlanSeeder;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, PlanSeeder::class]);
});

test('paket free menolak undangan anggota keempat', function () {
    ['user' => $owner, 'tenant' => $tenant] = memberTenant('keluarga-uji');
    $admin = categoryUser($tenant, TenantRole::Admin, 'Sinta');
    attachMember($tenant, $admin, TenantRole::Admin);
    $member = categoryUser($tenant, TenantRole::Member, 'Rudi');
    attachMember($tenant, $member, TenantRole::Member);
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/invitations', invitePayload(['email' => 'keempat@fluxa.test']))
        ->assertSessionHasErrors(['email']);

    expect(TenantInvitation::query()->count())->toBe(0);

    // Naik ke pro membuka kembali.
    billingSubscription($tenant, 'pro-monthly');

    $this->actingAs($owner)
        ->post($url.'/invitations', invitePayload(['email' => 'keempat@fluxa.test']))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(TenantInvitation::query()->count())->toBe(1);
});

test('kirim ulang undangan lama tetap boleh saat limit penuh', function () {
    ['user' => $owner, 'tenant' => $tenant] = memberTenant('keluarga-uji');
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/invitations', invitePayload())
        ->assertRedirect();

    $admin = categoryUser($tenant, TenantRole::Admin, 'Sinta');
    attachMember($tenant, $admin, TenantRole::Admin);
    $member = categoryUser($tenant, TenantRole::Member, 'Rudi');
    attachMember($tenant, $member, TenantRole::Member);

    $this->actingAs($owner)
        ->post($url.'/invitations', invitePayload())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(TenantInvitation::query()->count())->toBe(1);
});

test('paket free menolak kantong keempat, termasuk yang terarsip tetap dihitung', function () {
    ['user' => $owner, 'tenant' => $tenant] = memberTenant('keluarga-uji');
    $url = categoryBaseUrl('keluarga-uji');

    foreach (['Satu', 'Dua', 'Tiga'] as $name) {
        $this->actingAs($owner)
            ->post($url.'/accounts', accountPayload(['name' => $name]))
            ->assertRedirect();
    }

    $this->actingAs($owner)
        ->post($url.'/accounts', accountPayload(['name' => 'Empat']))
        ->assertSessionHasErrors(['name']);

    expect(Account::query()->count())->toBe(3);

    $first = Account::query()->where('name', 'Satu')->firstOrFail();

    $this->actingAs($owner)
        ->patch($url.'/accounts/'.$first->getKey().'/archive')
        ->assertRedirect();

    $this->actingAs($owner)
        ->post($url.'/accounts', accountPayload(['name' => 'Empat']))
        ->assertSessionHasErrors(['name']);

    // Ubah kantong yang ada tetap boleh.
    $this->actingAs($owner)
        ->put($url.'/accounts/'.$first->getKey(), accountPayload(['name' => 'Satu Baru']))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    // Naik ke pro membuka kembali.
    billingSubscription($tenant, 'pro-monthly');

    $this->actingAs($owner)
        ->post($url.'/accounts', accountPayload(['name' => 'Empat']))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Account::query()->count())->toBe(4);
});
