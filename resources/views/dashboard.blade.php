<x-app-layout>
    <div class="space-y-5 px-4 py-5 sm:px-6 lg:px-8">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-card>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-zinc-500">Active Students</p>
                        <p class="mt-2 text-3xl font-semibold tracking-normal text-zinc-950">1,250</p>
                    </div>
                    <x-badge text="+12.5%" icon="arrow-trending-up" color="green" light />
                </div>
                <div class="mt-5 space-y-1">
                    <p class="text-sm font-semibold text-zinc-950">Attendance trending up</p>
                    <p class="text-sm text-zinc-500">Active learners in the last 30 days</p>
                </div>
            </x-card>

            <x-card>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-zinc-500">Open Exams</p>
                        <p class="mt-2 text-3xl font-semibold tracking-normal text-zinc-950">48</p>
                    </div>
                    <x-badge text="-4%" icon="arrow-trending-down" color="yellow" light />
                </div>
                <div class="mt-5 space-y-1">
                    <p class="text-sm font-semibold text-zinc-950">Fewer pending sessions</p>
                    <p class="text-sm text-zinc-500">Exam windows closing this week</p>
                </div>
            </x-card>

            <x-card>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-zinc-500">Submissions</p>
                        <p class="mt-2 text-3xl font-semibold tracking-normal text-zinc-950">45,678</p>
                    </div>
                    <x-badge text="+18.2%" icon="arrow-trending-up" color="green" light />
                </div>
                <div class="mt-5 space-y-1">
                    <p class="text-sm font-semibold text-zinc-950">Strong completion rate</p>
                    <p class="text-sm text-zinc-500">Across all assigned classes</p>
                </div>
            </x-card>

            <x-card>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-zinc-500">Average Score</p>
                        <p class="mt-2 text-3xl font-semibold tracking-normal text-zinc-950">82.4%</p>
                    </div>
                    <x-badge text="+4.5%" icon="arrow-trending-up" color="green" light />
                </div>
                <div class="mt-5 space-y-1">
                    <p class="text-sm font-semibold text-zinc-950">Performance increase</p>
                    <p class="text-sm text-zinc-500">Meets subject progression targets</p>
                </div>
            </x-card>
        </section>

        <x-card>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-zinc-950">Exam Activity</h2>
                    <p class="mt-1 text-sm text-zinc-500">Total submissions for the last 3 months</p>
                </div>
                <div class="inline-flex w-fit rounded-md border border-zinc-200 bg-zinc-50 p-1 text-sm">
                    <x-button text="Last 3 months" color="gray" xs />
                    <x-button text="Last 30 days" color="gray" xs flat />
                    <x-button text="Last 7 days" color="gray" xs flat />
                </div>
            </div>

            <div class="mt-8 overflow-hidden">
                <svg viewBox="0 0 900 230" class="h-64 w-full" role="img" aria-label="Area chart showing exam submission activity increasing across the quarter">
                    <defs>
                        <linearGradient id="activityFill" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="#18181b" stop-opacity="0.5" />
                            <stop offset="100%" stop-color="#18181b" stop-opacity="0.05" />
                        </linearGradient>
                    </defs>
                    <g class="text-zinc-200" stroke="currentColor" stroke-width="1">
                        <line x1="0" y1="35" x2="900" y2="35" />
                        <line x1="0" y1="80" x2="900" y2="80" />
                        <line x1="0" y1="125" x2="900" y2="125" />
                        <line x1="0" y1="170" x2="900" y2="170" />
                    </g>
                    <path d="M0,160 C15,190 30,105 45,95 C60,85 72,180 85,80 C100,190 112,95 125,105 C140,60 150,195 165,130 C180,125 195,80 210,70 C225,105 240,170 255,120 C270,95 285,185 300,65 C315,150 330,55 345,135 C360,110 375,190 390,95 C405,40 420,160 435,90 C450,85 465,175 480,125 C495,75 510,45 525,140 C540,65 555,185 570,120 C585,115 600,165 615,70 C630,155 645,95 660,175 C675,55 690,165 705,75 C720,150 735,95 750,80 C765,145 780,60 795,90 C810,145 825,50 840,115 C855,70 870,160 885,85 C895,60 900,120 900,120 L900,220 L0,220 Z" fill="url(#activityFill)" />
                    <path d="M0,160 C15,190 30,105 45,95 C60,85 72,180 85,80 C100,190 112,95 125,105 C140,60 150,195 165,130 C180,125 195,80 210,70 C225,105 240,170 255,120 C270,95 285,185 300,65 C315,150 330,55 345,135 C360,110 375,190 390,95 C405,40 420,160 435,90 C450,85 465,175 480,125 C495,75 510,45 525,140 C540,65 555,185 570,120 C585,115 600,165 615,70 C630,155 645,95 660,175 C675,55 690,165 705,75 C720,150 735,95 750,80 C765,145 780,60 795,90 C810,145 825,50 840,115 C855,70 870,160 885,85 C895,60 900,120 900,120" fill="none" stroke="#52525b" stroke-width="2" />
                    <path d="M0,185 C25,170 40,145 58,150 C80,130 92,210 110,145 C130,165 145,125 160,155 C185,180 195,120 210,145 C235,185 250,155 268,160 C290,115 305,175 320,125 C340,170 355,130 375,150 C395,160 410,115 428,150 C455,170 470,120 490,140 C515,155 530,100 548,140 C570,150 588,125 605,165 C630,145 645,175 660,125 C682,170 695,135 710,155 C735,110 750,180 765,125 C785,145 800,105 818,150 C840,130 855,175 872,135 C890,170 900,125 900,125" fill="none" stroke="#18181b" stroke-width="1.5" />
                    <g class="text-xs fill-zinc-500">
                        <text x="50" y="225">Apr 7</text>
                        <text x="185" y="225">Apr 21</text>
                        <text x="315" y="225">May 5</text>
                        <text x="455" y="225">May 19</text>
                        <text x="595" y="225">Jun 2</text>
                        <text x="730" y="225">Jun 16</text>
                        <text x="855" y="225">Jun 30</text>
                    </g>
                </svg>
            </div>
        </x-card>

        <section class="space-y-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap gap-2">
                    <x-badge text="Overview"   />
                    <x-badge text="Past Performance 3" color="gray" light />
                    <x-badge text="Key Personnel 2" color="gray" light />
                    <x-badge text="Focus Documents" color="gray" light />
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-button text="Customize Columns" icon="adjustments-horizontal" outline sm />
                    <x-button text="Add Section" icon="plus"   sm />
                </div>
            </div>

            <x-card>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm">
                        <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-normal text-zinc-500">
                            <tr>
                                <th class="px-4 py-3">Exam</th>
                                <th class="px-4 py-3">Subject</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Class</th>
                                <th class="px-4 py-3">Time Limit</th>
                                <th class="px-4 py-3">Owner</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 bg-white">
                            <tr>
                                <td class="px-4 py-4 font-medium text-zinc-950">Midterm Paper A</td>
                                <td class="px-4 py-4 text-zinc-600">Mathematics</td>
                                <td class="px-4 py-4"><x-badge text="In Process" color="yellow" light /></td>
                                <td class="px-4 py-4 text-zinc-600">Class 4A</td>
                                <td class="px-4 py-4 text-zinc-600">15 min</td>
                                <td class="px-4 py-4 text-zinc-600">Eddie Lake</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-4 font-medium text-zinc-950">Grammar Quiz</td>
                                <td class="px-4 py-4 text-zinc-600">English</td>
                                <td class="px-4 py-4"><x-badge text="Done" color="green" light /></td>
                                <td class="px-4 py-4 text-zinc-600">Class 3B</td>
                                <td class="px-4 py-4 text-zinc-600">20 min</td>
                                <td class="px-4 py-4 text-zinc-600">Eddie Lake</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-4 font-medium text-zinc-950">Science Open Text</td>
                                <td class="px-4 py-4 text-zinc-600">Science</td>
                                <td class="px-4 py-4"><x-badge text="Draft" color="gray" light /></td>
                                <td class="px-4 py-4 text-zinc-600">Class 2C</td>
                                <td class="px-4 py-4 text-zinc-600">30 min</td>
                                <td class="px-4 py-4 text-zinc-600">Eddie Lake</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </x-card>
        </section>
    </div>
</x-app-layout>
