<x-layouts::app :title="'SQL console · Database'">
    @php
        $th = 'whitespace-nowrap px-3 py-2 text-left font-mono text-xs font-semibold text-zinc-500';
        $td = 'whitespace-nowrap px-3 py-2 align-top font-mono text-xs';
    @endphp

    <div class="relative mx-auto w-full max-w-7xl px-4 pb-10 pt-2">
        @include('admin.database._header', [
            'title' => 'SQL Console',
            'crumbs' => ['Database' => route('admin.database.index'), 'SQL console' => null],
        ])

        <div class="grid gap-6 lg:grid-cols-[1fr_280px]">
            <form method="POST" action="{{ route('admin.database.query') }}" class="min-w-0">
                @csrf
                <textarea name="sql" id="sql" rows="9" spellcheck="false"
                          class="w-full rounded-2xl border border-zinc-300 bg-zinc-950 p-4 font-mono text-sm leading-relaxed text-zinc-100 shadow-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-500/30 dark:border-zinc-700">{{ $sql }}</textarea>
                @error('sql')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror

                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <button type="submit"
                            class="inline-flex h-11 items-center gap-2 rounded-xl bg-red-600 px-6 text-sm font-semibold text-white shadow-sm transition hover:bg-red-500">
                        <flux:icon.play class="size-4" />
                        Run query
                    </button>
                    <span class="text-xs text-zinc-500">Read-only: SELECT, WITH, PRAGMA, EXPLAIN, SHOW, DESCRIBE. Max 500 rows shown.</span>
                </div>
            </form>

            <aside class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-2 text-sm font-semibold text-zinc-900 dark:text-white">Example queries</div>
                <div class="flex flex-col gap-1.5">
                    @foreach ($examples as $label => $example)
                        <button type="button"
                                data-sql="{{ $example }}"
                                onclick="document.getElementById('sql').value = this.dataset.sql; document.getElementById('sql').focus();"
                                class="rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-red-50 hover:text-red-700 dark:text-zinc-300 dark:hover:bg-red-950/40 dark:hover:text-red-300">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </aside>
        </div>

        @if ($error)
            <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">
                <div class="font-semibold">Query failed</div>
                <div class="mt-1 font-mono text-xs">{{ $error }}</div>
            </div>
        @endif

        @if ($result)
            <section class="mt-6 overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-3 text-sm dark:border-zinc-800">
                    <span class="font-semibold text-zinc-900 dark:text-white">
                        {{ number_format($result['count']) }} {{ \Illuminate\Support\Str::plural('row', $result['count']) }}
                        @if ($result['truncated'])<span class="font-normal text-zinc-500">(first 500 shown)</span>@endif
                    </span>
                    <span class="text-xs text-zinc-400">{{ $result['ms'] }} ms</span>
                </div>

                @if ($result['count'] === 0)
                    <div class="px-5 py-8 text-center text-sm text-zinc-500">The query returned no rows.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                            <thead class="bg-zinc-50 dark:bg-zinc-950/40">
                                <tr>
                                    @foreach ($result['columns'] as $c)
                                        <th class="{{ $th }}">{{ $c }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach ($result['rows'] as $row)
                                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                                        @foreach ($result['columns'] as $c)
                                            @php $v = $row[$c]; @endphp
                                            <td class="{{ $td }}">
                                                @if ($v === null)
                                                    <span class="italic text-zinc-300 dark:text-zinc-600">NULL</span>
                                                @else
                                                    <span title="{{ $v }}">{{ \Illuminate\Support\Str::limit((string) $v, 80) }}</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @endif
    </div>
</x-layouts::app>
