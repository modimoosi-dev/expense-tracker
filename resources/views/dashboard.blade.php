@extends('layouts.app')

@section('title', 'Dashboard - Expense Tracker')
@section('page-title', 'Dashboard')

@section('content')
<div x-data="dashboardData()" x-init="init()">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 gap-6 mb-6 md:grid-cols-3">
        <!-- Total Income -->
        <div class="p-6 bg-white rounded-lg shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Income</p>
                    <p class="text-2xl font-bold text-green-600" x-text="formatCurrency(stats.total_income)">$0.00</p>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Expense -->
        <div class="p-6 bg-white rounded-lg shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Expense</p>
                    <p class="text-2xl font-bold text-red-600" x-text="formatCurrency(stats.total_expense)">$0.00</p>
                </div>
                <div class="p-3 bg-red-100 rounded-full">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Balance -->
        <div class="p-6 bg-white rounded-lg shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Balance</p>
                    <p class="text-2xl font-bold" :class="stats.balance >= 0 ? 'text-blue-600' : 'text-orange-600'" x-text="formatCurrency(stats.balance)">$0.00</p>
                </div>
                <div class="p-3 rounded-full" :class="stats.balance >= 0 ? 'bg-blue-100' : 'bg-orange-100'">
                    <svg class="w-8 h-8" :class="stats.balance >= 0 ? 'text-blue-600' : 'text-orange-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 gap-6 mb-6 lg:grid-cols-2">
        <!-- Expenses by Category -->
        <div class="p-6 bg-white rounded-lg shadow">
            <h3 class="mb-4 text-lg font-semibold text-gray-800">Expenses by Category</h3>
            <div class="space-y-3">
                <template x-for="item in stats.expenses_by_category" :key="item.category_id">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center">
                                <span class="w-3 h-3 mr-2 rounded-full" :style="`background-color: ${item.category.color}`"></span>
                                <span class="text-sm font-medium text-gray-700" x-text="item.category.name"></span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900" x-text="formatCurrency(item.total)"></span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="h-2 rounded-full" :style="`width: ${(item.total / stats.total_expense * 100).toFixed(1)}%; background-color: ${item.category.color}`"></div>
                        </div>
                    </div>
                </template>
                <div x-show="stats.expenses_by_category.length === 0" class="py-8 text-center text-gray-500">
                    No expense data available
                </div>
            </div>
        </div>

        <!-- Income by Category -->
        <div class="p-6 bg-white rounded-lg shadow">
            <h3 class="mb-4 text-lg font-semibold text-gray-800">Income by Category</h3>
            <div class="space-y-3">
                <template x-for="item in stats.income_by_category" :key="item.category_id">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center">
                                <span class="w-3 h-3 mr-2 rounded-full" :style="`background-color: ${item.category.color}`"></span>
                                <span class="text-sm font-medium text-gray-700" x-text="item.category.name"></span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900" x-text="formatCurrency(item.total)"></span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="h-2 rounded-full" :style="`width: ${(item.total / stats.total_income * 100).toFixed(1)}%; background-color: ${item.category.color}`"></div>
                        </div>
                    </div>
                </template>
                <div x-show="stats.income_by_category.length === 0" class="py-8 text-center text-gray-500">
                    No income data available
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="p-6 bg-white rounded-lg shadow">
        <h3 class="mb-4 text-lg font-semibold text-gray-800">Quick Actions</h3>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <a href="{{ route('expenses.index') }}?type=expense" class="flex items-center p-4 text-left transition-colors border-2 border-gray-200 rounded-lg hover:border-blue-500">
                <div class="p-3 mr-4 bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-800">Add Expense</h4>
                    <p class="text-sm text-gray-600">Record a new expense</p>
                </div>
            </a>
            <a href="{{ route('expenses.index') }}?type=income" class="flex items-center p-4 text-left transition-colors border-2 border-gray-200 rounded-lg hover:border-blue-500">
                <div class="p-3 mr-4 bg-green-100 rounded-full">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-800">Add Income</h4>
                    <p class="text-sm text-gray-600">Record a new income</p>
                </div>
            </a>
        </div>
    </div>
</div>

<script>
function dashboardData() {
    return {
        stats: {
            total_income: 0,
            total_expense: 0,
            balance: 0,
            expenses_by_category: [],
            income_by_category: []
        },
        async init() {
            await this.fetchStats();
        },
        async fetchStats() {
            try {
                const response = await fetch('/api/v1/expenses/statistics/summary');
                if (response.ok) {
                    this.stats = await response.json();
                }
            } catch (error) {
                console.error('Error fetching stats:', error);
            }
        },
        formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(amount || 0);
        }
    }
}
</script>
@endsection
