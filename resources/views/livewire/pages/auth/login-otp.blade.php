<?php

use App\Models\LoginOtp;
use App\Models\User;
use App\Services\Auth\LoginOtpService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    #[Validate('required|digits:6')]
    public string $code = '';

    public function mount(): void
    {
        if (! session()->has(LoginOtpService::SESSION_USER_ID)) {
            $this->redirect(route('login'), navigate: true);
        }
    }

    public function verify(): void
    {
        $this->validate();

        $userId = session(LoginOtpService::SESSION_USER_ID);

        $otp = LoginOtp::query()
            ->where('user_id', $userId)
            ->whereNull('consumed_at')
            ->latest()
            ->first();

        if ($otp === null || $otp->isExpired() || $otp->attempts >= 5) {
            $this->clearPendingLogin();

            throw ValidationException::withMessages([
                'code' => __('The verification code has expired. Please sign in again.'),
            ]);
        }

        if (! $otp->matches($this->code)) {
            $otp->increment('attempts');

            throw ValidationException::withMessages([
                'code' => __('The verification code is invalid.'),
            ]);
        }

        $otp->update(['consumed_at' => now()]);

        $remember = (bool) session(LoginOtpService::SESSION_REMEMBER, false);
        $user = User::query()->findOrFail($userId);

        Auth::login($user, $remember);
        $this->clearPendingLogin();
        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }

    public function resend(LoginOtpService $otps): void
    {
        $user = User::query()->findOrFail(session(LoginOtpService::SESSION_USER_ID));

        $otps->send($user, (bool) session(LoginOtpService::SESSION_REMEMBER, false));

        session()->flash('status', __('A new verification code has been sent.'));
    }

    public function cancel(): void
    {
        $this->clearPendingLogin();

        $this->redirect(route('login'), navigate: true);
    }

    private function clearPendingLogin(): void
    {
        session()->forget([
            LoginOtpService::SESSION_USER_ID,
            LoginOtpService::SESSION_REMEMBER,
        ]);
    }
}; ?>

<div>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="verify">
        <x-pin
            wire:model.live="code"
            label="{{ __('Verification code') }}"
            hint="{{ __('Enter the 6-digit code sent to your email.') }}"
            length="6"
            numbers
            clear
            invalidate
        />

        <div class="flex items-center justify-between mt-4">
            <div class="flex items-center gap-4">
                <button type="button" wire:click="resend" class="underline text-sm text-gray-600 hover:text-gray-900">
                    {{ __('Resend code') }}
                </button>

                <button type="button" wire:click="cancel" class="underline text-sm text-gray-600 hover:text-gray-900">
                    {{ __('Cancel login') }}
                </button>
            </div>

            <x-primary-button>
                {{ __('Verify') }}
            </x-primary-button>
        </div>
    </form>
</div>
