<?php

use App\Enums\TenantRole;
use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\User;
use App\Notifications\TenantInvitationNotification;
use App\Support\TenantDestination;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Notification::fake();
    $this->seed(PermissionSeeder::class);
});

/**
 * Tenant uji yang pemiliknya juga tercatat di pivot keanggotaan (plus role),
 * seperti hasil alur produksi.
 *
 * @return array{user: User, tenant: Tenant}
 */
function memberTenant(string $domain): array
{
    ['user' => $owner, 'tenant' => $tenant] = categoryTenant($domain);

    $tenant->members()->attach($owner->getKey(), ['joined_at' => now()]);

    return ['user' => $owner, 'tenant' => $tenant];
}

/**
 * Tambahkan user ke tenant sebagai pivot sekaligus role.
 */
function attachMember(Tenant $tenant, User $user, TenantRole $role): void
{
    $tenant->members()->syncWithoutDetaching([$user->getKey() => ['joined_at' => now()]]);
    categoryRole($tenant, $user, $role);
}

function invitePayload(array $overrides = []): array
{
    return array_merge([
        'email' => 'calon@fluxa.test',
        'role' => TenantRole::Member->value,
    ], $overrides);
}

test('owner mengundang anggota baru dan notifikasi terkirim', function () {
    ['user' => $owner] = memberTenant('keluarga-uji');

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/invitations', invitePayload())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $invitation = TenantInvitation::query()->firstOrFail();

    expect($invitation->email)->toBe('calon@fluxa.test')
        ->and($invitation->role)->toBe(TenantRole::Member)
        ->and($invitation->status->value)->toBe('pending');

    Notification::assertSentTo($invitation, TenantInvitationNotification::class);

    $this->actingAs($owner)
        ->get(categoryBaseUrl('keluarga-uji').'/members')
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('members/Index')
                ->has('members', 1)
                ->where('members.0.role', 'owner')
                ->has('invitations', 1)
                ->where('invitations.0.email', 'calon@fluxa.test')
                ->where('invitations.0.can_resend', true)
                ->where('invitations.0.can_revoke', true)
                ->where('can.invite', true),
        );
});

test('admin boleh mengundang, member ditolak', function () {
    ['tenant' => $tenant] = memberTenant('keluarga-uji');
    $admin = categoryUser($tenant, TenantRole::Admin, 'Sinta');
    attachMember($tenant, $admin, TenantRole::Admin);
    $member = categoryUser($tenant, TenantRole::Member, 'Rudi');
    attachMember($tenant, $member, TenantRole::Member);

    $this->actingAs($admin)
        ->post(categoryBaseUrl('keluarga-uji').'/invitations', invitePayload())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->actingAs($member)
        ->post(categoryBaseUrl('keluarga-uji').'/invitations', invitePayload(['email' => 'lain@fluxa.test']))
        ->assertForbidden();

    expect(TenantInvitation::query()->count())->toBe(1);
});

test('undang ulang email yang sama tidak duplikat dan mematikan token lama', function () {
    ['user' => $owner] = memberTenant('keluarga-uji');

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/invitations', invitePayload())
        ->assertRedirect();

    $first = TenantInvitation::query()->firstOrFail();

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/invitations', invitePayload())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(TenantInvitation::query()->count())->toBe(1);

    $second = TenantInvitation::query()->firstOrFail();

    expect($second->token)->not->toBe($first->token);

    $this->actingAs($owner)
        ->get('/invitations/'.$first->token)
        ->assertNotFound();
});

test('email anggota aktif dan role owner ditolak', function () {
    ['user' => $owner, 'tenant' => $tenant] = memberTenant('keluarga-uji');

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/invitations', invitePayload(['email' => $owner->email]))
        ->assertSessionHasErrors(['email']);

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/invitations', invitePayload(['role' => TenantRole::Owner->value]))
        ->assertSessionHasErrors(['role']);

    expect(TenantInvitation::query()->count())->toBe(0);
});

test('halaman terima menampilkan status tamu untuk yang belum login', function () {
    ['user' => $owner] = memberTenant('keluarga-uji');

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/invitations', invitePayload())
        ->assertRedirect();

    $invitation = TenantInvitation::query()->firstOrFail();

    // actingAs menempel untuk seluruh request dalam satu test, jadi keluar
    // dulu untuk mensimulasikan pengklik yang belum login.
    auth()->logout();

    $this->get('/invitations/'.$invitation->token)
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('invitations/Show')
                ->where('state', 'guest')
                ->where('invitation.email', 'calon@fluxa.test'),
        );

    $this->get('/invitations/token-salah')
        ->assertNotFound();
});

test('email cocok bisa menerima dan menjadi anggota dengan role undangan', function () {
    ['user' => $owner, 'tenant' => $tenant] = memberTenant('keluarga-uji');

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/invitations', invitePayload(['role' => TenantRole::Admin->value]))
        ->assertRedirect();

    $invitation = TenantInvitation::query()->firstOrFail();
    $candidate = User::factory()->create(['email' => 'calon@fluxa.test']);

    $this->actingAs($candidate)
        ->get('/invitations/'.$invitation->token)
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('invitations/Show')
                ->where('state', 'ready'),
        );

    // Tanpa header X-Inertia, Inertia::location menjawab 302 biasa;
    // di browser (XHR) jawabannya 409 + X-Inertia-Location.
    $this->actingAs($candidate)
        ->post('/invitations/'.$invitation->token.'/accept')
        ->assertRedirect($tenant->url('/dashboard'));

    expect($tenant->members()->whereKey($candidate->getKey())->exists())->toBeTrue()
        ->and(TenantDestination::roleIn($tenant, $candidate))->toBe(TenantRole::Admin)
        ->and($invitation->refresh()->status->value)->toBe('accepted');
});

test('login dengan email berbeda ditolak dan tidak masuk tenant', function () {
    ['user' => $owner] = memberTenant('keluarga-uji');

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/invitations', invitePayload())
        ->assertRedirect();

    $invitation = TenantInvitation::query()->firstOrFail();
    $intruder = User::factory()->create(['email' => 'asing@fluxa.test']);

    $this->actingAs($intruder)
        ->get('/invitations/'.$invitation->token)
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('invitations/Show')
                ->where('state', 'email_mismatch'),
        );

    $this->actingAs($intruder)
        ->post('/invitations/'.$invitation->token.'/accept')
        ->assertForbidden();

    expect($invitation->tenant->members()->whereKey($intruder->getKey())->exists())->toBeFalse();
});

test('undangan kedaluwarsa ditandai dan tidak bisa diterima', function () {
    ['user' => $owner] = memberTenant('keluarga-uji');

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/invitations', invitePayload())
        ->assertRedirect();

    $invitation = TenantInvitation::query()->firstOrFail();
    $invitation->update(['expires_at' => now()->subDay()]);

    $candidate = User::factory()->create(['email' => 'calon@fluxa.test']);

    $this->actingAs($candidate)
        ->get('/invitations/'.$invitation->token)
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('invitations/Show')
                ->where('state', 'expired'),
        );

    expect($invitation->refresh()->status->value)->toBe('expired');

    $this->actingAs($candidate)
        ->post('/invitations/'.$invitation->token.'/accept')
        ->assertStatus(410);
});

test('undangan yang dibatalkan tidak bisa diterima dan tautannya mati', function () {
    ['user' => $owner] = memberTenant('keluarga-uji');
    $url = categoryBaseUrl('keluarga-uji');

    $this->actingAs($owner)
        ->post($url.'/invitations', invitePayload())
        ->assertRedirect();

    $invitation = TenantInvitation::query()->firstOrFail();

    $this->actingAs($owner)
        ->delete($url.'/invitations/'.$invitation->getKey())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($invitation->refresh()->status->value)->toBe('revoked');

    $candidate = User::factory()->create(['email' => 'calon@fluxa.test']);

    // Token diacak saat revoke, link lama mati total.
    $this->actingAs($candidate)
        ->get('/invitations/'.$invitation->refresh()->token)
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('invitations/Show')
                ->where('state', 'revoked'),
        );

    $this->actingAs($candidate)
        ->post('/invitations/'.$invitation->refresh()->token.'/accept')
        ->assertStatus(410);
});

test('terima dua kali idempotent, tidak duplikat pivot', function () {
    ['user' => $owner, 'tenant' => $tenant] = memberTenant('keluarga-uji');

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/invitations', invitePayload())
        ->assertRedirect();

    $invitation = TenantInvitation::query()->firstOrFail();
    $candidate = User::factory()->create(['email' => 'calon@fluxa.test']);

    $this->actingAs($candidate)
        ->post('/invitations/'.$invitation->token.'/accept')
        ->assertRedirect($tenant->url('/dashboard'));

    $this->actingAs($candidate)
        ->post('/invitations/'.$invitation->token.'/accept')
        ->assertStatus(410);

    expect($tenant->members()->whereKey($candidate->getKey())->count())->toBe(1);
});

test('anggota lama melihat status sudah-anggota', function () {
    ['user' => $owner, 'tenant' => $tenant] = memberTenant('keluarga-uji');
    $member = categoryUser($tenant, TenantRole::Member, 'Rudi');
    attachMember($tenant, $member, TenantRole::Member);

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/invitations', invitePayload(['email' => 'calon@fluxa.test']))
        ->assertRedirect();

    // Rudi membuka link undangan orang lain: ia sudah anggota, bukan mismatch.
    $invitation = TenantInvitation::query()->firstOrFail();

    $this->actingAs($member)
        ->get('/invitations/'.$invitation->token)
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('invitations/Show')
                ->where('state', 'already_member'),
        );
});

test('batalkan undangan tenant lain menjawab 404', function () {
    ['user' => $firstOwner] = memberTenant('keluarga-uji');

    $this->actingAs($firstOwner)
        ->post(categoryBaseUrl('keluarga-uji').'/invitations', invitePayload())
        ->assertRedirect();

    $invitation = TenantInvitation::query()->firstOrFail();

    ['user' => $secondOwner] = memberTenant('komunitas-uji');

    $this->actingAs($secondOwner)
        ->delete(categoryBaseUrl('komunitas-uji').'/invitations/'.$invitation->getKey())
        ->assertNotFound();

    expect($invitation->refresh()->status->value)->toBe('pending');
});
