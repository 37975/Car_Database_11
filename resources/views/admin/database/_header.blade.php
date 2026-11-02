{{-- Shared header for the database viewer. Expects: $title, $crumbs (array label => url|null), $driver --}}
<header class="relative mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <div class="flex flex-wrap items-center gap-2 text-sm text-zinc-400">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-red-600">Admin</a>
            @foreach ($crumbs as $label => $url)
                <span>/</span>
                @if ($url)
                    <a href="{{ $url }}" class="hover:text-red-600">{{ $label }}</a>
                @else
                    <span class="text-zinc-600 dark:text-zinc-300">{{ $label }}</span>
                @endif
            @endforeach
        </div>

        <h1 class="font-display mt-1 text-4xl font-black uppercase italic leading-none tracking-tight text-zinc-900 sm:text-5xl dark:text-white">
            {{ $title }}
        </h1>
        <div class="mt-2 h-1 w-14 -skew-x-12 bg-red-600"></div>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <span class="inline-flex w-fit items-center gap-2 rounded-full border border-zinc-300 px-4 py-1.5 text-sm dark:border-zinc-700">
            <flux:icon.circle-stack class="size-4 text-red-600" />
            <span class="font-mono">{{ $driver }}</span>
        </span>
        <span class="w-fit rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-900">
            Read-only
        </span>
    </div>
</header>
