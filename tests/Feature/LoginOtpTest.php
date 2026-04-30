<?php

use App\Models\LoginOtp;
use App\Models\User;
use App\Notifications\LoginOtpNotification;
use App\Services\Auth\LoginOtpService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;

test('otp screen redirects to login without a pending login session', function (): void {
    $this->get(route('login.otp'))->assertRedirect(route('login'));
});

test('otp can be resent for a pending login', function (): void {
    Notification::fake();

    $user = User::factory()->create();

    session([
        LoginOtpService::SESSION_USER_ID => $user->id,
        LoginOtpService::SESSION_REMEMBER => true,
    ]);

    Volt::test('pages.auth.login-otp')
        ->call('resend')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('login_otps', ['user_id' => $user->id, 'consumed_at' => null]);
    Notification::assertSentTo($user, LoginOtpNotification::class);
});

test('invalid otp increments attempts and keeps user unauthenticated', function (): void {
    $user = User::factory()->create();
    $otp = LoginOtp::query()->create([
        'user_id' => $user->id,
        'code_hash' => Hash::make('123456'),
        'expires_at' => now()->addMinutes(10),
    ]);

    session([
        LoginOtpService::SESSION_USER_ID => $user->id,
        LoginOtpService::SESSION_REMEMBER => false,
    ]);

    Volt::test('pages.auth.login-otp')
        ->set('code', '654321')
        ->call('verify')
        ->assertHasErrors(['code']);

    expect($otp->fresh()->attempts)->toBe(1);
    $this->assertGuest();
});

test('expired otp clears the pending login session', function (): void {
    $user = User::factory()->create();

    LoginOtp::query()->create([
        'user_id' => $user->id,
        'code_hash' => Hash::make('123456'),
        'expires_at' => now()->subMinute(),
    ]);

    session([
        LoginOtpService::SESSION_USER_ID => $user->id,
        LoginOtpService::SESSION_REMEMBER => false,
    ]);

    Volt::test('pages.auth.login-otp')
        ->set('code', '123456')
        ->call('verify')
        ->assertHasErrors(['code']);

    expect(session()->has(LoginOtpService::SESSION_USER_ID))->toBeFalse();
    $this->assertGuest();
});

test('pending otp login can be cancelled', function (): void {
    $user = User::factory()->create();

    session([
        LoginOtpService::SESSION_USER_ID => $user->id,
        LoginOtpService::SESSION_REMEMBER => false,
    ]);

    Volt::test('pages.auth.login-otp')
        ->call('cancel')
        ->assertRedirect(route('login', absolute: false));

    expect(session()->has(LoginOtpService::SESSION_USER_ID))->toBeFalse();
    $this->assertGuest();
});
