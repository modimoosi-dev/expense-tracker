@extends('layouts.app')

@section('title', 'Reports - Expense Tracker')
@section('page-title', 'Reports & Analytics')

@section('content')
<div x-data="reportsData()" x-init="init()">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Reports & Analytics</h1>
            <p class="text-gray-600">Insights into your spending patterns</p>
        </div>
        <button @click="exportData()" class="px-4 py-2 text-white transition-colors bg-green-600 rounded-lg hover:bg-green-700">
            <svg class="inline-block w-5 h-5 mr-2 -mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Export CSV
        </button>
    </div>

    <div class="grid grid-cols-1 gap-6 mb-6 lg:grid-cols-2">
        <div class="p-6 bg-white rounded-lg shadow">
            <h2 class="mb-4 text-lg font-semibold text-gray-800">Yearly Comparison</h2>
            <div class="space-y-4">
                <div>
                    <h3 class="mb-2 text-sm font-medium text-gray-600" x-text="yearlyComparison.current_year?.year + ' (Current)'"></h3>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="p-3 bg-green-50 rounded-lg">
                            <p class="text-xs text-green-600">Income</p>
                            <p class="text-lg font-bold text-green-700" x-text="formatCurrency(yearlyComparison.current_year?.income || 0)"></p>
                        </div>
                        <div class="p-3 bg-red-50 rounded-lg">
                            <p class="text-xs text-red-600">Expense</p>
                            <p class="text-lg font-bold text-red-700" x-text="formatCurrency(yearlyComparison.current_year?.expense || 0)"></p>
                        </div>
                        <div class="p-3 bg-blue-50 rounded-lg">
                            <p class="text-xs text-blue-600">Balance</p>
                            <p class="text-lg font-bold text-blue-700" x-text="formatCurrency(yearlyComparison.current_year?.balance || 0)"></p>
                        </div>
                    </div>
                </div>
                <div>
                    <h3 class="mb-2 text-sm font-medium text-gray-600" x-text="yearlyComparison.previous_year?.year + ' (Previous)'"></h3>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <p class="text-xs text-gray-600">Income</p>
                            <p class="text-lg font-bold text-gray-700" x-text="formatCurrency(yearlyComparison.previous_year?.income || 0)"></p>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <p class="text-xs text-gray-600">Expense</p>
                            <p class="text-lg font-bold text-gray-700" x-text="formatCurrency(yearlyComparison.previous_year?.expense || 0)"></p>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <p class="text-xs text-gray-600">Balance</p>
                            <p class="text-lg font-bold text-gray-700" x-text="formatCurrency(yearlyComparison.previous_year?.balance || 0)"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6 bg-white rounded-lg shadow">
            <h2 class="mb-4 text-lg font-semibold text-gray-800">Top 5 Expenses (This Month)</h2>
            <div class="space-y-3">
                <template x-for="(expense, index) in topExpenses.slice(0, 5)" :key="expense.id">
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center flex-1">
                            <span class="flex items-center justify-center w-8 h-8 mr-3 text-sm font-bold text-white bg-blue-600 rounded-full" x-text="index + 1"></span>
                            <div>
                                <p class="font-medium text-gray-900" x-text="expense.description || 'No description'"></p>
                                <p class="text-xs text-gray-600" x-text="expense.category?.name"></p>
                            </div>
                        </div>
                        <span class="font-bold text-red-600" x-text="formatCurrency(expense.amount)"></span>
                    </div>
                </template>
                <div x-show="topExpenses.length === 0" class="py-8 text-center text-gray-500">
                    No expenses this month
                </div>
            </div>
        </div>
    </div>

    <div class="p-6 mb-6 bg-white rounded-lg shadow">
        <h2 class="mb-4 text-lg font-semibold text-gray-800">Monthly Trends (Last 12 Months)</h2>
        <div style="height: 300px;">
            <canvas id="trendsChart"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="p-6 bg-white rounded-lg shadow">
            <h2 class="mb-4 text-lg font-semibold text-gray-800">Expense Categories (This Month)</h2>
            <div style="height: 300px;">
                <canvas id="expenseCategoryChart"></canvas>
            </div>
        </div>
        <div class="p-6 bg-white rounded-lg shadow">
            <h2 class="mb-4 text-lg font-semibold text-gray-800">Income Categories (This Month)</h2>
            <div style="height: 300px;">
                <canvas id="incomeCategoryChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
function reportsData() {
    return {
        monthlyTrends: [],
        yearlyComparison: {},
        topExpenses: [],
        expenseBreakdown: [],
        incomeBreakdown: [],
        charts: {},
        async init() {
            await this.fetchData();
            setTimeout(() => this.initCharts(), 100);
        },
        async fetchData() {
            try {
                const [trends, yearly, top, expenseBreak, incomeBreak] = await Promise.all([
                    fetch('/api/v1/reports/monthly-trends?user_id=1&months=12').then(r => r.json()),
                    fetch('/api/v1/reports/yearly-comparison?user_id=1').then(r => r.json()),
                    fetch('/api/v1/reports/top-expenses?user_id=1&limit=5').then(r => r.json()),
                    fetch('/api/v1/reports/category-breakdown?user_id=1&type=expense').then(r => r.json()),
                    fetch('/api/v1/reports/category-breakdown?user_id=1&type=income').then(r => r.json())
                ]);
                this.monthlyTrends = trends;
                this.yearlyComparison = yearly;
                this.topExpenses = top;
                this.expenseBreakdown = expenseBreak;
                this.incomeBreakdown = incomeBreak;
            } catch (error) {
                console.error('Error fetching reports data:', error);
            }
        },
        initCharts() {
            this.initTrendsChart();
            if (this.expenseBreakdown.length > 0) {
                this.initCategoryChart('expenseCategoryChart', this.expenseBreakdown);
            }
            if (this.incomeBreakdown.length > 0) {
                this.initCategoryChart('incomeCategoryChart', this.incomeBreakdown);
            }
        },
        initTrendsChart() {
            const ctx = document.getElementById('trendsChart');
            if (!ctx) return;
            const labels = this.monthlyTrends.map(t => t.month);
            const incomeData = this.monthlyTrends.map(t => parseFloat(t.total_income));
            const expenseData = this.monthlyTrends.map(t => parseFloat(t.total_expense));
            this.charts.trends = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Income',
                        data: incomeData,
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.3,
                        fill: true
                    }, {
                        label: 'Expenses',
                        data: expenseData,
                        borderColor: 'rgb(239, 68, 68)',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        },
        initCategoryChart(canvasId, data) {
            const ctx = document.getElementById(canvasId);
            if (!ctx) return;
            const labels = data.map(item => item.category?.name || 'Unknown');
            const amounts = data.map(item => parseFloat(item.total));
            const colors = ['#EF4444', '#F59E0B', '#10B981', '#3B82F6', '#8B5CF6', '#EC4899', '#14B8A6', '#F97316'];
            this.charts[canvasId] = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: amounts,
                        backgroundColor: colors,
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { padding: 15, font: { size: 11 } }
                        }
                    }
                }
            });
        },
        async exportData() {
            window.location.href = '/api/v1/reports/export-csv?user_id=1';
        },
        formatCurrency(amount) {
            return window.formatCurrency(amount);
        }
    }
}
</script>
@endsection
