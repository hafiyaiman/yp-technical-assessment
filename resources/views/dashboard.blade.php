<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            <x-alert
                title="TallStack UI is active"
                text="Breeze auth, Livewire, and TallStack UI are rendering together."
                color="green"
                icon="check-circle"
                light
            />

            <x-card header="Application setup">
                <div class="space-y-4">
                    <p class="text-sm text-gray-700">
                        {{ __("You're logged in. Supabase is configured through environment variables for PostgreSQL and S3-compatible storage.") }}
                    </p>

                    <div class="flex flex-wrap gap-2">
                        <x-badge text="Laravel 11" color="red" light />
                        <x-badge text="Breeze" color="gray" light />
                        <x-badge text="Livewire" color="pink" light />
                        <x-badge text="TallStack UI v3" color="primary" light />
                        <x-badge text="Supabase ready" color="green" light />
                    </div>
                </div>

                <x-slot:footer>
                    <x-button text="Manage profile" :href="route('profile')" color="primary" />
                </x-slot:footer>
            </x-card>
        </div>
    </div>
</x-app-layout>
