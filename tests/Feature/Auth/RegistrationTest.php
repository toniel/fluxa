<?php

use Database\Seeders\PermissionSeeder;
use Database\Seeders\PlanSeeder;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    // Registrasi membuatkan tenant + role + langganan free, jadi definisi
    // role dan paket harus ada.
    $this->seed([
        PermissionSeeder::class,
        PlanSeeder::class,
    ]);

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('tenants.index', absolute: false));
});
