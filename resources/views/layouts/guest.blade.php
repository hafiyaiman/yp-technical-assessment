<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="tallstackui_darkTheme()" x-bind:class="{ 'dark': darkTheme }"
    x-bind:style="{ colorScheme: darkTheme ? 'dark' : 'light' }">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @include('layouts.partials.theme-script')
    <tallstackui:script />
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans text-gray-900 antialiased dark:bg-dark-900 dark:text-dark-100">
    <div class="fixed right-4 top-4 z-10">
        <x-theme-switch simple only-icons />

    </div>

    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-dark-900">
        <div>
            <a href="/" wire:navigate>
                <x-application-logo class="w-20 h-20 fill-current text-gray-500 dark:text-dark-300" />
            </a>
        </div>

        <div
            class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md ring-1 ring-transparent overflow-hidden sm:rounded-lg dark:bg-dark-800 dark:ring-dark-600">
            {{ $slot }}

        </div>
        <div class="flex justify-center items-center pt-8 gap-2">
            @if (request()->routeIs('login'))
                <p>Don't have an account?</p>
                <a class="underline text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    href="{{ route('register') }}" wire:navigate>
                    {{ __('Register here') }}
                </a>
            @elseif (request()->routeIs('register'))
                <p>Already have an account?</p>
                <a class="underline text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    href="{{ route('login') }}" wire:navigate>
                    {{ __('Login here') }}
                </a>
            @endif
        </div>
    </div>

    <x-dialog />
    <x-toast />
    @livewireScripts
</body>

</html>
