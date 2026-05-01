<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="tallstackui_darkTheme()">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <tallstackui:script />
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body x-bind:class="{ 'dark bg-gray-700': darkTheme, 'bg-white': !darkTheme }">
    <div class="min-h-screen bg-zinc-50 dark:bg-gray-800">
        <livewire:layout.navigation />

        <main class="min-h-screen pt-16 md:pl-72">
            {{ $slot }}
        </main>
    </div>

    <x-dialog />
    @livewireScripts
</body>

</html>
