@inject('settings', 'App\Services\Settings')

<div class="flex h-full flex-col text-sand-100">
    <div class="flex h-16 items-center px-5">
        <a href="{{ route('admin.dashboard') }}" class="font-brand text-xl text-white">{{ $settings->get('store_name') }}</a>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-2" aria-label="Admin">
        <x-admin.nav-link :href="route('admin.dashboard')" icon="grid" :active="request()->routeIs('admin.dashboard')">
            Dashboard
        </x-admin.nav-link>

        @can('manage-orders')
            <p class="px-3 pt-6 pb-2 text-xs font-medium tracking-wider text-sand-300/60 uppercase">Sales</p>

            <x-admin.nav-link :href="route('admin.orders.index')" icon="bag" :active="request()->routeIs('admin.orders.*')" :count="$ordersWaiting">
                Orders
                @if ($ordersWaiting > 0)
                    <span class="sr-only">({{ $ordersWaiting }} waiting for confirmation)</span>
                @endif
            </x-admin.nav-link>
            <x-admin.nav-link :href="route('admin.customers.index')" icon="users" :active="request()->routeIs('admin.customers.*')">
                Customers
            </x-admin.nav-link>
        @endcan

        @can('manage-catalog')
            <p class="px-3 pt-6 pb-2 text-xs font-medium tracking-wider text-sand-300/60 uppercase">Catalog</p>

            <x-admin.nav-link :href="route('admin.products.index')" icon="bag" :active="request()->routeIs('admin.products.*')">
                Products
            </x-admin.nav-link>
            <x-admin.nav-link :href="route('admin.inventory.index')" icon="layers" :active="request()->routeIs('admin.inventory.*', 'admin.stock-movements.*')">
                Inventory
            </x-admin.nav-link>

            <p class="px-3 pt-6 pb-2 text-xs font-medium tracking-wider text-sand-300/60 uppercase">Catalog setup</p>

            <x-admin.nav-link :href="route('admin.categories.index')" icon="tag" :active="request()->routeIs('admin.categories.*')">
                Categories
            </x-admin.nav-link>
            <x-admin.nav-link :href="route('admin.collections.index')" icon="grid" :active="request()->routeIs('admin.collections.*')">
                Collections
            </x-admin.nav-link>
            <x-admin.nav-link :href="route('admin.colors.index')" icon="palette" :active="request()->routeIs('admin.colors.*')">
                Colors
            </x-admin.nav-link>
            <x-admin.nav-link :href="route('admin.sizes.index')" icon="ruler" :active="request()->routeIs('admin.sizes.*')">
                Sizes
            </x-admin.nav-link>
            <x-admin.nav-link :href="route('admin.size-guides.index')" icon="table" :active="request()->routeIs('admin.size-guides.*')">
                Size guides
            </x-admin.nav-link>
        @endcan

        @can('manage-catalog')
            <p class="px-3 pt-6 pb-2 text-xs font-medium tracking-wider text-sand-300/60 uppercase">Marketing</p>

            <x-admin.nav-link :href="route('admin.banners.index')" icon="image" :active="request()->routeIs('admin.banners.*')">
                Home banners
            </x-admin.nav-link>
        @endcan

        @can('manage-store')
            <p class="px-3 pt-6 pb-2 text-xs font-medium tracking-wider text-sand-300/60 uppercase">Store</p>

            <x-admin.nav-link :href="route('admin.settings.edit')" icon="settings" :active="request()->routeIs('admin.settings.*')">
                Settings
            </x-admin.nav-link>
            <x-admin.nav-link :href="route('admin.delivery-zones.index')" icon="truck" :active="request()->routeIs('admin.delivery-zones.*')">
                Delivery areas
            </x-admin.nav-link>
            <x-admin.nav-link :href="route('admin.staff.index')" icon="users" :active="request()->routeIs('admin.staff.*')">
                Team
            </x-admin.nav-link>
        @endcan

        <p class="px-3 pt-6 pb-2 text-xs font-medium tracking-wider text-sand-300/60 uppercase">Storefront</p>

        <x-admin.nav-link :href="route('home', ['locale' => 'en'])" icon="external" target="_blank">
            View store
        </x-admin.nav-link>
    </nav>

    <div class="border-t border-white/10 p-3">
        <div class="px-3 py-2">
            <p class="truncate text-sm font-medium text-white">{{ auth()->user()->name }}</p>
            <p class="truncate text-xs text-sand-300/70">{{ auth()->user()->role->label() }} · {{ auth()->user()->email }}</p>
        </div>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="flex h-11 w-full items-center gap-3 rounded-lg px-3 text-sm text-sand-200 hover:bg-white/10 hover:text-white">
                <x-icon name="logout" class="size-5" />
                Sign out
            </button>
        </form>
    </div>
</div>
