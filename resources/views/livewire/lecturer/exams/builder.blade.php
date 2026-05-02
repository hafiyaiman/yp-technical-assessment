<div class="space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-950">{{ $examId ? 'Edit Exam' : 'Create Exam' }}</h1>
            <p class="mt-1 text-sm text-zinc-500">Build a timed paper with multiple-choice and open-text questions.</p>
        </div>
        <x-button text="Back to Exams" icon="arrow-left" flat :href="route('lecturer.exams.index')" navigate />
    </div>

    <x-input-error :messages="$errors->get('exam')" />

    <div class="grid gap-6 xl:grid-cols-[360px_minmax(0,1fr)]">
        <x-card>
            @php($assignment = $this->assignment())
            <div class="space-y-4">
                @if ($assignment)
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-normal text-zinc-500">Teaching assignment</p>
                        <p class="mt-2 font-semibold text-zinc-950">{{ $assignment->schoolClass->name }}</p>
                        <p class="text-sm text-zinc-600">{{ $assignment->subject->name }}</p>
                    </div>
                @endif

                <x-input wire:model="title" label="Exam title" placeholder="Midterm Paper A" />
                <x-textarea wire:model="instructions" label="Instructions" resize />

                <x-number wire:model="duration_minutes" label="Time limit in minutes" :min="1"
                    :max="240" />

                <x-input type="datetime-local" wire:model="available_from" label="Available from" />
                <x-input type="datetime-local" wire:model="available_until" label="Available until" />

                <div class="flex gap-2">
                    <x-button text="Save Draft" icon="document-check" outline wire:click="save" class="flex-1" />
                    <x-button text="Publish" icon="rocket-launch" wire:click="publish" class="flex-1" />
                </div>
            </div>
        </x-card>

        <div class="space-y-4">
            <div class="flex flex-wrap gap-2">
                <x-button text="Add Multiple Choice" icon="list-bullet" outline
                    wire:click="addQuestion('multiple_choice')" />
                <x-button text="Add Open Text" icon="pencil-square" outline wire:click="addQuestion('open_text')" />
            </div>

            @foreach ($questions as $questionIndex => $question)
                <x-card wire:key="question-{{ $questionIndex }}">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <x-badge text="Question {{ $questionIndex + 1 }}" color="gray" light />
                            <x-badge :text="$question['type'] === 'multiple_choice' ? 'Multiple choice' : 'Open text'" color="blue" light />
                        </div>
                        <x-button text="Remove" xs color="red" outline
                            wire:click="removeQuestion({{ $questionIndex }})" />
                    </div>

                    <div class="mt-4 grid gap-4">
                        <x-select.styled wire:model.live="questions.{{ $questionIndex }}.type" label="Question type">
                            <option value="multiple_choice">Multiple choice</option>
                            <option value="open_text">Open text</option>
                        </x-select.styled>

                        <x-textarea wire:model="questions.{{ $questionIndex }}.prompt" label="Prompt" resize-auto />
                        <x-number wire:model="questions.{{ $questionIndex }}.points" label="Points" :min="1"
                            :max="100" />

                        @if ($question['type'] === 'multiple_choice')
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-semibold text-zinc-900">Options</p>
                                    <x-button text="Add Option" xs outline
                                        wire:click="addOption({{ $questionIndex }})" />
                                </div>

                                @foreach ($question['options'] as $optionIndex => $option)
                                    <div class="grid gap-3 md:grid-cols-[auto_minmax(0,1fr)_auto] md:items-center"
                                        wire:key="question-{{ $questionIndex }}-option-{{ $optionIndex }}">
                                        <x-radio wire:model="questions.{{ $questionIndex }}.correct_option"
                                            value="{{ $optionIndex }}" label="Correct" sm />
                                        <x-input
                                            wire:model="questions.{{ $questionIndex }}.options.{{ $optionIndex }}.text"
                                            placeholder="Option text" />
                                        <x-button.circle icon="trash" color="red" outline
                                            wire:click="removeOption({{ $questionIndex }}, {{ $optionIndex }})" />
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </x-card>
            @endforeach
        </div>
    </div>
</div>
