<x-app-layout>
    <div class="space-y-5 px-4 py-5 sm:px-6 lg:px-8">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-card>
                <p class="text-sm text-zinc-500 dark:text-dark-300">Active Students</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-zinc-950 dark:text-white">1,250</p>
            </x-card>

            <x-card>
                <p class="text-sm text-zinc-500 dark:text-dark-300">Open Exams</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-zinc-950 dark:text-white">48</p>
            </x-card>
        </section>

        <x-card>
            <h2 class="text-base font-semibold text-zinc-950 dark:text-white">Exam Activity</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">Total submissions for the last 3 months</p>
        </x-card>
    </div>
</x-app-layout>
