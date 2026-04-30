<?php

use App\Models\LoginOtp;
use App\Models\User;
use App\Notifications\LoginOtpNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response
        ->assertOk()
        ->assertSeeVolt('pages.auth.login');
});

test('valid login credentials send an otp and redirect to verification', function () {
    Notification::fake();

    $user = User::factory()->create();

    $component = Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'password');

    $component->call('login');

    $component
        ->assertHasNoErrors()
        ->assertRedirect(route('login.otp', absolute: false));

    $this->assertGuest();
    $this->assertDatabaseHas('login_otps', ['user_id' => $user->id, 'consumed_at' => null]);
    Notification::assertSentTo($user, LoginOtpNotification::class);
});

test('users can authenticate using the emailed otp', function () {
    $user = User::factory()->create();

    session([
        \App\Services\Auth\LoginOtpService::SESSION_USER_ID => $user->id,
        \App\Services\Auth\LoginOtpService::SESSION_REMEMBER => false,
    ]);

    LoginOtp::query()->create([
        'user_id' => $user->id,
        'code_hash' => Hash::make('123456'),
        'expires_at' => now()->addMinutes(10),
    ]);

    Volt::test('pages.auth.login-otp')
        ->set('code', '123456')
        ->call('verify')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $component = Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'wrong-password');

    $component->call('login');

    $component
        ->assertHasErrors()
        ->assertNoRedirect();

    $this->assertGuest();
});

test('navigation menu can be rendered', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response
        ->assertOk()
        ->assertSee('Exam Activity')
        ->assertSee('Active Students')
        ->assertSeeVolt('layout.navigation');
});

test('users can logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('layout.navigation');

    $component->call('logout');

    $component
        ->assertHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
});
