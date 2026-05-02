<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;
use TallStackUi\Traits\Interactions;

new class extends Component
{
    use Interactions;

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->toast()->success('Password updated.')->send();
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Update Password') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form wire:submit="updatePassword" class="mt-6 space-y-6">
        <x-password wire:model="current_password" label="{{ __('Current Password') }}" autocomplete="current-password" />

        <div>
            <x-password wire:model="password" label="{{ __('New Password') }}" autocomplete="new-password" :rules="true" />
        </div>

        <div>
            <x-password wire:model="password_confirmation" label="{{ __('Confirm Password') }}" autocomplete="new-password" />
        </div>

        <div class="flex items-center gap-4">
            <x-button type="submit" text="{{ __('Save') }}" loading="updatePassword" />

        </div>
    </form>
</section>
