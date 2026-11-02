<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">

    @php
        // [rail] admins get the icon rail (<x-admin.rail />) on desktop instead of this sidebar
        $isAdmin = auth()->user()->isAdmin();

        // Menu item size: height h-10 (40px), text 15px
        $navItem = 'h-10! lg:h-10! text-[15px]! font-medium';

        // Highlight the current admin item in red
        $active = fn (string $pattern) => request()->routeIs($pattern)
            ? 'bg-red-600! text-white! hover:bg-red-700!'
            : '';
    @endphp

    {{-- [rail] for admins this sidebar is only the mobile drawer (hidden from lg up) --}}
    <flux:sidebar sticky collapsible="mobile"
        :class="'border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 ' . ($isAdmin ? 'lg:hidden!' : '')">
        <flux:sidebar.header>
            <x-app-logo :sidebar="true" href="{{ route('admin.dashboard') }}" wire:navigate />

            <div x-data class="ms-auto">
                <button
                    type="button"
                    x-on:click="$flux.dark = ! $flux.dark"
                    aria-label="Toggle theme"
                    class="flex size-9 items-center justify-center rounded-lg border
                           border-zinc-300 bg-white text-zinc-800 shadow-sm transition
                           hover:bg-zinc-100
                           dark:border-zinc-600 dark:bg-zinc-800 dark:text-amber-300 dark:hover:bg-zinc-700"
                >
                    <flux:icon.moon x-show="! $flux.dark" class="size-5 text-indigo-600" />
                    <flux:icon.sun x-show="$flux.dark" class="size-5 text-amber-300" />
                </button>
            </div>

            <flux:sidebar.collapse class="lg:hidden" />
        </flux:sidebar.header>

        @if (auth()->user()->isAdmin())
            <div class="px-2">
                <flux:badge color="red" icon="shield-check" size="sm">
                    Admin account
                </flux:badge>
            </div>
        @endif

       
        @if (auth()->user()->isAdmin())
            <flux:sidebar.nav class="gap-1">
                <flux:sidebar.item
                    icon="cog"
                    :href="route('admin.dashboard')"
                    :current="request()->routeIs('admin.dashboard')"
                    :class="$navItem . ' ' . $active('admin.dashboard')"
                    wire:navigate
                >
                    Admin Dashboard
                </flux:sidebar.item>

                {{-- Members (added by teammate) --}}
                <flux:sidebar.item
                    icon="users"
                    :href="route('admin.members.index')"
                    :current="request()->routeIs('admin.members.*')"
                    :class="$navItem . ' ' . $active('admin.members.*')"
                >
                    Members
                </flux:sidebar.item>

                <flux:sidebar.item
                    icon="clipboard-document-list"
                    :href="route('admin.orders.index')"
                    :current="request()->routeIs('admin.orders.*')"
                    :class="$navItem . ' ' . $active('admin.orders.*')"
                >
                    Orders
                </flux:sidebar.item>

                {{-- Shop data (routes/admin-catalog.php) --}}
                <flux:sidebar.item
                    icon="tag"
                    :href="route('admin.brands.index')"
                    :current="request()->routeIs('admin.brands.*')"
                    :class="$navItem . ' ' . $active('admin.brands.*')"
                >
                    Brands
                </flux:sidebar.item>

                <flux:sidebar.item
                    icon="squares-2x2"
                    :href="route('admin.categories.index')"
                    :current="request()->routeIs('admin.categories.*')"
                    :class="$navItem . ' ' . $active('admin.categories.*')"
                >
                    Categories
                </flux:sidebar.item>

                <flux:sidebar.item
                    icon="truck"
                    :href="route('admin.cars.index')"
                    :current="request()->routeIs('admin.cars.*')"
                    :class="$navItem . ' ' . $active('admin.cars.*')"
                >
                    Cars
                </flux:sidebar.item>

                <flux:sidebar.item
                    icon="trophy"
                    :href="route('admin.tiers.index')"
                    :current="request()->routeIs('admin.tiers.*')"
                    :class="$navItem . ' ' . $active('admin.tiers.*')"
                >
                    Membership Tiers
                </flux:sidebar.item>

                <flux:sidebar.item
                    icon="star"
                    :href="route('admin.reviews.index')"
                    :current="request()->routeIs('admin.reviews.*')"
                    :class="$navItem . ' ' . $active('admin.reviews.*')"
                >
                    Reviews
                </flux:sidebar.item>

                <flux:sidebar.item
                    icon="document-text"
                    :href="route('admin.logs.index')"
                    :current="request()->routeIs('admin.logs.*')"
                    :class="$navItem . ' ' . $active('admin.logs.*')"
                >
                    Activity Log
                </flux:sidebar.item>

                <flux:sidebar.item
                    icon="circle-stack"
                    :href="route('admin.database.index')"
                    :current="request()->routeIs('admin.database.*')"
                    :class="$navItem . ' ' . $active('admin.database.*')"
                >
                    Database
                </flux:sidebar.item>
            </flux:sidebar.nav>

        
        @else
            <flux:sidebar.nav class="gap-1">
                <flux:sidebar.group :heading="__('Platform')" class="grid gap-1">
                    <flux:sidebar.item
                        icon="shopping-bag"
                        :href="route('products.index')"
                        :current="request()->routeIs('products.*')"
                        :class="$navItem"
                    >
                        Cars
                    </flux:sidebar.item>

                    @if (auth()->user()->isMember())
                        <flux:sidebar.item
                            icon="shopping-cart"
                            :href="route('cart.index')"
                            :current="request()->routeIs('cart.*')"
                            :class="$navItem"
                        >
                            My Cart
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="clipboard-document-list"
                            :href="route('orders.index')"
                            :current="request()->routeIs('orders.*')"
                            :class="$navItem"
                        >
                            My Orders
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="trophy"
                            :href="route('membership.index')"
                            :current="request()->routeIs('membership.*')"
                            :class="$navItem"
                        >
                            Membership
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>
            </flux:sidebar.nav>
        @endif

        <flux:spacer />

        <flux:sidebar.nav>
            <flux:sidebar.item icon="arrow-uturn-left" :href="route('home')" :class="$navItem">
                Back to website
            </flux:sidebar.item>
        </flux:sidebar.nav>

        <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
    </flux:sidebar>

    {{-- [rail] admin-only floating icon rail (desktop) --}}
    @if ($isAdmin)
        <x-admin.rail />
    @endif

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
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
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
        <x-confirm-dialog />
</body>
</html>