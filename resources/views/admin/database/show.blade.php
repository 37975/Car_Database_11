<x-layouts::app :title="$table.' · Database'">
    @php
        $card = 'rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900';
        $th = 'whitespace-nowrap px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500';
        $td = 'whitespace-nowrap px-3 py-2 align-top';
        $fmt = function ($v) {
            if ($v === null) return null;
            $s = is_scalar($v) ? (string) $v : json_encode($v);
            return \Illuminate\Support\Str::limit($s, 60);
        };
    @endphp

    <div class="relative mx-auto w-full max-w-7xl px-4 pb-10 pt-2">
        @include('admin.database._header', [
            'title' => $table,
            'crumbs' => ['Database' => route('admin.database.index'), $table => null],
        ])

        <p class="-mt-2 mb-6 text-sm text-zinc-500">{{ $description }} · <b>{{ number_format($rows->total()) }}</b> rows</p>

        {{-- Structure --}}
        <section class="{{ $card }} mb-6 overflow-hidden">
            <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-3 dark:border-zinc-800">
                <h2 class="font-semibold text-zinc-900 dark:text-white">Structure</h2>
                <span class="text-xs text-zinc-400">{{ count($columns) }} columns</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                    <thead class="bg-zinc-50 dark:bg-zinc-950/40">
                        <tr>
                            <th class="{{ $th }}">Key</th>
                            <th class="{{ $th }}">Column</th>
                            <th class="{{ $th }}">Type</th>
                            <th class="{{ $th }}">Null</th>
                            <th class="{{ $th }}">Default</th>
                            <th class="{{ $th }}">References</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($columns as $col)
                            @php
                                $name = $col['name'];
                                $isPk = in_array($name, $primary, true);
                                $isUq = in_array($name, $unique, true);
                                $fk = $fkByColumn[$name] ?? null;
                            @endphp
                            <tr>
                                <td class="{{ $td }}">
                                    <div class="flex gap-1">
                                        @if ($isPk)<span class="rounded bg-orange-100 px-1.5 py-0.5 text-[11px] font-bold text-orange-700 dark:bg-orange-950/50 dark:text-orange-300">PK</span>@endif
                                        @if ($fk)<span class="rounded bg-blue-100 px-1.5 py-0.5 text-[11px] font-bold text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">FK</span>@endif
                                        @if ($isUq)<span class="rounded bg-zinc-200 px-1.5 py-0.5 text-[11px] font-bold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">UQ</span>@endif
                                    </div>
                                </td>
                                <td class="{{ $td }} font-mono font-semibold {{ $isPk ? 'underline' : '' }} text-zinc-900 dark:text-white">
                                    {{ $name }}
                                    @if ($col['auto_increment'] ?? false)<span class="ml-1 text-[11px] font-normal text-zinc-400">auto</span>@endif
                                </td>
                                <td class="{{ $td }} font-mono text-zinc-600 dark:text-zinc-300">{{ $col['type'] ?? $col['type_name'] }}</td>
                                <td class="{{ $td }} text-zinc-500">{{ $col['nullable'] ? 'YES' : 'NO' }}</td>
                                <td class="{{ $td }} font-mono text-zinc-500">{{ $col['default'] ?? '—' }}</td>
                                <td class="{{ $td }}">
                                    @if ($fk)
                                        @if (in_array($fk['table'], $viewable, true))
                                            <a href="{{ route('admin.database.show', $fk['table']) }}" class="font-mono text-blue-600 hover:underline dark:text-blue-400">{{ $fk['table'] }}.{{ $fk['column'] }}</a>
                                        @else
                                            <span class="font-mono">{{ $fk['table'] }}.{{ $fk['column'] }}</span>
                                        @endif
                                        @if ($fk['on_delete'])<span class="ml-1 text-xs text-zinc-400">on delete {{ strtolower($fk['on_delete']) }}</span>@endif
                                    @else
                                        <span class="text-zinc-300 dark:text-zinc-600">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if (count($indexes))
                <div class="border-t border-zinc-200 px-5 py-3 text-sm dark:border-zinc-800">
                    <div class="mb-2 font-semibold text-zinc-900 dark:text-white">Indexes</div>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($indexes as $idx)
                            <span class="rounded-lg border border-zinc-200 px-2.5 py-1 font-mono text-xs dark:border-zinc-700">
                                <span class="font-semibold">{{ $idx['primary'] ? 'PRIMARY' : ($idx['unique'] ? 'UNIQUE' : 'INDEX') }}</span>
                                ({{ implode(', ', $idx['columns']) }})
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($ddl)
                <details class="border-t border-zinc-200 px-5 py-3 dark:border-zinc-800">
                    <summary class="cursor-pointer text-sm font-semibold text-zinc-900 dark:text-white">CREATE TABLE statement</summary>
                    <pre class="mt-3 overflow-x-auto rounded-xl bg-zinc-950 p-4 font-mono text-xs leading-relaxed text-zinc-100">{{ $ddl }}</pre>
                </details>
            @endif
        </section>

        {{-- Rows --}}
        <section class="{{ $card }} overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-200 px-5 py-3 dark:border-zinc-800">
                <h2 class="font-semibold text-zinc-900 dark:text-white">Rows</h2>
                <code class="rounded bg-zinc-100 px-2 py-1 font-mono text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ $rowSql }}</code>
            </div>

            @if ($rows->isEmpty())
                <div class="px-5 py-10 text-center text-sm text-zinc-500">This table has no rows yet.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                        <thead class="bg-zinc-50 dark:bg-zinc-950/40">
                            <tr>
                                @foreach ($columns as $col)
                                    <th class="{{ $th }} font-mono normal-case">{{ $col['name'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($rows as $row)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                                    @foreach ($columns as $col)
                                        @php $v = $row->{$col['name']} ?? null; @endphp
                                        <td class="{{ $td }} font-mono text-xs">
                                            @if (in_array($col['name'], $hidden, true))
                                                <span class="italic text-zinc-400">hidden</span>
                                            @elseif ($v === null)
                                                <span class="italic text-zinc-300 dark:text-zinc-600">NULL</span>
                                            @else
                                                <span title="{{ is_scalar($v) ? $v : json_encode($v) }}">{{ $fmt($v) }}</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($rows->hasPages())
                    <div class="border-t border-zinc-200 px-5 py-3 dark:border-zinc-800">{{ $rows->links() }}</div>
                @endif
            @endif
        </section>

        @if ($hidden)
            <p class="mt-3 text-xs text-zinc-400">Hidden for security: {{ implode(', ', $hidden) }} (stored as a bcrypt hash / token).</p>
        @endif
    </div>
</x-layouts::app>
