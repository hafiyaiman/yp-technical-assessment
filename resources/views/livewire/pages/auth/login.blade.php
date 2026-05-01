<?php

use App\Livewire\Forms\LoginForm;
use App\Services\Auth\LoginOtpService;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(LoginOtpService $otps): void
    {
        $this->validate();

        $user = $this->form->authenticate();

        Session::regenerate();

        $otps->send($user, $this->form->remember);

        $this->redirect(route('login.otp'), navigate: true);
    }
}; ?>

<div>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login">
        <!-- Email Address -->
        <x-input wire:model="form.email" label="{{ __('Email') }}" type="email" required autofocus autocomplete="username" />

        <!-- Password -->
        <div class="mt-4">
            <x-password wire:model="form.password" label="{{ __('Password') }}" required autocomplete="current-password" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <x-checkbox wire:model="form.remember" :label="__('Remember me')" />
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}" wire:navigate>
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-button type="submit" text="{{ __('Log in') }}" class="ms-3" />
        </div>
    </form>
</div>
