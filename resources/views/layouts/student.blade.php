<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="tallstackui_darkTheme()"
    x-bind:class="{ 'dark': darkTheme }" x-bind:style="{ colorScheme: darkTheme ? 'dark' : 'light' }">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @include('layouts.partials.theme-script')
        <tallstackui:script />
        @livewireStyles
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-zinc-100 font-sans text-zinc-950 antialiased dark:bg-dark-900 dark:text-dark-100">
        <div class="min-h-screen lg:grid lg:grid-cols-[220px_minmax(0,1fr)]">
            <aside class="border-b border-zinc-200 bg-white dark:border-dark-600 dark:bg-dark-800 lg:fixed lg:inset-y-0 lg:left-0 lg:w-[220px] lg:border-b-0 lg:border-r">
                <div class="flex h-16 items-center gap-3 px-5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-zinc-950 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">YP</span>
                    <div>
                        <p class="text-sm font-semibold">Exam Portal</p>
                        <p class="text-xs text-zinc-500 dark:text-dark-300">Student</p>
                    </div>
                </div>

                <nav class="flex gap-2 px-3 pb-3 lg:block lg:space-y-1 lg:pb-0">
                    <x-button text="Home" icon="home" color="gray" flat :href="route('student.home')" navigate class="justify-start lg:w-full" />
                    <x-button text="Exam" icon="academic-cap" color="gray" flat :href="route('student.exams.index')" navigate class="justify-start lg:w-full" />
                </nav>

                <div class="hidden px-3 py-4 lg:block">
                    <x-theme-switch simple only-icons />
                </div>
            </aside>

            <main class="lg:col-start-2">
                {{ $slot }}
            </main>
        </div>

        <x-dialog />
        <x-toast />
        @livewireScripts
    </body>
</html>
