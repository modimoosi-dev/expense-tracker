@extends('layouts.app')

@section('title', 'Dashboard - Expense Tracker')
@section('page-title', 'Dashboard')

@section('content')
<div x-data="dashboardData">
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
                <span class="px-3 py-1 text-xs font-medium text-red-600 bg-red-50 rounded-full">All Time</span>
            </div>
            <div class="relative w-full h-64">
                <canvas id="expensesPieChart"></canvas>
            </div>
        </div>

        <!-- Cashflow -->
        <div class="p-6 bg-white rounded-2xl shadow-modern">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-800">Daily Cashflow</h3>
                <span class="px-3 py-1 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-full">This Month</span>
            </div>
            <div class="relative w-full h-64">
                <canvas id="cashflowLineChart"></canvas>
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
@endsection
