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
    <div class="flex gap-4 mb-6">
        <select x-model="filters.type" @change="fetchRecurring()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="">All Types</option>
            <option value="income">Income</option>
            <option value="expense">Expense</option>
        </select>
        <select x-model="filters.frequency" @change="fetchRecurring()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="">All Frequencies</option>
            <option value="daily">Daily</option>
            <option value="weekly">Weekly</option>
            <option value="monthly">Monthly</option>
            <option value="yearly">Yearly</option>
        </select>
        <select x-model="filters.is_active" @change="fetchRecurring()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="">All Status</option>
            <option value="1">Active</option>
            <option value="0">Inactive</option>
        </select>
    </div>

    <!-- Recurring List -->
    <div class="overflow-hidden bg-white rounded-lg shadow">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Description</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Frequency</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Next Run</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="item in recurringList" :key="item.id">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-gray-900" x-text="item.description || 'No description'"></div>
                                    <div class="text-sm text-gray-500" x-text="item.category?.name"></div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-semibold" :class="item.type === 'income' ? 'text-green-600' : 'text-red-600'" x-text="formatCurrency(item.amount)"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded-full" :class="item.type === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" x-text="item.type"></span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap capitalize" x-text="item.frequency"></td>
                            <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap" x-text="formatDate(getNextRunDate(item))"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded-full" :class="item.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'" x-text="item.is_active ? 'Active' : 'Inactive'"></span>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                <button @click="toggleStatus(item)" class="mr-3 text-blue-600 hover:text-blue-900" x-text="item.is_active ? 'Pause' : 'Activate'"></button>
                                <button @click="generateNow(item)" class="mr-3 text-green-600 hover:text-green-900" x-show="item.is_active">Generate</button>
                                <button @click="editRecurring(item)" class="mr-3 text-indigo-600 hover:text-indigo-900">Edit</button>
                                <button @click="deleteRecurring(item.id)" class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="recurringList.length === 0">
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            No recurring transactions found. Click "Add Recurring" to create one.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Form Modal -->
    <div x-show="showForm" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="showForm = false"></div>
            <div class="relative w-full max-w-2xl p-6 bg-white rounded-lg shadow-xl">
                <h3 class="mb-4 text-xl font-semibold text-gray-900" x-text="form.id ? 'Edit Recurring Transaction' : 'New Recurring Transaction'"></h3>
                <form @submit.prevent="saveRecurring()">
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
                        <div x-show="form.frequency === 'weekly'">
                            <label class="block mb-2 text-sm font-medium text-gray-700">Day of Week</label>
                            <select x-model="form.day_of_week" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="0">Sunday</option>
                                <option value="1">Monday</option>
                                <option value="2">Tuesday</option>
                                <option value="3">Wednesday</option>
                                <option value="4">Thursday</option>
                                <option value="5">Friday</option>
                                <option value="6">Saturday</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" @click="showForm = false" class="px-4 py-2 text-gray-700 transition-colors bg-gray-200 rounded-lg hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700">
                            <span x-text="form.id ? 'Update' : 'Create'"></span>
                        </button>
                    </div>
                </form>
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
            user_id: 1,
            category_id: '',
            amount: '',
            type: 'expense',
            description: '',
            payment_method: '',
            frequency: 'monthly',
            start_date: '',
            end_date: '',
            day_of_month: null,
            day_of_week: null
        },
        async init() {
            await this.fetchCategories();
            await this.fetchRecurring();
        },
        async fetchCategories() {
            try {
                const response = await fetch('/api/v1/categories?user_id=1');
                if (response.ok) {
                    const data = await response.json();
                    this.categories = data.data || data;
                }
            } catch (error) {
                console.error('Error fetching categories:', error);
            }
        },
        async fetchRecurring() {
            try {
                let url = '/api/v1/recurring-expenses?user_id=1';
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

                const response = await fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.form)
                });

                if (response.ok) {
                    this.showForm = false;
                    await this.fetchRecurring();
                    this.resetForm();
                }
            } catch (error) {
                console.error('Error saving recurring:', error);
            }
        },
        editRecurring(item) {
            this.form = { ...item };
            this.showForm = true;
        },
        async deleteRecurring(id) {
            if (!confirm('Are you sure you want to delete this recurring transaction?')) return;

            try {
                const response = await fetch(`/api/v1/recurring-expenses/${id}`, {
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
                const response = await fetch(`/api/v1/recurring-expenses/${item.id}/toggle`, {
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
                const response = await fetch(`/api/v1/recurring-expenses/${item.id}/generate`, {
                    method: 'POST'
                });
                if (response.ok) {
                    alert('Expense generated successfully!');
                    await this.fetchRecurring();
                }
            } catch (error) {
                console.error('Error generating expense:', error);
            }
        },
        resetForm() {
            this.form = {
                user_id: 1,
                category_id: '',
                amount: '',
                type: 'expense',
                description: '',
                payment_method: '',
                frequency: 'monthly',
                start_date: '',
                end_date: '',
                day_of_month: null,
                day_of_week: null
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
