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
use TallStackUi\Traits\Interactions;

new #[Layout('layouts.guest')] class extends Component {
    use Interactions;

    #[Validate('required|digits:6')]
    public string $code = '';

    public function mount(): void
    {
        if (!session()->has(LoginOtpService::SESSION_USER_ID)) {
            $this->redirect(route('login'), navigate: true);
        }
    }

    public function verify(): void
    {
        $this->validate();

        $userId = session(LoginOtpService::SESSION_USER_ID);

        $otp = LoginOtp::query()->where('user_id', $userId)->whereNull('consumed_at')->latest()->first();

        if ($otp === null || $otp->isExpired() || $otp->attempts >= 5) {
            $this->clearPendingLogin();

            throw ValidationException::withMessages([
                'code' => __('The verification code has expired. Please sign in again.'),
            ]);
        }

        if (!$otp->matches($this->code)) {
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

        $this->toast()->success('Verification code sent.', 'A new verification code has been sent.')->send();
    }

    public function cancel(): void
    {
        $this->clearPendingLogin();

        $this->redirect(route('login'), navigate: true);
    }

    private function clearPendingLogin(): void
    {
        session()->forget([LoginOtpService::SESSION_USER_ID, LoginOtpService::SESSION_REMEMBER]);
    }
}; ?>

<div>
    <form wire:submit="verify">
        <div class="mb-6">
            <h2 class="text-lg font-medium text-gray-900 text-center">
                {{ __('Verify your email address') }}
            </h2>
            <p class="text-sm text-gray-600 text-center">
                Enter the verification code sent to your email address.
            </p>
        </div>
        <div class="w-fit justify-center items-center mx-auto">
            <x-pin wire:model.live="code" length="6" numbers clear invalidate />
        </div>
        <div class="pt-6 pb-4 flex justify-center items-center">
            <x-button type="button" text="{{ __('Resend code') }}" flat sm wire:click="resend" loading="resend" />
        </div>

        <div class="flex flex-col mt-4 gap-2">
            <x-button type="submit" text="{{ __('Verify') }}" loading="verify" />
            <x-button type="button" text="{{ __('Cancel') }}" flat wire:click="cancel" loading="cancel" />
        </div>
    </form>
</div>
