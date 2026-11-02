<x-layouts::app :title="'Database'">
    <div class="relative mx-auto w-full max-w-6xl px-4 pb-10 pt-2">
        @include('admin.database._header', [
            'title' => 'Database',
            'crumbs' => ['Database' => null],
        ])

        <p class="mb-6 max-w-3xl text-sm text-zinc-500 dark:text-zinc-400">
            Live view of the database this website is running on. Open a table to see its structure
            (columns, primary key, foreign keys, indexes and the <span class="font-mono">CREATE TABLE</span> statement)
            and its rows, or run your own read-only SQL.
        </p>

        <div class="mb-8 flex flex-wrap gap-3">
            <a href="{{ route('admin.database.query') }}"
               class="inline-flex h-11 items-center gap-2 rounded-xl bg-red-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-500">
                <flux:icon.command-line class="size-5" />
                Open SQL console
            </a>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($tables as $t)
                <a href="{{ route('admin.database.show', $t['name']) }}"
                   class="group rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-red-500/60 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="truncate font-mono text-lg font-bold text-zinc-900 group-hover:text-red-600 dark:text-white">{{ $t['name'] }}</div>
                            <div class="mt-0.5 text-sm text-zinc-500">{{ $t['description'] }}</div>
                        </div>
                        <flux:icon.table-cells class="size-6 shrink-0 text-zinc-300 group-hover:text-red-500 dark:text-zinc-600" />
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2 text-xs">
                        <span class="rounded-full bg-zinc-100 px-2.5 py-1 font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                            {{ number_format($t['rows']) }} {{ \Illuminate\Support\Str::plural('row', $t['rows']) }}
                        </span>
                        <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                            {{ $t['columns'] }} columns
                        </span>
                        @if ($t['foreign_keys'])
                            <span class="rounded-full bg-blue-50 px-2.5 py-1 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300">
                                {{ $t['foreign_keys'] }} FK
                            </span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</x-layouts::app>
