@extends('layouts.app')

@section('title', 'Transactions - Expense Tracker')
@section('page-title', 'Transactions')

@section('content')
<div x-data="expensesData()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col mb-6 space-y-4 md:flex-row md:items-center md:justify-between md:space-y-0">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Transactions</h1>
            <p class="text-gray-600">Track all your income and expenses</p>
        </div>
        <button @click="openModal()" class="px-4 py-2 text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700">
            <svg class="inline-block w-5 h-5 mr-2 -mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Add Transaction
        </button>
    </div>

    <!-- Filters -->
    <div class="p-4 mb-6 bg-white rounded-lg shadow">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-700">Type</label>
                <select x-model="filters.type" @change="fetchExpenses()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All</option>
                    <option value="income">Income</option>
                    <option value="expense">Expense</option>
                </select>
            </div>
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-700">Category</label>
                <select x-model="filters.category_id" @change="fetchExpenses()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Categories</option>
                    <template x-for="category in categories" :key="category.id">
                        <option :value="category.id" x-text="category.name"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-700">Start Date</label>
                <input type="date" x-model="filters.start_date" @change="fetchExpenses()"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-700">End Date</label>
                <input type="date" x-model="filters.end_date" @change="fetchExpenses()"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="overflow-hidden bg-white rounded-lg shadow">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Description</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Category</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="expense in expenses.data" :key="expense.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap" x-text="formatDate(expense.date)"></td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div x-text="expense.description || '-'"></div>
                                <div class="text-xs text-gray-500" x-show="expense.payment_method" x-text="expense.payment_method"></div>
                            </td>
                            <td class="px-6 py-4 text-sm whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="w-3 h-3 mr-2 rounded-full" :style="`background-color: ${expense.category.color}`"></span>
                                    <span x-text="expense.category.name"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm whitespace-nowrap">
                                <span class="inline-block px-2 py-1 text-xs font-medium rounded-full"
                                      :class="expense.type === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                      x-text="expense.type.charAt(0).toUpperCase() + expense.type.slice(1)"></span>
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-right whitespace-nowrap"
                                :class="expense.type === 'income' ? 'text-green-600' : 'text-red-600'"
                                x-text="formatCurrency(expense.amount)"></td>
                            <td class="px-6 py-4 text-sm text-right whitespace-nowrap">
                                <button @click="editExpense(expense)" class="mr-3 text-blue-600 hover:text-blue-900">Edit</button>
                                <button @click="deleteExpense(expense.id)" class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div x-show="expenses.data && expenses.data.length === 0" class="py-12 text-center text-gray-500">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"></path>
            </svg>
            <p class="text-lg font-medium">No transactions found</p>
            <p class="text-sm">Add your first transaction to get started</p>
        </div>

        <!-- Pagination -->
        <div x-show="expenses.last_page > 1" class="px-6 py-4 bg-gray-50">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing <span x-text="expenses.from"></span> to <span x-text="expenses.to"></span> of <span x-text="expenses.total"></span> results
                </div>
                <div class="flex space-x-2">
                    <button @click="goToPage(expenses.current_page - 1)" :disabled="!expenses.prev_page_url"
                            class="px-3 py-1 text-sm border border-gray-300 rounded disabled:opacity-50 hover:bg-gray-100">
                        Previous
                    </button>
                    <button @click="goToPage(expenses.current_page + 1)" :disabled="!expenses.next_page_url"
                            class="px-3 py-1 text-sm border border-gray-300 rounded disabled:opacity-50 hover:bg-gray-100">
                        Next
                    </button>
                </div>
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
            <h2 class="mb-4 text-xl font-bold text-gray-800" x-text="editingExpense ? 'Edit Transaction' : 'Add Transaction'"></h2>

            <form @submit.prevent="saveExpense()">
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Type*</label>
                        <select x-model="form.type" required @change="filterCategoriesByType()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Amount*</label>
                        <input type="number" step="0.01" x-model="form.amount" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-700">Category*</label>
                    <select x-model="form.category_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Category</option>
                        <template x-for="category in filteredCategories" :key="category.id">
                            <option :value="category.id" x-text="category.name"></option>
                        </template>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-700">Date*</label>
                    <input type="date" x-model="form.date" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-700">Description</label>
                    <textarea x-model="form.description" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Payment Method</label>
                        <input type="text" x-model="form.payment_method"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Reference</label>
                        <input type="text" x-model="form.reference"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" @click="closeModal()"
                            class="px-4 py-2 text-gray-700 transition-colors bg-gray-100 rounded-lg hover:bg-gray-200">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700">
                        <span x-text="editingExpense ? 'Update' : 'Create'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function expensesData() {
    return {
        expenses: { data: [] },
        categories: [],
        filteredCategories: [],
        showModal: false,
        editingExpense: null,
        filters: {
            type: '',
            category_id: '',
            start_date: '',
            end_date: ''
        },
        form: {
            user_id: 1, // TODO: Get from auth
            category_id: '',
            amount: '',
            type: 'expense',
            description: '',
            date: new Date().toISOString().split('T')[0],
            payment_method: '',
            reference: ''
        },
        async init() {
            await this.fetchCategories();
            await this.fetchExpenses();
            this.filterCategoriesByType();
        },
        async fetchExpenses(page = 1) {
            try {
                const params = new URLSearchParams({ page: page.toString() });
                if (this.filters.type) params.append('type', this.filters.type);
                if (this.filters.category_id) params.append('category_id', this.filters.category_id);
                if (this.filters.start_date) params.append('start_date', this.filters.start_date);
                if (this.filters.end_date) params.append('end_date', this.filters.end_date);

                const response = await fetch(`/api/v1/expenses?${params}`);
                if (response.ok) {
                    this.expenses = await response.json();
                }
            } catch (error) {
                console.error('Error fetching expenses:', error);
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
        filterCategoriesByType() {
            this.filteredCategories = this.categories.filter(c => c.type === this.form.type);
        },
        goToPage(page) {
            this.fetchExpenses(page);
        },
        openModal() {
            this.showModal = true;
            this.editingExpense = null;
            this.form = {
                user_id: 1,
                category_id: '',
                amount: '',
                type: 'expense',
                description: '',
                date: new Date().toISOString().split('T')[0],
                payment_method: '',
                reference: ''
            };
            this.filterCategoriesByType();
        },
        closeModal() {
            this.showModal = false;
            this.editingExpense = null;
        },
        editExpense(expense) {
            this.editingExpense = expense;
            this.form = {
                user_id: expense.user_id,
                category_id: expense.category_id,
                amount: expense.amount,
                type: expense.type,
                description: expense.description || '',
                date: expense.date,
                payment_method: expense.payment_method || '',
                reference: expense.reference || ''
            };
            this.filterCategoriesByType();
            this.showModal = true;
        },
        async saveExpense() {
            try {
                const url = this.editingExpense
                    ? `/api/v1/expenses/${this.editingExpense.id}`
                    : '/api/v1/expenses';
                const method = this.editingExpense ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.form)
                });

                if (response.ok) {
                    await this.fetchExpenses();
                    this.closeModal();
                } else {
                    const error = await response.json();
                    alert('Error: ' + (error.message || 'Failed to save transaction'));
                }
            } catch (error) {
                console.error('Error saving expense:', error);
                alert('Error saving transaction');
            }
        },
        async deleteExpense(id) {
            if (!confirm('Are you sure you want to delete this transaction?')) return;

            try {
                const response = await fetch(`/api/v1/expenses/${id}`, {
                    method: 'DELETE'
                });

                if (response.ok) {
                    await this.fetchExpenses();
                }
            } catch (error) {
                console.error('Error deleting expense:', error);
            }
        },
        formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(amount || 0);
        },
        formatDate(date) {
            return new Date(date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }
    }
}
</script>
@endsection
