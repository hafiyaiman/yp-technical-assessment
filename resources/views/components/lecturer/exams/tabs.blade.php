@props([
    'exam',
])

@php($examKey = $exam instanceof \App\Models\Exam ? $exam->getKey() : $exam)

<div wire:ignore wire:key="lecturer-exam-tabs-{{ $examKey }}">
    <x-tab scroll-on-mobile>
        <x-tab.items tab="overview" title="Overview" :href="route('lecturer.exams.show', $exam)" navigate />
        <x-tab.items tab="builder" title="Builder" :href="route('lecturer.exams.edit', $exam)" navigate />
        <x-tab.items tab="submissions" title="Submissions" :href="route('lecturer.exams.submissions', $exam)" navigate />
        <x-tab.items tab="results" title="Results" :href="route('lecturer.exams.results', $exam)" navigate />
        <x-tab.items tab="activity" title="Activity History" :href="route('lecturer.exams.activity', $exam)" navigate />
    </x-tab>
</div>
