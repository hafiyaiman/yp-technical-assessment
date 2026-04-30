<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div x-data="{ sidebarOpen: false }">
    <div
        x-show="sidebarOpen"
        x-transition.opacity
        class="fixed inset-0 z-30 bg-black/30 lg:hidden"
        x-on:click="sidebarOpen = false"
        aria-hidden="true"
    ></div>

    <aside
        class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-zinc-200 bg-zinc-50 transition-transform duration-200 lg:translate-x-0"
        x-bind:class="{ 'translate-x-0': sidebarOpen }"
    >
        <div class="flex h-14 items-center gap-3 px-5">
            <span class="flex h-8 w-8 items-center justify-center rounded-full border border-zinc-300 bg-white text-sm font-bold text-zinc-900">YP</span>
            <span class="text-sm font-semibold text-zinc-950">Exam Portal</span>
        </div>

        <div class="px-4">
            <x-button text="Quick Create" icon="plus" color="dark" class="w-full justify-start" />
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-5">
            <div class="space-y-1">
                <a href="{{ route('dashboard') }}" wire:navigate class="flex h-9 items-center gap-3 rounded-md px-3 text-sm font-medium text-zinc-950 hover:bg-white">
                    <span class="h-1.5 w-1.5 rounded-full bg-zinc-950"></span>
                    Dashboard
                </a>
                <a href="{{ route('lecturer.dashboard') }}" wire:navigate class="flex h-9 items-center gap-3 rounded-md px-3 text-sm text-zinc-700 hover:bg-white hover:text-zinc-950">
                    <span class="h-1.5 w-1.5 rounded-full border border-zinc-400"></span>
                    Lecturer
                </a>
                <a href="{{ route('student.dashboard') }}" wire:navigate class="flex h-9 items-center gap-3 rounded-md px-3 text-sm text-zinc-700 hover:bg-white hover:text-zinc-950">
                    <span class="h-1.5 w-1.5 rounded-full border border-zinc-400"></span>
                    Student
                </a>
                <a href="#" class="flex h-9 items-center gap-3 rounded-md px-3 text-sm text-zinc-700 hover:bg-white hover:text-zinc-950">
                    <span class="h-1.5 w-1.5 rounded-full border border-zinc-400"></span>
                    Exams
                </a>
                <a href="#" class="flex h-9 items-center gap-3 rounded-md px-3 text-sm text-zinc-700 hover:bg-white hover:text-zinc-950">
                    <span class="h-1.5 w-1.5 rounded-full border border-zinc-400"></span>
                    Classes
                </a>
                <a href="#" class="flex h-9 items-center gap-3 rounded-md px-3 text-sm text-zinc-700 hover:bg-white hover:text-zinc-950">
                    <span class="h-1.5 w-1.5 rounded-full border border-zinc-400"></span>
                    Subjects
                </a>
            </div>

            <div class="mt-8">
                <p class="px-3 text-xs font-medium text-zinc-500">Management</p>
                <div class="mt-2 space-y-1">
                    <a href="#" class="flex h-9 items-center rounded-md px-3 text-sm text-zinc-700 hover:bg-white hover:text-zinc-950">Students</a>
                    <a href="#" class="flex h-9 items-center rounded-md px-3 text-sm text-zinc-700 hover:bg-white hover:text-zinc-950">Question Bank</a>
                    <a href="#" class="flex h-9 items-center rounded-md px-3 text-sm text-zinc-700 hover:bg-white hover:text-zinc-950">Results</a>
                    <a href="#" class="flex h-9 items-center rounded-md px-3 text-sm text-zinc-700 hover:bg-white hover:text-zinc-950">Reports</a>
                </div>
            </div>
        </nav>

        <div class="border-t border-zinc-200 p-4">
            <div class="space-y-1">
                <a href="{{ route('profile') }}" wire:navigate class="flex h-9 items-center rounded-md px-3 text-sm text-zinc-700 hover:bg-white hover:text-zinc-950">Settings</a>
                <a href="#" class="flex h-9 items-center rounded-md px-3 text-sm text-zinc-700 hover:bg-white hover:text-zinc-950">Get Help</a>
            </div>
        </div>
    </aside>

    <header class="fixed left-0 right-0 top-0 z-20 flex h-14 items-center justify-between border-b border-zinc-200 bg-white/95 px-4 backdrop-blur lg:left-64">
        <div class="flex items-center gap-3">
            <button
                type="button"
                class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-zinc-200 text-zinc-700 hover:bg-zinc-50 lg:hidden"
                x-on:click="sidebarOpen = true"
                aria-label="Open navigation"
            >
                <span class="block h-4 w-4 border-y-2 border-zinc-700"></span>
            </button>
            <div>
                <p class="text-sm font-semibold text-zinc-950">Dashboard</p>
                <p class="hidden text-xs text-zinc-500 sm:block">Online examination and student management</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <x-button text="New Exam" icon="plus" color="dark" sm />

            <x-dropdown position="bottom-end">
                <x-slot:action>
                    <button
                        type="button"
                        x-on:click="show = !show"
                        class="flex h-10 items-center gap-3 rounded-full border border-zinc-200 bg-white px-2 py-1 text-left shadow-sm hover:bg-zinc-50"
                    >
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-zinc-900 text-xs font-semibold text-white">
                            {{ str(auth()->user()->name)->substr(0, 1)->upper() }}
                        </span>
                        <span class="hidden min-w-0 sm:block">
                            <span class="block truncate text-xs font-semibold text-zinc-950">{{ auth()->user()->name }}</span>
                            <span class="block truncate text-[11px] text-zinc-500">{{ auth()->user()->roles->pluck('name')->join(', ') ?: 'User' }}</span>
                        </span>
                    </button>
                </x-slot:action>

                <x-slot:header>
                    <div class="px-2 py-1">
                        <p class="text-sm font-semibold text-zinc-950">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-zinc-500">{{ auth()->user()->email }}</p>
                    </div>
                </x-slot:header>

                <x-dropdown.items text="Profile settings" icon="user-circle" :href="route('profile')" navigate />
                <x-dropdown.items text="Dashboard" icon="squares-2x2" :href="route('dashboard')" navigate />
                <x-dropdown.items separator>
                    <button type="button" wire:click="logout" class="flex w-full items-center gap-2 text-left text-sm text-red-600">
                        <span>Log out</span>
                    </button>
                </x-dropdown.items>
            </x-dropdown>
        </div>
    </header>
</div>
