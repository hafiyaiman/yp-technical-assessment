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

<body class="bg-zinc-50 font-sans text-zinc-950 antialiased dark:bg-dark-900 dark:text-dark-100">
    <div class="min-h-screen bg-zinc-50 dark:bg-dark-900">
        <livewire:layout.navigation />

        <main class="min-h-screen pt-16 md:pl-72 dark:bg-dark-900">
            {{ $slot }}
        </main>
    </div>

    <x-dialog />
    <x-toast />
    @livewireScripts
</body>

</html>
