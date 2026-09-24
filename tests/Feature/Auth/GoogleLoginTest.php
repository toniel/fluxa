<?php

use App\Models\Category;
use App\Models\Domain;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PlanSeeder;
use Laravel\Socialite\Contracts\User as GoogleUser;
use Laravel\Socialite\Facades\Socialite;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, PlanSeeder::class]);
});

function mockGoogleDriver(?GoogleUser $user = null): void
{
    $driver = Mockery::mock();
    $driver->shouldReceive('redirect')->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'))->byDefault();
    $driver->shouldReceive('user')->andReturn($user)->byDefault();

    Socialite::shouldReceive('driver')->with('google')->andReturn($driver);
}

function mockGoogleUser(array $overrides = []): GoogleUser
{
    $user = Mockery::mock(GoogleUser::class);
    $user->shouldReceive('getId')->andReturn($overrides['id'] ?? 'google-123');
    $user->shouldReceive('getEmail')->andReturn($overrides['email'] ?? 'budi@gmail.com');
    $user->shouldReceive('getName')->andReturn($overrides['name'] ?? 'Budi');
    $user->shouldReceive('getNickname')->andReturn($overrides['nickname'] ?? null);
    $user->shouldReceive('getAvatar')->andReturn($overrides['avatar'] ?? 'https://avatar/x.png');

    return $user;
}

test('redirect menyimpan token undangan lalu ke google', function () {
    mockGoogleDriver();

    $this->get('/auth/google/redirect?invitation=token-abc')
        ->assertRedirect('https://accounts.google.com/o/oauth2/auth');

    expect(session('fluxa.invitation_token'))->toBe('token-abc');
});

test('user baru dibuatkan akun plus tenant lengkap', function () {
    mockGoogleDriver(mockGoogleUser());

    $this->get('/auth/google/callback')
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $user = User::query()->where('email', 'budi@gmail.com')->firstOrFail();

    expect($user->google_id)->toBe('google-123')
        ->and($user->password)->toBeNull()
        ->and($user->hasPassword())->toBeFalse()
        ->and($user->email_verified_at)->not->toBeNull();

    $tenant = Tenant::query()->where('owner_id', $user->getKey())->firstOrFail();

    expect($tenant->members()->whereKey($user->getKey())->exists())->toBeTrue();

    $tenantId = $tenant->getKey();

    $categories = Category::query()->withoutTenantScope()->where('tenant_id', $tenantId)->count();

    expect($categories)->toBe(7);

    $subscription = Subscription::query()->withoutTenantScope()->where('tenant_id', $tenantId)->firstOrFail();

    expect($subscription->plan->slug)->toBe('free')
        ->and($subscription->isActive())->toBeTrue();

    $this->assertAuthenticatedAs($user);
});

test('email lama yang sudah punya akun hanya di-link, tidak duplikat', function () {
    $existing = User::factory()->create(['email' => 'budi@gmail.com']);

    mockGoogleDriver(mockGoogleUser());

    $this->get('/auth/google/callback')
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(User::query()->count())->toBe(1)
        ->and($existing->refresh()->google_id)->toBe('google-123');
});

test('user google-only ditolak login password tanpa error', function () {
    User::factory()->create(['email' => 'budi@gmail.com', 'password' => null]);

    $this->post('/login', ['email' => 'budi@gmail.com', 'password' => 'rahasia'])
        ->assertSessionHasErrors(['email']);

    $this->assertGuest();
});

test('user baru lewat undangan tidak dibuatkan tenant pribadi', function () {
    ['user' => $owner, 'tenant' => $tenant] = memberTenant('keluarga-uji');

    $this->actingAs($owner)
        ->post(categoryBaseUrl('keluarga-uji').'/invitations', invitePayload(['email' => 'calon@gmail.com']))
        ->assertRedirect();

    $invitation = TenantInvitation::query()->firstOrFail();

    mockGoogleDriver(mockGoogleUser(['email' => 'calon@gmail.com', 'id' => 'google-999']));

    $this->get('/auth/google/redirect?invitation='.$invitation->token)
        ->assertRedirect();

    $this->get('/auth/google/callback')
        ->assertRedirect(route('invitations.show', $invitation->token));

    $candidate = User::query()->where('email', 'calon@gmail.com')->firstOrFail();

    expect(Tenant::query()->where('owner_id', $candidate->getKey())->count())->toBe(0);
});

test('register email biasa juga membuat tenant', function () {
    $this->post('/register', [
        'name' => 'Sinta',
        'email' => 'sinta@fluxa.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect();

    $user = User::query()->where('email', 'sinta@fluxa.test')->firstOrFail();

    expect(Tenant::query()->where('owner_id', $user->getKey())->count())->toBe(1);
});

test('subdomain tenant baru unik dan bukan kata reserved', function () {
    // Satu mock, dua jawaban berurutan: shouldReceive kedua menumpuk dan
    // panggilan kedua akan memakai jawaban pertama.
    $first = mockGoogleUser(['email' => 'satu@gmail.com', 'id' => 'google-1']);
    $second = mockGoogleUser(['email' => 'dua@gmail.com', 'id' => 'google-2', 'name' => 'Budi']);

    $driver = Mockery::mock();
    $driver->shouldReceive('user')->andReturn($first, $second);
    Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

    $this->get('/auth/google/callback')->assertRedirect();
    $this->get('/auth/google/callback')->assertRedirect();

    $subdomains = Domain::query()->pluck('domain')->all();

    expect($subdomains)->toHaveCount(2)
        ->and($subdomains[0])->not->toBe($subdomains[1]);

    foreach ($subdomains as $subdomain) {
        // Format haikunator: kata-kata-angka, mis. wispy-dust-42.
        expect($subdomain)->toMatch('/^[a-z]+-[a-z0-9]+-\d+$/')
            ->and($subdomain)->not->toBeIn(config('fluxa.reserved_subdomains'));
    }
});
