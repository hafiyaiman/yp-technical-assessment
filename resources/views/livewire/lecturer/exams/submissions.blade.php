<div class="space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-950">Submissions</h1>
            <p class="mt-1 text-sm text-zinc-500">{{ $exam->title }} · {{ $exam->schoolClass->name }} ·
                {{ $exam->subject->name }}</p>
        </div>
        <x-button text="Back to Exams" icon="arrow-left" flat :href="route('lecturer.exams.index')" navigate />
    </div>

    <x-lecturer.exams.tabs :exam="$exam" />

    <div class="space-y-3">
        @forelse ($this->attempts() as $attempt)
            @php $accordionId = 'attempt-' . $attempt->id; @endphp

            <x-accordion multiple>
                <x-accordion.items :id="$accordionId">
                    <x-slot:title>
                        <div class="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between pr-4">
                            <div>
                                <p class="font-semibold text-zinc-950">{{ $attempt->student->name }}</p>
                                <p class="text-sm text-zinc-500">{{ $attempt->student->email }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <x-badge :text="str($attempt->status->value)->headline()" :color="$attempt->status === \App\Enums\ExamAttemptStatus::Graded
                                    ? 'green'
                                    : ($attempt->status === \App\Enums\ExamAttemptStatus::Expired
                                        ? 'red'
                                        : 'yellow')" light />
                                <x-badge text="{{ $attempt->score }} / {{ $attempt->max_score }} marks" color="gray"
                                    light />
                                <x-badge text="{{ $attempt->percentage }}%" :color="$attempt->percentage_color" light />
                            </div>
                        </div>
                    </x-slot:title>

                    <div class="space-y-4 pt-2">
                        @foreach ($attempt->answers as $answer)
                            <div class="rounded-lg border border-zinc-200 p-4">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-zinc-950">{{ $answer->question->prompt }}
                                        </p>
                                        <p class="mt-1 text-xs text-zinc-500">{{ $answer->question->points }} mark
                                            question</p>
                                    </div>
                                    <x-badge text="{{ $answer->points_awarded }} marks" color="gray" light />
                                </div>

                                @if ($answer->question->type === \App\Enums\QuestionType::MultipleChoice)
                                    <p class="mt-3 rounded-md bg-zinc-50 px-3 py-2 text-sm text-zinc-700">
                                        Selected: {{ $answer->selectedOption?->text ?? 'No answer' }}
                                    </p>
                                @else
                                    <p class="mt-3 rounded-md bg-zinc-50 px-3 py-2 text-sm leading-6 text-zinc-700">
                                        {{ $answer->open_text_answer ?: 'No written answer.' }}
                                    </p>
                                    <div class="mt-4 grid gap-3 md:grid-cols-[160px_minmax(0,1fr)_auto] md:items-end">
                                        <x-number wire:model="points.{{ $answer->id }}" label="Marks"
                                            :min="0" :max="$answer->question->points" />
                                        <x-input wire:model="feedback.{{ $answer->id }}" label="Feedback"
                                            placeholder="Short feedback for the student" />
                                        <x-button text="Save Grade" wire:click="grade({{ $answer->id }})"
                                            loading="grade" />
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-accordion.items>
            </x-accordion>

        @empty
            <x-card>
                <div class="py-10 text-center">
                    <p class="font-semibold text-zinc-950">No submissions yet</p>
                    <p class="mt-1 text-sm text-zinc-500">Student attempts will appear here after they submit.</p>
                </div>
            </x-card>
        @endforelse
    </div>
</div>
