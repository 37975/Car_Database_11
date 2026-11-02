{{--
    Admin-only icon rail (desktop). No sidebar strip: every entry is its own floating square block.
    Point at a block and only that block slides out to show its label (over the page content).
    Used from layouts/app/sidebar.blade.php:  @if ($isAdmin) <x-admin.rail /> @endif
--}}
@php
    $user = auth()->user();

    // label, flux icon, route name, routeIs() pattern
    // rail => false: not shown here (reachable from the dashboard cards instead)
    $items = [
        ['label' => 'Admin Dashboard',  'icon' => 'cog',                     'route' => 'admin.dashboard',        'match' => 'admin.dashboard', 'navigate' => true],
        ['label' => 'Members',          'icon' => 'users',                   'route' => 'admin.members.index',    'match' => 'admin.members.*'],
        ['label' => 'Orders',           'icon' => 'clipboard-document-list', 'route' => 'admin.orders.index',     'match' => 'admin.orders.*'],
        ['label' => 'Brands',           'icon' => 'tag',                     'route' => 'admin.brands.index',     'match' => 'admin.brands.*'],
        ['label' => 'Categories',       'icon' => 'squares-2x2',             'route' => 'admin.categories.index', 'match' => 'admin.categories.*'],
        ['label' => 'Cars',             'icon' => 'truck',                   'route' => 'admin.cars.index',       'match' => 'admin.cars.*'],
        ['label' => 'Membership Tiers', 'icon' => 'trophy',                  'route' => 'admin.tiers.index',      'match' => 'admin.tiers.*'],
        ['label' => 'Reviews',          'icon' => 'star',                    'route' => 'admin.reviews.index',    'match' => 'admin.reviews.*'],
        ['label' => 'Activity Log',     'icon' => 'document-text',           'route' => 'admin.logs.index',       'match' => 'admin.logs.*'],
        ['label' => 'Database',         'icon' => 'circle-stack',            'route' => 'admin.database.index',   'match' => 'admin.database.*'],
    ];

    // Each entry: a fixed 40px square slot; the "pill" inside grows to the right on hover/focus.
    $slot   = 'pointer-events-auto relative h-10 w-10 shrink-0';
    $pill   = 'absolute inset-y-0 start-0 z-10 flex h-10 w-max max-w-10 items-center gap-3 overflow-hidden rounded-xl px-2.5 shadow-sm '
            . 'transition-[max-width,background-color,box-shadow] duration-200 ease-out '
            . 'hover:z-20 hover:max-w-64 hover:shadow-xl focus-visible:z-20 focus-visible:max-w-64 focus-visible:shadow-xl '
            . 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500/60';
    $active = 'bg-red-600 text-white shadow-md shadow-red-600/30 hover:bg-red-700';
    $idle   = 'bg-white text-zinc-600 ring-1 ring-zinc-200 hover:bg-zinc-50 hover:text-zinc-900 hover:ring-zinc-300 '
            . 'dark:bg-zinc-900 dark:text-zinc-300 dark:ring-zinc-800 dark:hover:bg-zinc-800 dark:hover:text-white dark:hover:ring-zinc-700';
    $label  = 'whitespace-nowrap pe-2 text-[15px] font-medium';
@endphp

{{-- pointer-events-none on the container so the empty space between blocks never blocks clicks --}}
<aside class="pointer-events-none fixed inset-y-0 start-3 z-40 hidden flex-col gap-2 py-3 lg:flex" aria-label="Main navigation">

    {{-- Logo --}}
    <div class="{{ $slot }}">
        <a href="{{ route('admin.dashboard') }}" wire:navigate aria-label="VELOCE"
           class="{{ $pill }} bg-red-600 text-white shadow-md shadow-red-600/30 hover:bg-red-700">
            <span class="grid size-5 shrink-0 place-items-center text-lg font-black italic leading-none">V</span>
            <span class="whitespace-nowrap pe-2 font-display text-base font-black uppercase italic tracking-tight">Veloce</span>
        </a>
    </div>

    {{-- Admin badge (not a link) --}}
    <div class="{{ $slot }}">
        <div class="{{ $pill }} bg-red-50 text-red-500 ring-1 ring-red-200 dark:bg-red-950/40 dark:ring-red-900/50">
            <flux:icon.shield-check class="size-5 shrink-0" />
            <span class="{{ $label }}">Admin account</span>
        </div>
    </div>

    {{-- Menu --}}
    <nav class="flex flex-1 flex-col gap-2">
        @foreach ($items as $item)
            @continue(! ($item['rail'] ?? true))

            @php $isCurrent = request()->routeIs($item['match']); @endphp

            <div class="{{ $slot }}">
                <a href="{{ route($item['route']) }}"
                   @if ($item['navigate'] ?? false) wire:navigate @endif
                   @if ($isCurrent) aria-current="page" @endif
                   aria-label="{{ $item['label'] }}"
                   class="{{ $pill }} {{ $isCurrent ? $active : $idle }}">
                    <flux:icon :name="$item['icon']" class="size-5 shrink-0" />
                    <span class="{{ $label }}">{{ $item['label'] }}</span>
                </a>
            </div>
        @endforeach
    </nav>

    {{-- Bottom --}}
    <div class="flex flex-col gap-2">
        <div class="{{ $slot }}">
            <a href="{{ route('home') }}" aria-label="Back to website" class="{{ $pill }} {{ $idle }}">
                <flux:icon name="arrow-uturn-left" class="size-5 shrink-0" />
                <span class="{{ $label }}">Back to website</span>
            </a>
        </div>

        <div x-data class="{{ $slot }}">
            <button type="button" x-on:click="$flux.dark = ! $flux.dark" aria-label="Toggle theme"
                    class="{{ $pill }} {{ $idle }}">
                <span class="relative size-5 shrink-0">
                    <flux:icon.moon x-show="! $flux.dark" class="size-5 text-indigo-500" />
                    <flux:icon.sun x-show="$flux.dark" class="size-5 text-amber-300" />
                </span>
                <span class="{{ $label }}">Toggle theme</span>
            </button>
        </div>

        <div class="{{ $slot }}">
            <flux:dropdown position="right" align="end">
                <button type="button" aria-label="Account menu" data-test="sidebar-menu-button"
                        class="{{ $pill }} {{ $idle }} px-1">
                    <flux:avatar :name="$user->name" :initials="$user->initials()" size="sm" class="shrink-0" />
                    <span class="{{ $label }}">{{ $user->name }}</span>
                </button>

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar :name="$user->name" :initials="$user->initials()" />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ $user->name }}</flux:heading>
                                    <flux:text class="truncate">{{ $user->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer">
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>
</aside>