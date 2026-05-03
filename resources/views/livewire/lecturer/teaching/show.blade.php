<div class="space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    @php($summary = $this->summary())

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ $assignment->schoolClass->name }}</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">
                {{ $assignment->subject->name }} / {{ $assignment->subject->code }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <x-badge text="Class code: {{ $assignment->schoolClass->code }}" color="blue" light />
            <x-button text="Create Exam" icon="plus" :href="route('lecturer.teaching.exams.create', $assignment)" navigate />
            <x-button text="Back" icon="arrow-left" color="gray" outline :href="route('lecturer.teaching.index')" navigate />
        </div>
    </div>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <x-stats animated :number="$summary['students']" title="Students" icon="users" />
        <x-stats animated :number="$summary['exams']" title="Exams" icon="document-text" color="secondary" />
        <x-stats animated :number="$summary['published']" title="Published" icon="document-check" color="green" />
        <x-stats animated :number="$summary['pendingMarking']" title="Pending Marking" icon="pencil-square" color="yellow" />
        <x-stats animated :number="$summary['joinRequests']" title="Join Requests" icon="user-plus" color="blue" />
    </section>

    <x-tab selected="students" scroll-on-mobile>
        <x-tab.items tab="students" title="Students">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-zinc-950 dark:text-white">Students</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">Read-only class roster.</p>
                </div>
                <x-badge :text="$summary['students'] . ' students'" color="gray" light />
            </div>

            <div class="mt-5 overflow-hidden rounded-md border border-zinc-200 dark:border-dark-600">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-dark-600">
                    <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase text-zinc-500 dark:bg-dark-800 dark:text-dark-300">
                        <tr>
                            <th class="px-4 py-3">Student</th>
                            <th class="px-4 py-3">Email</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 bg-white dark:divide-dark-700 dark:bg-dark-900">
                        @forelse ($this->students() as $student)
                            <tr>
                                <td class="px-4 py-4 font-medium text-zinc-950 dark:text-white">{{ $student->name }}</td>
                                <td class="px-4 py-4 text-zinc-600 dark:text-dark-300">{{ $student->email }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-dark-300">
                                    No students enrolled yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-tab.items>

        <x-tab.items tab="requests" title="Join Requests ({{ $summary['joinRequests'] }})">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-zinc-950 dark:text-white">Join Requests</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">
                        Students can request this class using code {{ $assignment->schoolClass->code }}.
                    </p>
                </div>
                <x-badge :text="$summary['joinRequests'] . ' pending'" color="blue" light />
            </div>

            <div class="mt-5 space-y-3">
                @forelse ($this->joinRequests() as $request)
                    <div class="rounded-md border border-zinc-200 p-4 dark:border-dark-600">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-semibold text-zinc-950 dark:text-white">{{ $request->student->name }}</p>
                                <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">{{ $request->student->email }}</p>
                                <p class="mt-1 text-xs text-zinc-400 dark:text-dark-300">
                                    Requested {{ $request->created_at->diffForHumans() }}
                                    @if ($request->student->schoolClass)
                                        / current class: {{ $request->student->schoolClass->name }}
                                    @endif
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <x-button text="Reject" color="red" outline wire:click="askRejectJoinRequest({{ $request->id }})" loading="askRejectJoinRequest" />
                                <x-button text="Approve" icon="check" wire:click="askApproveJoinRequest({{ $request->id }})" loading="askApproveJoinRequest" />
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-md border border-dashed border-zinc-200 px-4 py-8 text-center dark:border-dark-600">
                        <p class="font-medium text-zinc-950 dark:text-white">No pending requests</p>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">Students who enter this class code will appear here for review.</p>
                    </div>
                @endforelse
            </div>
        </x-tab.items>

        <x-tab.items tab="exams" title="Handled Exams">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-zinc-950 dark:text-white">Handled Exams</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">Exams for this class and subject.</p>
                </div>
                <x-button text="View All" sm outline :href="route('lecturer.exams.index', ['assignmentFilter' => $assignment->id])" navigate />
            </div>

            <div class="mt-5 space-y-3">
                @forelse ($this->exams() as $exam)
                    <div class="rounded-md border border-zinc-200 p-4 dark:border-dark-600">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="font-semibold text-zinc-950 dark:text-white">{{ $exam->title }}</p>
                                <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">
                                    {{ $exam->duration_minutes }} min / {{ $exam->questions_count }} questions / {{ $exam->attempts_count }} submissions
                                </p>
                            </div>
                            <x-badge :text="str($exam->status->value)->headline()" :color="$exam->status === \App\Enums\ExamStatus::Published ? 'green' : ($exam->status === \App\Enums\ExamStatus::Closed ? 'red' : 'gray')" light />
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <x-button text="Overview" sm :href="route('lecturer.exams.show', $exam)" navigate />
                            <x-button text="Submissions" sm outline :href="route('lecturer.exams.submissions', $exam)" navigate />
                        </div>
                    </div>
                @empty
                    <div class="rounded-md border border-dashed border-zinc-200 px-4 py-8 text-center dark:border-dark-600">
                        <p class="font-medium text-zinc-950 dark:text-white">No exams yet</p>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">Create an exam from this class to begin.</p>
                    </div>
                @endforelse
            </div>
        </x-tab.items>
    </x-tab>
</div>
