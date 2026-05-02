<div class="space-y-5 px-4 py-5 sm:px-6 lg:px-8">
    @php
        $logs = $this->logs();
    @endphp

    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-normal text-zinc-950 dark:text-white">Audit Logs</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">
                Track admin CRUD changes across users, classes, subjects, and teaching assignments.
            </p>
        </div>
        <x-badge text="{{ $logs->total() }} records" icon="clipboard-document-list" color="gray" light />
    </div>

    <x-card>
        <div class="grid gap-3 lg:grid-cols-[1fr_24rem]">
            <div>
                <x-input wire:model.live.debounce.400ms="search" label="Search" icon="magnifying-glass"
                    placeholder="Description, action, actor name, or email" />
            </div>
            <div class="ts-select-compact">
                <x-select.styled wire:model.live="actionFilters" label="Actions" :options="$this->actionOptions()"
                    placeholder="All actions" searchable multiple />
            </div>
        </div>
    </x-card>

    <x-card>
        <div class="overflow-hidden rounded-md border border-zinc-200 dark:border-dark-600">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-dark-600">
                <thead
                    class="bg-zinc-50 text-left text-xs font-semibold uppercase text-zinc-500 dark:bg-dark-800 dark:text-dark-300">
                    <tr>
                        <th class="px-4 py-3">Time</th>
                        <th class="px-4 py-3">Action</th>
                        <th class="px-4 py-3">Description</th>
                        <th class="px-4 py-3">Actor</th>
                        <th class="px-4 py-3">Target</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white dark:divide-dark-700 dark:bg-dark-900">
                    @forelse ($logs as $log)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-4 text-zinc-600 dark:text-dark-300">
                                {{ $log->created_at->format('M j, Y g:i A') }}
                            </td>
                            <td class="px-4 py-4">
                                <x-badge :text="str($log->action)->headline()" color="gray" light />
                            </td>
                            <td class="px-4 py-4 font-medium text-zinc-950 dark:text-white">
                                {{ $log->description }}
                            </td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-dark-300">
                                <div>{{ $log->actor?->name ?? 'System' }}</div>
                                @if ($log->actor?->email)
                                    <div class="text-xs text-zinc-500 dark:text-dark-300">{{ $log->actor->email }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-dark-300">
                                {{ str($log->subject_type ?? 'none')->afterLast('\\')->headline() }}
                                @if ($log->subject_id)
                                    <span class="text-zinc-400 dark:text-dark-400">#{{ $log->subject_id }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-dark-300">
                                No audit logs found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    </x-card>
</div>
