@extends('layouts.app')

@section('title', 'Budgets - Expense Tracker')
@section('page-title', 'Budgets')

@section('content')
<div x-data="budgetsData()" x-init="init()">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Budget Tracking</h1>
            <p class="text-gray-600">Monitor your spending against budgets</p>
        </div>
        <button @click="openModal()" class="px-4 py-2 text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700">
            <svg class="inline-block w-5 h-5 mr-2 -mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Create Budget
        </button>
    </div>

    <!-- Active Budgets -->
    <div class="mb-6">
        <h2 class="mb-4 text-lg font-semibold text-gray-800">Active Budgets</h2>
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            <template x-for="budget in activeBudgets" :key="budget.id">
                <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-800" x-text="budget.name"></h3>
                            <p class="text-sm text-gray-600" x-text="budget.category ? budget.category.name : 'All Categories'"></p>
                            <p class="text-xs text-gray-500" x-text="formatPeriod(budget.period)"></p>
                        </div>
                        <div class="flex space-x-2">
                            <button @click="editBudget(budget)" class="text-gray-400 hover:text-blue-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                            <button @click="deleteBudget(budget.id)" class="text-gray-400 hover:text-red-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Budget Progress -->
                    <div class="mb-3">
                        <div class="flex items-center justify-between mb-2 text-sm">
                            <span class="font-medium text-gray-700">Spent</span>
                            <span class="font-semibold" :class="budget.percentage_used > 100 ? 'text-red-600' : 'text-gray-900'" x-text="formatCurrency(budget.spent_amount)"></span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="h-3 rounded-full transition-all"
                                 :class="budget.percentage_used >= 100 ? 'bg-red-500' : budget.percentage_used >= 80 ? 'bg-orange-500' : 'bg-green-500'"
                                 :style="`width: ${Math.min(budget.percentage_used, 100)}%`"></div>
                        </div>
                        <div class="flex items-center justify-between mt-2 text-sm">
                            <span class="text-gray-600" x-text="`${budget.percentage_used.toFixed(1)}% used`"></span>
                            <span class="font-medium text-gray-700" x-text="`${formatCurrency(budget.amount)} budget`"></span>
                        </div>
                    </div>

                    <!-- Remaining -->
                    <div class="pt-3 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Remaining</span>
                            <span class="text-lg font-bold" :class="budget.remaining_amount < 0 ? 'text-red-600' : 'text-green-600'" x-text="formatCurrency(budget.remaining_amount)"></span>
                        </div>
                        <div class="mt-1 text-xs text-gray-500" x-text="`${formatDate(budget.start_date)} - ${formatDate(budget.end_date)}`"></div>
                    </div>
                </div>
            </template>

            <div x-show="activeBudgets.length === 0" class="col-span-full py-12 text-center text-gray-500">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                <p class="text-lg font-medium">No active budgets</p>
                <p class="text-sm">Create your first budget to start tracking</p>
            </div>
        </div>
    </div>

    <!-- Inactive Budgets -->
    <div x-show="inactiveBudgets.length > 0">
        <h2 class="mb-4 text-lg font-semibold text-gray-800">Inactive Budgets</h2>
        <div class="overflow-hidden bg-white rounded-lg shadow">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Category</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Period</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">Amount</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="budget in inactiveBudgets" :key="budget.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 whitespace-nowrap" x-text="budget.name"></td>
                                <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap" x-text="budget.category?.name || 'All'"></td>
                                <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap" x-text="formatPeriod(budget.period)"></td>
                                <td class="px-6 py-4 text-sm font-semibold text-right text-gray-900 whitespace-nowrap" x-text="formatCurrency(budget.amount)"></td>
                                <td class="px-6 py-4 text-sm text-right whitespace-nowrap">
                                    <button @click="toggleActive(budget)" class="mr-3 text-blue-600 hover:text-blue-900">Activate</button>
                                    <button @click="deleteBudget(budget.id)" class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div x-show="showModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto bg-gray-900 bg-opacity-50"
         style="display: none;">
        <div @click.away="closeModal()"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-95"
             class="w-full max-w-lg p-6 bg-white rounded-lg shadow-xl">
            <h2 class="mb-4 text-xl font-bold text-gray-800" x-text="editingBudget ? 'Edit Budget' : 'Create Budget'"></h2>

            <form @submit.prevent="saveBudget()">
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-700">Budget Name*</label>
                    <input type="text" x-model="form.name" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Amount*</label>
                        <input type="number" step="0.01" x-model="form.amount" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Period*</label>
                        <select x-model="form.period" required @change="updateDates()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-700">Category (Optional)</label>
                    <select x-model="form.category_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Categories</option>
                        <template x-for="category in categories.filter(c => c.type === 'expense')" :key="category.id">
                            <option :value="category.id" x-text="category.name"></option>
                        </template>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Start Date*</label>
                        <input type="date" x-model="form.start_date" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">End Date*</label>
                        <input type="date" x-model="form.end_date" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" x-model="form.is_active" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">Active</span>
                    </label>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" @click="closeModal()"
                            class="px-4 py-2 text-gray-700 transition-colors bg-gray-100 rounded-lg hover:bg-gray-200">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700">
                        <span x-text="editingBudget ? 'Update' : 'Create'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function budgetsData() {
    return {
        budgets: [],
        categories: [],
        showModal: false,
        editingBudget: null,
        form: {
            user_id: 1,
            category_id: '',
            name: '',
            amount: '',
            period: 'monthly',
            start_date: '',
            end_date: '',
            is_active: true
        },
        get activeBudgets() {
            return this.budgets.filter(b => b.is_active);
        },
        get inactiveBudgets() {
            return this.budgets.filter(b => !b.is_active);
        },
        async init() {
            await this.fetchCategories();
            await this.fetchBudgets();
            this.setDefaultDates();
        },
        async fetchBudgets() {
            try {
                const response = await fetch('/api/v1/budgets?user_id=1');
                if (response.ok) {
                    this.budgets = await response.json();
                }
            } catch (error) {
                console.error('Error fetching budgets:', error);
            }
        },
        async fetchCategories() {
            try {
                const response = await fetch('/api/v1/categories');
                if (response.ok) {
                    this.categories = await response.json();
                }
            } catch (error) {
                console.error('Error fetching categories:', error);
            }
        },
        setDefaultDates() {
            const now = new Date();
            this.form.start_date = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
            this.updateDates();
        },
        updateDates() {
            if (!this.form.start_date) return;

            const start = new Date(this.form.start_date);
            let end = new Date(start);

            if (this.form.period === 'monthly') {
                end.setMonth(end.getMonth() + 1);
                end.setDate(end.getDate() - 1);
            } else if (this.form.period === 'quarterly') {
                end.setMonth(end.getMonth() + 3);
                end.setDate(end.getDate() - 1);
            } else if (this.form.period === 'yearly') {
                end.setFullYear(end.getFullYear() + 1);
                end.setDate(end.getDate() - 1);
            }

            this.form.end_date = end.toISOString().split('T')[0];
        },
        openModal() {
            this.showModal = true;
            this.editingBudget = null;
            this.setDefaultDates();
            this.form = {
                user_id: 1,
                category_id: '',
                name: '',
                amount: '',
                period: 'monthly',
                start_date: this.form.start_date,
                end_date: this.form.end_date,
                is_active: true
            };
        },
        closeModal() {
            this.showModal = false;
            this.editingBudget = null;
        },
        editBudget(budget) {
            this.editingBudget = budget;
            this.form = {
                user_id: budget.user_id,
                category_id: budget.category_id || '',
                name: budget.name,
                amount: budget.amount,
                period: budget.period,
                start_date: budget.start_date,
                end_date: budget.end_date,
                is_active: budget.is_active
            };
            this.showModal = true;
        },
        async saveBudget() {
            try {
                const url = this.editingBudget
                    ? `/api/v1/budgets/${this.editingBudget.id}`
                    : '/api/v1/budgets';
                const method = this.editingBudget ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.form)
                });

                if (response.ok) {
                    await this.fetchBudgets();
                    this.closeModal();
                }
            } catch (error) {
                console.error('Error saving budget:', error);
            }
        },
        async toggleActive(budget) {
            try {
                const response = await fetch(`/api/v1/budgets/${budget.id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ is_active: !budget.is_active })
                });

                if (response.ok) {
                    await this.fetchBudgets();
                }
            } catch (error) {
                console.error('Error toggling budget:', error);
            }
        },
        async deleteBudget(id) {
            if (!confirm('Are you sure you want to delete this budget?')) return;

            try {
                const response = await fetch(`/api/v1/budgets/${id}`, {
                    method: 'DELETE'
                });

                if (response.ok) {
                    await this.fetchBudgets();
                }
            } catch (error) {
                console.error('Error deleting budget:', error);
            }
        },
        formatCurrency(amount) {
            return window.formatCurrency(amount);
        },
        formatDate(date) {
            return new Date(date).toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
        },
        formatPeriod(period) {
            return period.charAt(0).toUpperCase() + period.slice(1);
        }
    }
}
</script>
@endsection
