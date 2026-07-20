<x-layouts.app title="Dashboard">
    <x-page-header title="Dashboard" icon="dashboard" subtitle="What sold, what needs shipping, what is about to run out." />

    {{-- Stat tiles --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat label="Revenue Today" :value="$stats['revenue_today']" icon="credit-card" />
        <x-stat label="Revenue (30 Days)" :value="$stats['revenue_month']" icon="chart"
            :trend="$revenueTrend['label']" :trendColor="$revenueTrend['tone']" />
        <x-stat label="Orders (30 Days)" :value="$stats['orders_month']" icon="bag" />
        <x-stat label="Average Order" :value="$stats['avg_order']" icon="tag" />
    </div>

    <div class="section-divider my-8 border-t border-slate-200"></div>

    {{-- Needs Attention --}}
    <div class="mb-8">
        <h2 class="text-sm font-semibold text-slate-900 mb-3">Needs Attention</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <a href="{{ route('orders.index', ['status' => 'open', 'fulfillment' => 'unfulfilled']) }}"
                class="flex items-center gap-4 bg-white rounded-xl ring-1 ring-slate-200 shadow-sm p-5 hover:ring-brand-300 hover:shadow-md transition">
                <span class="inline-flex items-center justify-center w-11 h-11 rounded-lg bg-amber-50 text-amber-600 ring-1 ring-amber-100 shrink-0">
                    <x-icon name="truck" class="w-5 h-5" />
                </span>
                <div class="min-w-0">
                    <p class="text-2xl font-semibold text-slate-900 tabular">{{ $awaitingFulfillment }}</p>
                    <p class="text-sm text-slate-500 truncate">Awaiting Fulfillment</p>
                </div>
            </a>
            <a href="{{ route('orders.index', ['status' => 'open', 'financial' => 'pending']) }}"
                class="flex items-center gap-4 bg-white rounded-xl ring-1 ring-slate-200 shadow-sm p-5 hover:ring-brand-300 hover:shadow-md transition">
                <span class="inline-flex items-center justify-center w-11 h-11 rounded-lg bg-rose-50 text-rose-600 ring-1 ring-rose-100 shrink-0">
                    <x-icon name="credit-card" class="w-5 h-5" />
                </span>
                <div class="min-w-0">
                    <p class="text-2xl font-semibold text-slate-900 tabular">{{ $awaitingPayment }}</p>
                    <p class="text-sm text-slate-500 truncate">Awaiting Payment</p>
                </div>
            </a>
            <a href="{{ route('products.index') }}"
                class="flex items-center gap-4 bg-white rounded-xl ring-1 ring-slate-200 shadow-sm p-5 hover:ring-brand-300 hover:shadow-md transition">
                <span class="inline-flex items-center justify-center w-11 h-11 rounded-lg bg-brand-50 text-brand-600 ring-1 ring-brand-100 shrink-0">
                    <x-icon name="box" class="w-5 h-5" />
                </span>
                <div class="min-w-0">
                    <p class="text-2xl font-semibold text-slate-900 tabular">{{ $lowStockCount }}</p>
                    <p class="text-sm text-slate-500 truncate">Low Stock Variants</p>
                </div>
            </a>
        </div>
    </div>

    <div class="section-divider my-8 border-t border-slate-200"></div>

    {{-- Sales sparkline (rendered by public/js/shop-admin.js — no chart library) --}}
    <x-card title="Sales, Last 30 Days" subtitle="Net revenue on paid orders, by day." class="mb-8">
        <div x-data="dashboardSparkline(@js($salesSeries))" x-init="init()">
            <svg viewBox="0 0 640 160" preserveAspectRatio="none" class="w-full h-32">
                <polygon :points="areaPoints" class="fill-brand-500/10"></polygon>
                <polyline :points="linePoints" fill="none" class="stroke-brand-500" stroke-width="2" vector-effect="non-scaling-stroke"></polyline>
            </svg>
            <div class="flex justify-between text-[11px] text-slate-400 mt-1">
                <span x-text="firstLabel"></span>
                <span x-text="lastLabel"></span>
            </div>
        </div>
    </x-card>

    {{-- Tabbed panels --}}
    <div x-data="{ tab: 'orders' }">
        <div class="inline-flex flex-wrap gap-1 rounded-lg bg-slate-100 p-1 mb-4" role="tablist" aria-label="Dashboard panels">
            <button type="button" role="tab" :aria-selected="(tab === 'orders').toString()" @click="tab = 'orders'"
                :class="tab === 'orders' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500 hover:text-slate-700'"
                class="px-3 py-1.5 rounded-md text-sm font-medium transition">Recent Orders</button>
            <button type="button" role="tab" :aria-selected="(tab === 'products').toString()" @click="tab = 'products'"
                :class="tab === 'products' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500 hover:text-slate-700'"
                class="px-3 py-1.5 rounded-md text-sm font-medium transition">Top Products</button>
            <button type="button" role="tab" :aria-selected="(tab === 'lowstock').toString()" @click="tab = 'lowstock'"
                :class="tab === 'lowstock' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500 hover:text-slate-700'"
                class="px-3 py-1.5 rounded-md text-sm font-medium transition">Low Stock</button>
            <button type="button" role="tab" :aria-selected="(tab === 'customers').toString()" @click="tab = 'customers'"
                :class="tab === 'customers' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500 hover:text-slate-700'"
                class="px-3 py-1.5 rounded-md text-sm font-medium transition">New Customers</button>
        </div>

        {{-- Recent Orders --}}
        <div x-show="tab === 'orders'" x-cloak>
            <x-card flush>
                @if ($recentOrders->isEmpty())
                    <x-empty-state icon="bag" title="No Orders Yet" description="Orders will appear here as they come in." />
                @else
                    <x-table flush>
                        <thead>
                            <tr>
                                <th>Order</th><th>Customer</th><th>Payment</th><th>Fulfillment</th><th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentOrders as $order)
                                <tr class="cursor-pointer" onclick="window.location='{{ route('orders.show', $order) }}'">
                                    <td>
                                        <a href="{{ route('orders.show', $order) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $order->number }}</a>
                                        <span class="block text-xs text-slate-500">{{ $order->created_at?->diffForHumans() }}</span>
                                    </td>
                                    <td class="text-slate-600">{{ $order->customer_name }}</td>
                                    <td><x-badge :color="$order->financial_badge" dot>{{ \Illuminate\Support\Str::headline($order->financial_status) }}</x-badge></td>
                                    <td><x-badge :color="$order->fulfillment_badge" dot>{{ \Illuminate\Support\Str::headline($order->fulfillment_status) }}</x-badge></td>
                                    <td class="text-right font-medium text-slate-900">{{ $order->total_formatted }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-table>
                @endif
            </x-card>
        </div>

        {{-- Top Products --}}
        <div x-show="tab === 'products'" x-cloak>
            <x-card flush>
                @if ($topProducts->isEmpty())
                    <x-empty-state icon="star" title="No Sales Yet" description="Best sellers over the last 30 days will appear here." />
                @else
                    <x-table flush>
                        <thead>
                            <tr>
                                <th>Product</th><th class="text-right">Units Sold</th><th class="text-right">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($topProducts as $row)
                                <tr>
                                    <td class="font-medium text-slate-900">
                                        @if ($row->product_id)
                                            <a href="{{ route('products.edit', $row->product_id) }}" class="hover:text-brand-700">{{ $row->name }}</a>
                                        @else
                                            {{ $row->name }}
                                        @endif
                                    </td>
                                    <td class="text-right tabular">{{ $row->units }}</td>
                                    <td class="text-right font-medium text-slate-900">{{ $row->revenue }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-table>
                @endif
            </x-card>
        </div>

        {{-- Low Stock --}}
        <div x-show="tab === 'lowstock'" x-cloak>
            <x-card flush>
                @if ($lowStock->isEmpty())
                    <x-empty-state icon="box" title="Stock Levels Look Healthy" description="Variants at or below the low-stock threshold appear here." />
                @else
                    <x-table flush>
                        <thead>
                            <tr>
                                <th>Product</th><th>SKU</th><th class="text-right">In Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lowStock as $variant)
                                <tr>
                                    <td class="font-medium text-slate-900">
                                        <a href="{{ route('products.edit', $variant->product_id) }}" class="hover:text-brand-700">{{ $variant->product?->name }}</a>
                                        @if ($variant->name !== 'Default')
                                            <span class="block text-xs text-slate-500">{{ $variant->name }}</span>
                                        @endif
                                    </td>
                                    <td class="font-mono text-xs text-slate-500">{{ $variant->sku ?: '—' }}</td>
                                    <td class="text-right">
                                        <x-badge :color="$variant->inventory_qty <= 0 ? 'danger' : 'warn'" dot>{{ $variant->inventory_qty }}</x-badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-table>
                @endif
            </x-card>
        </div>

        {{-- New Customers --}}
        <div x-show="tab === 'customers'" x-cloak>
            <x-card flush>
                @if ($newCustomers->isEmpty())
                    <x-empty-state icon="users" title="No Customers Yet" description="New sign-ups and guest checkouts will appear here." />
                @else
                    <x-table flush>
                        <thead>
                            <tr>
                                <th>Customer</th><th>Email</th><th class="text-right">Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($newCustomers as $customer)
                                <tr class="cursor-pointer" onclick="window.location='{{ route('customers.show', $customer) }}'">
                                    <td>
                                        <div class="flex items-center gap-2.5">
                                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-brand-50 text-brand-700 ring-1 ring-brand-100 text-xs font-semibold shrink-0">{{ $customer->initials }}</span>
                                            <a href="{{ route('customers.show', $customer) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $customer->name }}</a>
                                        </div>
                                    </td>
                                    <td class="text-slate-500">{{ $customer->email }}</td>
                                    <td class="text-right text-slate-500">{{ $customer->created_at?->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-table>
                @endif
            </x-card>
        </div>
    </div>

    <div class="section-divider my-8 border-t border-slate-200"></div>

    {{-- Catalog snapshot --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <x-stat label="Total Products" :value="$catalogCounts['products']" icon="bag" />
        <x-stat label="Active Products" :value="$catalogCounts['active']" icon="check-circle" />
        <x-stat label="Draft Products" :value="$catalogCounts['draft']" icon="edit" />
        <x-stat label="Total Customers" :value="$catalogCounts['customers']" icon="users" />
    </div>
</x-layouts.app>
