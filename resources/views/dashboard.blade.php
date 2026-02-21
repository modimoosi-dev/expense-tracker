@extends('layouts.app')

@section('title', 'Dashboard - Expense Tracker')
@section('page-title', 'Dashboard')

@section('content')
<div x-data="dashboardData()" x-init="init()">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 gap-6 mb-8 md:grid-cols-3">
        <!-- Total Income -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-modern hover-lift">
            <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-green-400/20 to-emerald-400/20 rounded-full -mr-16 -mt-16"></div>
            <div class="relative p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <span class="text-xs font-medium text-green-600 bg-green-100 px-3 py-1 rounded-full">+12.5%</span>
                </div>
                <p class="text-sm font-medium text-gray-500 mb-1">Total Income</p>
                <p class="text-3xl font-bold text-gray-900" x-text="formatCurrency(stats.total_income)">$0.00</p>
            </div>
        </div>

        <!-- Total Expense -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-modern hover-lift">
            <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-red-400/20 to-pink-400/20 rounded-full -mr-16 -mt-16"></div>
            <div class="relative p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-gradient-to-br from-red-500 to-pink-600 rounded-xl shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                        </svg>
                    </div>
                    <span class="text-xs font-medium text-red-600 bg-red-100 px-3 py-1 rounded-full">-8.2%</span>
                </div>
                <p class="text-sm font-medium text-gray-500 mb-1">Total Expense</p>
                <p class="text-3xl font-bold text-gray-900" x-text="formatCurrency(stats.total_expense)">$0.00</p>
            </div>
        </div>

        <!-- Balance -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-modern hover-lift">
            <div class="absolute top-0 right-0 w-32 h-32 rounded-full -mr-16 -mt-16"
                 :class="stats.balance >= 0 ? 'bg-gradient-to-br from-indigo-400/20 to-purple-400/20' : 'bg-gradient-to-br from-orange-400/20 to-amber-400/20'"></div>
            <div class="relative p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 rounded-xl shadow-lg" :class="stats.balance >= 0 ? 'bg-gradient-to-br from-indigo-500 to-purple-600' : 'bg-gradient-to-br from-orange-500 to-amber-600'">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <span class="text-xs font-medium px-3 py-1 rounded-full" :class="stats.balance >= 0 ? 'text-indigo-600 bg-indigo-100' : 'text-orange-600 bg-orange-100'">Current</span>
                </div>
                <p class="text-sm font-medium text-gray-500 mb-1">Net Balance</p>
                <p class="text-3xl font-bold text-gray-900" x-text="formatCurrency(stats.balance)">$0.00</p>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 gap-6 mb-8 lg:grid-cols-2">
        <!-- Expenses by Category -->
        <div class="p-6 bg-white rounded-2xl shadow-modern">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-800">Expenses by Category</h3>
                <span class="px-3 py-1 text-xs font-medium text-red-600 bg-red-50 rounded-full">This Month</span>
            </div>
            <div class="space-y-4">
                <template x-for="item in stats.expenses_by_category" :key="item.category_id">
                    <div class="group">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center">
                                <span class="w-2 h-2 mr-3 rounded-full" :style="`background-color: ${item.category.color}`"></span>
                                <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900 transition-colors" x-text="item.category.name"></span>
                            </div>
                            <span class="text-sm font-bold text-gray-900" x-text="formatCurrency(item.total)"></span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden">
                            <div class="h-2.5 rounded-full transition-all duration-500 ease-out shadow-sm"
                                 :style="`width: ${(item.total / stats.total_expense * 100).toFixed(1)}%; background: linear-gradient(90deg, ${item.category.color}, ${item.category.color}dd)`"></div>
                        </div>
                    </div>
                </template>
                <div x-show="stats.expenses_by_category.length === 0" class="py-12 text-center">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <p class="text-sm text-gray-500">No expense data available</p>
                </div>
            </div>
        </div>

        <!-- Income by Category -->
        <div class="p-6 bg-white rounded-2xl shadow-modern">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-800">Income by Category</h3>
                <span class="px-3 py-1 text-xs font-medium text-green-600 bg-green-50 rounded-full">This Month</span>
            </div>
            <div class="space-y-4">
                <template x-for="item in stats.income_by_category" :key="item.category_id">
                    <div class="group">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center">
                                <span class="w-2 h-2 mr-3 rounded-full" :style="`background-color: ${item.category.color}`"></span>
                                <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900 transition-colors" x-text="item.category.name"></span>
                            </div>
                            <span class="text-sm font-bold text-gray-900" x-text="formatCurrency(item.total)"></span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden">
                            <div class="h-2.5 rounded-full transition-all duration-500 ease-out shadow-sm"
                                 :style="`width: ${(item.total / stats.total_income * 100).toFixed(1)}%; background: linear-gradient(90deg, ${item.category.color}, ${item.category.color}dd)`"></div>
                        </div>
                    </div>
                </template>
                <div x-show="stats.income_by_category.length === 0" class="py-12 text-center">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <p class="text-sm text-gray-500">No income data available</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="p-6 bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 rounded-2xl shadow-modern">
        <h3 class="mb-6 text-lg font-bold text-gray-800">Quick Actions</h3>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <a href="{{ route('expenses.index') }}?type=expense" class="group relative overflow-hidden bg-white p-5 rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 hover:border-red-200">
                <div class="flex items-center">
                    <div class="p-3 bg-gradient-to-br from-red-500 to-pink-600 rounded-xl shadow-lg group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h4 class="font-bold text-gray-800 group-hover:text-red-600 transition-colors">Add Expense</h4>
                        <p class="text-sm text-gray-500">Record a new expense</p>
                    </div>
                </div>
                <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-red-400/10 to-pink-400/10 rounded-full -mr-12 -mt-12"></div>
            </a>
            <a href="{{ route('expenses.index') }}?type=income" class="group relative overflow-hidden bg-white p-5 rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 hover:border-green-200">
                <div class="flex items-center">
                    <div class="p-3 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-lg group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h4 class="font-bold text-gray-800 group-hover:text-green-600 transition-colors">Add Income</h4>
                        <p class="text-sm text-gray-500">Record a new income</p>
                    </div>
                </div>
                <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-green-400/10 to-emerald-400/10 rounded-full -mr-12 -mt-12"></div>
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
            return window.formatCurrency(amount);
        }
    }
}
</script>
@endsection
