<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered($user = User::create($validated)));

        $user->assignRole('student');

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <form wire:submit="register">
        <!-- Name -->
        <x-input wire:model="name" label="{{ __('Name') }}" required autofocus autocomplete="name" />

        <!-- Email Address -->
        <div class="mt-4">
            <x-input wire:model="email" label="{{ __('Email') }}" type="email" required autocomplete="username" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-password wire:model="password" label="{{ __('Password') }}" required autocomplete="new-password" :rules="true" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-password wire:model="password_confirmation" label="{{ __('Confirm Password') }}" required autocomplete="new-password" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}" wire:navigate>
                {{ __('Already registered?') }}
            </a>

            <x-button type="submit" text="{{ __('Register') }}" class="ms-4" />
        </div>
    </form>
</div>
