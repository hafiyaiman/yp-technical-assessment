<?php

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;
use TallStackUi\Traits\Interactions;

new #[Layout('layouts.guest')] class extends Component
{
    use Interactions;

    #[Locked]
    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public bool $invitation = false;

    /**
     * Mount the component.
     */
    public function mount(string $token): void
    {
        $this->token = $token;

        $this->email = request()->string('email');
        $this->invitation = request()->boolean('invitation');
    }

    /**
     * Reset the password for the given user.
     */
    public function resetPassword(): void
    {
        $this->validate([
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $this->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) {
                $user->forceFill([
                    'password' => Hash::make($this->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        if ($status != Password::PASSWORD_RESET) {
            $this->addError('email', __($status));

            return;
        }

        $this->toast()->success($this->invitation ? 'Password set.' : 'Password reset.', __($status))->flash()->send();

        $this->redirectRoute('login', navigate: true);
    }
}; ?>

<div>
    <form wire:submit="resetPassword">
        <!-- Email Address -->
        <x-input wire:model="email" label="{{ __('Email') }}" type="email" required autofocus autocomplete="username" />

        <!-- Password -->
        <div class="mt-4">
            <x-password wire:model="password" label="{{ __('Password') }}" required autocomplete="new-password" :rules="true" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-password wire:model="password_confirmation" label="{{ __('Confirm Password') }}" required autocomplete="new-password" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-button type="submit" text="{{ $invitation ? __('Set Password') : __('Reset Password') }}" />
        </div>
    </form>
</div>
