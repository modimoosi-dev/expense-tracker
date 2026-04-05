@extends('layouts.app')

@section('title', 'Recurring Transactions - Expense Tracker')
@section('page-title', 'Recurring Transactions')

@section('content')
<div x-data="recurringData()" x-init="init()">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Recurring Transactions</h1>
            <p class="text-gray-600">Manage your recurring income and expenses</p>
        </div>
        <button @click="showForm = true; resetForm()" class="px-4 py-2 text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700">
            <svg class="inline-block w-5 h-5 mr-2 -mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Add Recurring
        </button>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-3 mb-6">
        <select x-model="filters.type" @change="fetchRecurring()" class="flex-1 min-w-[120px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
            <option value="">All Types</option>
            <option value="income">Income</option>
            <option value="expense">Expense</option>
        </select>
        <select x-model="filters.frequency" @change="fetchRecurring()" class="flex-1 min-w-[130px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
            <option value="">All Frequencies</option>
            <option value="daily">Daily</option>
            <option value="weekly">Weekly</option>
            <option value="monthly">Monthly</option>
            <option value="yearly">Yearly</option>
        </select>
        <select x-model="filters.is_active" @change="fetchRecurring()" class="flex-1 min-w-[110px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
            <option value="">All Status</option>
            <option value="1">Active</option>
            <option value="0">Inactive</option>
        </select>
    </div>

    <!-- Recurring List -->
    <div class="space-y-3">
        <template x-for="item in recurringList" :key="item.id">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-semibold text-gray-900 truncate" x-text="item.description || 'No description'"></span>
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full shrink-0"
                                  :class="item.type === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                  x-text="item.type"></span>
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full shrink-0"
                                  :class="item.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                                  x-text="item.is_active ? 'Active' : 'Inactive'"></span>
                        </div>
                        <div class="mt-1 text-sm text-gray-500" x-text="item.category?.name"></div>
                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-600">
                            <span class="font-semibold" :class="item.type === 'income' ? 'text-green-600' : 'text-red-600'" x-text="formatCurrency(item.amount)"></span>
                            <span class="capitalize" x-text="item.frequency"></span>
                            <span x-text="'Next: ' + formatDate(getNextRunDate(item))"></span>
                        </div>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap gap-2 border-t border-gray-100 pt-3">
                    <button @click="toggleStatus(item)"
                            class="px-3 py-1 text-xs font-medium rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100"
                            x-text="item.is_active ? 'Pause' : 'Activate'"></button>
                    <button @click="generateNow(item)" x-show="item.is_active"
                            class="px-3 py-1 text-xs font-medium rounded-lg bg-green-50 text-green-600 hover:bg-green-100">Generate</button>
                    <button @click="editRecurring(item)"
                            class="px-3 py-1 text-xs font-medium rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100">Edit</button>
                    <button @click="deleteRecurring(item.id)"
                            class="px-3 py-1 text-xs font-medium rounded-lg bg-red-50 text-red-600 hover:bg-red-100">Delete</button>
                </div>
            </div>
        </template>
        <div x-show="recurringList.length === 0" class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-10 text-center text-gray-500">
            No recurring transactions found. Tap "Add Recurring" to create one.
        </div>
    </div>

    <!-- Form Modal -->
    <div x-show="showForm" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center px-4 pt-4 pb-20 sm:pb-4" style="display: none;">
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="showForm = false"></div>
        <div class="relative w-full max-w-2xl bg-white rounded-xl shadow-xl flex flex-col" style="max-height: calc(100vh - 5rem);">
            <!-- Header -->
            <div class="px-6 pt-6 pb-4 border-b border-gray-100 shrink-0">
                <h3 class="text-xl font-semibold text-gray-900" x-text="form.id ? 'Edit Recurring Transaction' : 'New Recurring Transaction'"></h3>
            </div>
            <!-- Scrollable body -->
            <div class="overflow-y-auto flex-1 px-6 py-4">
                <form id="recurringForm" @submit.prevent="saveRecurring()">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block mb-2 text-sm font-medium text-gray-700">Type</label>
                            <select x-model="form.type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="expense">Expense</option>
                                <option value="income">Income</option>
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="block mb-2 text-sm font-medium text-gray-700">Category</label>
                            <select x-model="form.category_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select category</option>
                                <template x-for="cat in categories" :key="cat.id">
                                    <option :value="cat.id" x-text="cat.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Amount</label>
                            <input type="number" step="0.01" x-model="form.amount" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Frequency</label>
                            <select x-model="form.frequency" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="block mb-2 text-sm font-medium text-gray-700">Description</label>
                            <input type="text" x-model="form.description" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Payment Method</label>
                            <input type="text" x-model="form.payment_method" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Start Date</label>
                            <input type="date" x-model="form.start_date" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">End Date (Optional)</label>
                            <input type="date" x-model="form.end_date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div x-show="form.frequency === 'monthly'">
                            <label class="block mb-2 text-sm font-medium text-gray-700">Day of Month (1-31)</label>
                            <input type="number" min="1" max="31" x-model="form.day_of_month" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div class="col-span-2">
                            <label class="block mb-1.5 text-sm font-medium text-gray-700">
                                Active Days
                                <span class="text-gray-400 font-normal">(optional — leave blank for every day)</span>
                            </label>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="(day, idx) in ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']" :key="idx">
                                    <label class="cursor-pointer">
                                        <input type="checkbox" class="sr-only"
                                               :value="idx"
                                               :checked="form.days_of_week.includes(idx)"
                                               @change="toggleDay(idx)">
                                        <span class="inline-block px-3 py-1.5 rounded-lg text-sm font-semibold border-2 transition-colors"
                                              :class="form.days_of_week.includes(idx)
                                                ? 'bg-blue-600 border-blue-600 text-white'
                                                : 'bg-white border-gray-300 text-gray-600 hover:border-blue-400'"
                                              x-text="day"></span>
                                    </label>
                                </template>
                            </div>
                            <p class="mt-1.5 text-xs text-gray-400">
                                e.g. Mon–Fri for weekday-only transport. Works with any frequency.
                            </p>
                        </div>
                    </div>
                </form>
            </div>
            <!-- Pinned footer -->
            <div class="px-6 py-4 border-t border-gray-100 shrink-0 flex justify-end gap-3">
                <button type="button" @click="showForm = false" class="px-4 py-2 text-gray-700 transition-colors bg-gray-200 rounded-lg hover:bg-gray-300">
                    Cancel
                </button>
                <button type="submit" form="recurringForm" class="px-4 py-2 text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700">
                    <span x-text="form.id ? 'Update' : 'Create'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function recurringData() {
    return {
        recurringList: [],
        categories: [],
        showForm: false,
        filters: {
            type: '',
            frequency: '',
            is_active: ''
        },
        form: {
            user_id: {{ auth()->id() }},
            category_id: '',
            amount: '',
            type: 'expense',
            description: '',
            payment_method: '',
            frequency: 'monthly',
            start_date: '',
            end_date: '',
            day_of_month: null,
            day_of_week: null,
            days_of_week: []
        },
        async init() {
            await this.fetchCategories();
            await this.fetchRecurring();
        },
        async fetchCategories() {
            try {
                this.categories = await window.getUserCategories();
            } catch (error) {
                console.error('Error fetching categories:', error);
            }
        },
        async fetchRecurring() {
            try {
                let url = '/api/v1/recurring-expenses?user_id={{ auth()->id() }}';
                if (this.filters.type) url += `&type=${this.filters.type}`;
                if (this.filters.frequency) url += `&frequency=${this.filters.frequency}`;
                if (this.filters.is_active !== '') url += `&is_active=${this.filters.is_active}`;

                const response = await fetch(url);
                if (response.ok) {
                    const data = await response.json();
                    this.recurringList = data.data || data;
                }
            } catch (error) {
                console.error('Error fetching recurring:', error);
            }
        },
        async saveRecurring() {
            try {
                const url = this.form.id
                    ? `/api/v1/recurring-expenses/${this.form.id}`
                    : '/api/v1/recurring-expenses';
                const method = this.form.id ? 'PUT' : 'POST';

                const response = await window.fetchWithCsrf(url, {
                    method: method,
                    body: JSON.stringify(this.form)
                });

                if (response.ok) {
                    this.showForm = false;
                    await this.fetchRecurring();
                    this.resetForm();
                } else {
                    const error = await response.text();
                    console.error('Error response:', error);
                    alert('Failed to save recurring transaction. Check console for details.');
                }
            } catch (error) {
                console.error('Error saving recurring:', error);
                alert('Failed to save recurring transaction.');
            }
        },
        editRecurring(item) {
            this.form = { ...item, days_of_week: Array.isArray(item.days_of_week) ? item.days_of_week : [] };
            this.showForm = true;
        },
        async deleteRecurring(id) {
            if (!confirm('Are you sure you want to delete this recurring transaction?')) return;

            try {
                const response = await window.fetchWithCsrf(`/api/v1/recurring-expenses/${id}`, {
                    method: 'DELETE'
                });
                if (response.ok) {
                    await this.fetchRecurring();
                }
            } catch (error) {
                console.error('Error deleting recurring:', error);
            }
        },
        async toggleStatus(item) {
            try {
                const response = await window.fetchWithCsrf(`/api/v1/recurring-expenses/${item.id}/toggle`, {
                    method: 'POST'
                });
                if (response.ok) {
                    await this.fetchRecurring();
                }
            } catch (error) {
                console.error('Error toggling status:', error);
            }
        },
        async generateNow(item) {
            if (!confirm('Generate an expense from this recurring transaction now?')) return;

            try {
                const today = new Date().toISOString().split('T')[0];
                await window.addFirestoreExpense({
                    category_id: item.category_id || '',
                    amount: parseFloat(item.amount),
                    type: item.type,
                    description: (item.description || '') + ' (Auto-generated)',
                    date: today,
                    payment_method: item.payment_method || '',
                });
                // Also update last_generated on the server
                await window.fetchWithCsrf(`/api/v1/recurring-expenses/${item.id}/generate`, { method: 'POST' });
                alert('Expense generated successfully!');
                await this.fetchRecurring();
            } catch (error) {
                console.error('Error generating expense:', error);
                alert('Failed to generate expense.');
            }
        },
        toggleDay(idx) {
            const pos = this.form.days_of_week.indexOf(idx);
            if (pos === -1) this.form.days_of_week.push(idx);
            else this.form.days_of_week.splice(pos, 1);
        },
        resetForm() {
            this.form = {
                user_id: {{ auth()->id() }},
                category_id: '',
                amount: '',
                type: 'expense',
                description: '',
                payment_method: '',
                frequency: 'monthly',
                start_date: '',
                end_date: '',
                day_of_month: null,
                day_of_week: null,
                days_of_week: []
            };
        },
        getNextRunDate(item) {
            if (!item.last_generated) return item.start_date;

            const lastDate = new Date(item.last_generated);
            const today = new Date();

            switch (item.frequency) {
                case 'daily':
                    lastDate.setDate(lastDate.getDate() + 1);
                    break;
                case 'weekly':
                    lastDate.setDate(lastDate.getDate() + 7);
                    break;
                case 'monthly':
                    lastDate.setMonth(lastDate.getMonth() + 1);
                    break;
                case 'yearly':
                    lastDate.setFullYear(lastDate.getFullYear() + 1);
                    break;
            }

            return lastDate > today ? lastDate.toISOString().split('T')[0] : today.toISOString().split('T')[0];
        },
        formatCurrency(amount) {
            return window.formatCurrency(amount);
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
