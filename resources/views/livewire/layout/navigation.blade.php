<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component {
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }

    public function breadcrumbs(): array
    {
        $items = [['label' => 'Dashboard', 'link' => route('dashboard'), 'icon' => 'home']];

        if (request()->routeIs('admin.users.*')) {
            return [...$items, ['label' => 'Users']];
        }

        if (request()->routeIs('admin.classes.*')) {
            return [...$items, ['label' => 'Classes']];
        }

        if (request()->routeIs('admin.subjects.*')) {
            return [...$items, ['label' => 'Subjects']];
        }

        if (request()->routeIs('admin.audit-logs.*')) {
            return [...$items, ['label' => 'Audit Logs']];
        }

        if (request()->routeIs('admin.teaching-assignments.*')) {
            return [...$items, ['label' => 'Teaching Assignments']];
        }

        if (request()->routeIs('lecturer.teaching.index')) {
            return [...$items, ['label' => 'My Classes']];
        }

        if (request()->routeIs('lecturer.teaching.exams.create')) {
            return [...$items, ['label' => 'Exams', 'link' => route('lecturer.exams.index')], ['label' => 'Create']];
        }

        if (request()->routeIs('lecturer.exams.edit')) {
            return [...$items, ['label' => 'Exams', 'link' => route('lecturer.exams.index')], ['label' => 'Builder']];
        }

        if (request()->routeIs('lecturer.exams.submissions')) {
            return [...$items, ['label' => 'Exams', 'link' => route('lecturer.exams.index')], ['label' => 'Submissions']];
        }

        if (request()->routeIs('lecturer.exams.*')) {
            return [...$items, ['label' => 'Exams']];
        }

        if (request()->routeIs('profile')) {
            return [...$items, ['label' => 'Profile']];
        }

        return $items;
    }
}; ?>

<div x-data="{ tallStackUiMenuMobile: false }">
    <x-side-bar thin-scroll>
        <x-slot:brand>
            <div class="flex h-16 items-center gap-3 px-5">
                <span
                    class="flex h-9 w-9 items-center justify-center rounded-full border border-zinc-300 bg-zinc-950 text-sm font-bold text-white">EP</span>
                <div>
                    <p class="text-sm font-semibold text-zinc-950 dark:text-white">Exam Portal</p>
                </div>
            </div>
        </x-slot:brand>

        <x-side-bar.item text="Dashboard" :href="route(
            auth()->user()->hasRole('system-admin')
                ? 'admin.dashboard'
                : (auth()->user()->hasRole('lecturer')
                    ? 'lecturer.dashboard'
                    : 'student.home'),
        )" icon="squares-2x2" :current="request()->routeIs(
            'dashboard',
            'admin.dashboard',
            'lecturer.dashboard',
            'student.dashboard',
            'student.home',
        )" wire:navigate />

        @if (auth()->user()->hasRole('system-admin'))
            <x-side-bar.separator text="Admin" />
            <x-side-bar.item text="Users" :href="route('admin.users.index')" icon="users" :current="request()->routeIs('admin.users.*')" wire:navigate />
            <x-side-bar.item text="Classes" :href="route('admin.classes.index')" icon="building-library" :current="request()->routeIs('admin.classes.*')" wire:navigate />
            <x-side-bar.item text="Subjects" :href="route('admin.subjects.index')" icon="book-open" :current="request()->routeIs('admin.subjects.*')" wire:navigate />
            <x-side-bar.item text="Audit Logs" :href="route('admin.audit-logs.index')" icon="clipboard-document-list" :current="request()->routeIs('admin.audit-logs.*')" wire:navigate />
        @endif

        @if (auth()->user()->hasPermission('manage-exams'))
            <x-side-bar.separator text="Lecturer" />
            <x-side-bar.item text="My Classes" :href="route('lecturer.teaching.index')" icon="academic-cap" :current="request()->routeIs('lecturer.teaching.*')" wire:navigate />
            <x-side-bar.item text="Exams" :href="route('lecturer.exams.index')" icon="document-text" :current="request()->routeIs('lecturer.exams.*')" wire:navigate />
            <x-side-bar.item text="Marking" :href="route('lecturer.exams.index')" icon="pencil-square" wire:navigate />
            <x-side-bar.item text="Results" :href="route('lecturer.exams.index')" icon="chart-bar" wire:navigate />
        @endif

        @if (auth()->user()->hasPermission('take-exams'))
            <x-side-bar.separator text="Student" />
            <x-side-bar.item text="My Exams" :href="route('student.exams.index')" icon="academic-cap" :current="request()->routeIs('student.exams.*')" wire:navigate />
            <x-side-bar.item text="Results" :href="route('student.results.index')" icon="chart-bar-square" :current="request()->routeIs('student.results.*')"
                wire:navigate />
        @endif

        <x-slot:footer>
            <div class="space-y-1">
                <x-side-bar.item text="Get Help" href="#" icon="question-mark-circle" />
            </div>
        </x-slot:footer>
    </x-side-bar>

    <header
        class="fixed left-0 right-0 top-0 z-20 flex h-16 items-center justify-between border-b border-zinc-200 bg-white/95 px-4 backdrop-blur dark:border-dark-600 dark:bg-dark-900 md:left-72">
        <div class="flex items-center gap-3">
            <x-button.circle icon="bars-3" color="gray" outline x-on:click="tallStackUiMenuMobile = true"
                class="md:hidden" aria-label="Open navigation" />
            <x-breadcrumbs :items="$this->breadcrumbs()" separator="icon:chevron-right" sm />
        </div>

        <div class="flex items-center gap-3">
            <x-theme-switch simple only-icons />

            <x-dropdown position="bottom-end">
                <x-slot:action>
                    <button type="button" x-on:click="show = !show"
                        class="flex items-center gap-3 rounded-full border border-zinc-200 bg-white text-left shadow-sm hover:bg-zinc-50 dark:border-dark-600 dark:bg-dark-700 dark:hover:bg-dark-600">
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-zinc-900 text-xs font-semibold text-white">
                            {{ str(auth()->user()->name)->substr(0, 1)->upper() }}
                        </span>
                    </button>
                </x-slot:action>

                <x-slot:header>
                    <div class="px-2 py-1">
                        <p class="text-sm font-semibold text-zinc-950 dark:text-white">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-zinc-500 dark:text-dark-300">{{ auth()->user()->email }}</p>
                    </div>
                </x-slot:header>

                <x-dropdown.items text="Profile settings" icon="user-circle" :href="route('profile')" navigate />
                <x-dropdown.items text="Log out" icon="arrow-left-start-on-rectangle" wire:click="logout" navigate
                    separator />
            </x-dropdown>
        </div>
    </header>
</div>
