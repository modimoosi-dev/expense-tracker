@extends('layouts.app')

@section('title', 'Dashboard - Expense Tracker')
@section('page-title', 'Dashboard')

@section('content')
<div x-data="dashboardData" x-init="init()">

    <!-- Hero Balance Card -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-700 shadow-modern-lg mb-6">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -mr-32 -mt-32"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/5 rounded-full -ml-24 -mb-24"></div>
        <div class="relative p-6">
            <p class="text-indigo-200 text-sm font-medium mb-1">Net Balance</p>
            <p class="text-white text-4xl font-bold tracking-tight mb-5" x-text="formatCurrency(stats.balance)">P0.00</p>
            <div class="flex gap-6 overflow-x-auto pb-1 scrollbar-none">
                <div class="flex items-center gap-2 shrink-0">
                    <div class="p-1.5 bg-white/20 rounded-lg">
                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-indigo-200 text-xs">Income</p>
                        <p class="text-white font-semibold text-sm" x-text="formatCurrency(stats.total_income)"></p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <div class="p-1.5 bg-white/20 rounded-lg">
                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-indigo-200 text-xs">Expenses</p>
                        <p class="text-white font-semibold text-sm" x-text="formatCurrency(stats.total_expense)"></p>
                    </div>
                </div>
                <div class="shrink-0 text-right">
                    <p class="text-indigo-200 text-xs">Saved</p>
                    <p class="text-white font-semibold text-sm" x-text="stats.savings_rate + '%'"></p>
                </div>
            </div>
            <!-- Spending bar -->
            <div class="mt-4 bg-white/20 rounded-full h-1.5">
                <div class="bg-white rounded-full h-1.5 transition-all duration-700"
                     :style="`width: ${Math.min(stats.spend_pct, 100)}%`"></div>
            </div>
            <p class="text-indigo-200 text-xs mt-1.5" x-text="`${stats.spend_pct}% of income spent`"></p>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 gap-6 mb-6 lg:grid-cols-2">

        <!-- Cashflow Chart -->
        <div class="p-5 bg-white dark:bg-gray-800 rounded-2xl shadow-modern" x-data="{ cashflowOpen: false }">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-gray-800 dark:text-white">Cashflow</h3>
                <!-- Trigger pill -->
                <button @click="cashflowOpen = true"
                        class="flex items-center gap-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 dark:bg-indigo-900/40 dark:text-indigo-300 rounded-full px-3 py-1.5 active:scale-95 transition-all">
                    <span x-text="cashflowPeriod === 'daily' ? 'Daily' : cashflowPeriod === 'weekly' ? 'Weekly' : 'Monthly'"></span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                </button>
            </div>
            <div class="relative w-full h-52">
                <canvas id="cashflowLineChart"></canvas>
            </div>

            <!-- Bottom sheet -->
            <div x-show="cashflowOpen"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-[90] flex items-end justify-center"
                 style="display:none">
                <div class="absolute inset-0 bg-black/40" @click="cashflowOpen = false"></div>
                <div class="relative w-full max-w-lg bg-white dark:bg-gray-800 rounded-t-2xl shadow-2xl pb-safe"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="translate-y-full"
                     x-transition:enter-end="translate-y-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="translate-y-0"
                     x-transition:leave-end="translate-y-full">
                    <div class="flex justify-center pt-3 pb-1">
                        <div class="w-10 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                    </div>
                    <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider px-6 pt-3 pb-2">Cashflow Period</p>
                    @foreach([['daily','Daily','Show this month day by day'],['weekly','Weekly','Last 12 weeks'],['monthly','Monthly','Last 12 months']] as [$val, $label, $hint])
                    <button @click="cashflowPeriod = '{{ $val }}'; redrawCashflow(); cashflowOpen = false"
                            class="w-full flex items-center justify-between px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700 active:bg-gray-100 transition-colors">
                        <div class="text-left">
                            <p class="text-sm font-semibold text-gray-800 dark:text-white">{{ $label }}</p>
                            <p class="text-xs text-gray-400">{{ $hint }}</p>
                        </div>
                        <svg x-show="cashflowPeriod === '{{ $val }}'" class="w-5 h-5 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </button>
                    @endforeach
                    <div class="pb-6"></div>
                </div>
            </div>
        </div>

        <!-- Spending by Category -->
        <div class="p-5 bg-white dark:bg-gray-800 rounded-2xl shadow-modern" x-data="{ categoryOpen: false }">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-gray-800 dark:text-white">Spending by Category</h3>
                <!-- Trigger pill -->
                <button @click="categoryOpen = true"
                        class="flex items-center gap-1.5 text-xs font-medium text-rose-600 bg-rose-50 dark:bg-rose-900/40 dark:text-rose-300 rounded-full px-3 py-1.5 active:scale-95 transition-all">
                    <span x-text="categoryPeriod === 'all' ? 'All Time' : categoryPeriod === 'month' ? 'This Month' : 'This Week'"></span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                </button>
            </div>
            <div class="relative w-full h-64">
                <canvas id="expensesPieChart"></canvas>
            </div>

            <!-- Bottom sheet -->
            <div x-show="categoryOpen"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-[90] flex items-end justify-center"
                 style="display:none">
                <div class="absolute inset-0 bg-black/40" @click="categoryOpen = false"></div>
                <div class="relative w-full max-w-lg bg-white dark:bg-gray-800 rounded-t-2xl shadow-2xl pb-safe"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="translate-y-full"
                     x-transition:enter-end="translate-y-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="translate-y-0"
                     x-transition:leave-end="translate-y-full">
                    <div class="flex justify-center pt-3 pb-1">
                        <div class="w-10 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                    </div>
                    <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider px-6 pt-3 pb-2">Spending Period</p>
                    @foreach([['all','All Time','Every transaction ever'],['month','This Month','Current calendar month'],['week','This Week','Starting from Sunday']] as [$val, $label, $hint])
                    <button @click="categoryPeriod = '{{ $val }}'; redrawPie(); categoryOpen = false"
                            class="w-full flex items-center justify-between px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700 active:bg-gray-100 transition-colors">
                        <div class="text-left">
                            <p class="text-sm font-semibold text-gray-800 dark:text-white">{{ $label }}</p>
                            <p class="text-xs text-gray-400">{{ $hint }}</p>
                        </div>
                        <svg x-show="categoryPeriod === '{{ $val }}'" class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </button>
                    @endforeach
                    <div class="pb-6"></div>
                </div>
            </div>
        </div>

    </div>

    <!-- Recent Transactions -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-modern">
        <div class="flex items-center justify-between px-5 pt-5 pb-3">
            <h3 class="text-base font-bold text-gray-800 dark:text-white">Recent Transactions</h3>
            <a href="{{ route('expenses.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">View all →</a>
        </div>
        <div class="divide-y divide-gray-50 dark:divide-gray-700">
            <template x-for="tx in recentTransactions" :key="tx.id">
                <div class="flex items-center gap-3 px-5 py-3">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0"
                         :style="`background-color: ${tx.category?.color || '#e5e7eb'}22`">
                        <span class="text-base" x-text="tx.category?.icon || '💸'"></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-800 dark:text-white truncate" x-text="tx.description || tx.category?.name || 'Transaction'"></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="formatDate(tx.date)"></p>
                    </div>
                    <span class="text-sm font-bold shrink-0"
                          :class="tx.type === 'income' ? 'text-green-600' : 'text-red-500'"
                          x-text="(tx.type === 'income' ? '+' : '-') + formatCurrency(tx.amount)"></span>
                </div>
            </template>
            <div x-show="recentTransactions.length === 0" class="px-5 py-8 text-center text-sm text-gray-400">
                No transactions yet. Add your first one above.
            </div>
        </div>
    </div>

</div>
@endsection
