<div class="space-y-5 px-4 py-5 sm:px-6 lg:px-8">
    @php
        $stats = $this->stats();
        $checks = $this->setupChecks();
        $logs = $this->recentAuditLogs();
    @endphp

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-stats animated :number="$stats['users']" title="Total Users" icon="users" href="{{ route('admin.users.index') }}"
            navigate footer="{{ $stats['students'] }} students, {{ $stats['lecturers'] }}
                lecturers">
        </x-stats>

        <x-stats animated :number="$stats['classes']" title="Classes" icon="building-library" color="green"
            href="{{ route('admin.classes.index') }}" navigate
            footer="{{ $stats['classesWithoutSubjects'] }} without subjects">
        </x-stats>

        <x-stats animated :number="$stats['subjects']" title="Subjects" icon="book-open" color="yellow"
            href="{{ route('admin.subjects.index') }}" navigate
            footer="{{ $stats['subjectsWithoutClasses'] }} without classes">
        </x-stats>

        <x-stats animated :number="$stats['assignments']" title="Teaching Assignments" icon="academic-cap" color="secondary"
            footer="{{ $stats['unassignedLecturers'] }} lecturers pending setup">
        </x-stats>
    </section>

    <section class="grid gap-5 xl:grid-cols-[1.25fr_0.75fr]">
        <x-card>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-zinc-950 dark:text-white">Setup Health</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">
                        Admin checklist for privacy, class readiness, and teaching coverage.
                    </p>
                </div>
                <x-badge text="{{ $stats['exams'] }} exams in system" icon="document-text" color="gray" light />
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-2">
                @foreach ($checks as $check)
                    <a href="{{ $check['href'] }}" wire:navigate
                        class="rounded-md border border-zinc-200 p-4 transition hover:border-primary-300 hover:bg-zinc-50 dark:border-dark-600 dark:hover:border-primary-500 dark:hover:bg-dark-800">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-zinc-950 dark:text-white">{{ $check['title'] }}</p>
                                <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">{{ $check['description'] }}
                                </p>
                            </div>
                            <x-badge :text="$check['count']" :color="$check['tone']" light />
                        </div>
                    </a>
                @endforeach
            </div>
        </x-card>

        <x-card>
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-zinc-950 dark:text-white">Quick Setup</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">The common admin actions.</p>
                </div>
                <x-icon name="bolt" class="h-5 w-5 text-primary-500" />
            </div>

            <div class="mt-5 grid gap-2">
                <x-button text="Create User" icon="user-plus" href="{{ route('admin.users.index') }}" navigate />
                <x-button text="Create Class" icon="building-library" color="green" outline
                    href="{{ route('admin.classes.index') }}" navigate />
                <x-button text="Create Subject" icon="book-open" color="yellow" outline
                    href="{{ route('admin.subjects.index') }}" navigate />
            </div>
        </x-card>
    </section>

    <x-card>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">Audit Logs</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">
                    Latest admin setup changes. Use the full log page for filtering and review.
                </p>
            </div>
            <x-button text="View All Logs" icon="clipboard-document-list" outline sm
                href="{{ route('admin.audit-logs.index') }}" navigate />
        </div>

        <div class="mt-5 overflow-hidden rounded-md border border-zinc-200 dark:border-dark-600">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-dark-600">
                <thead
                    class="bg-zinc-50 text-left text-xs font-semibold uppercase text-zinc-500 dark:bg-dark-800 dark:text-dark-300">
                    <tr>
                        <th class="px-4 py-3">Action</th>
                        <th class="px-4 py-3">Description</th>
                        <th class="px-4 py-3">Actor</th>
                        <th class="px-4 py-3">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white dark:divide-dark-700 dark:bg-dark-900">
                    @forelse ($logs as $log)
                        <tr>
                            <td class="px-4 py-4">
                                <x-badge :text="str($log->action)->headline()" color="gray" light />
                            </td>
                            <td class="px-4 py-4 font-medium text-zinc-950 dark:text-white">{{ $log->description }}
                            </td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-dark-300">{{ $log->actor?->name ?? 'System' }}
                            </td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-dark-300">
                                {{ $log->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-dark-300">
                                No audit logs yet. Admin changes will appear here.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
