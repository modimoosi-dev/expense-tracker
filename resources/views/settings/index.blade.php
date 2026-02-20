@extends('layouts.app')

@section('title', 'Settings - Expense Tracker')
@section('page-title', 'Settings')

@section('content')
<div x-data="settingsData()" x-init="init()">
    <div class="max-w-4xl">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Settings</h1>
            <p class="text-gray-600">Manage your account preferences</p>
        </div>

        <!-- Settings Form -->
        <div class="p-6 bg-white rounded-lg shadow">
            <form @submit.prevent="saveSettings()">
                <!-- Profile Settings -->
                <div class="pb-6 mb-6 border-b border-gray-200">
                    <h2 class="mb-4 text-lg font-semibold text-gray-800">Profile Information</h2>

                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Name</label>
                        <input type="text" x-model="form.name" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Email</label>
                        <input type="email" x-model="settings.email" disabled
                               class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg cursor-not-allowed">
                        <p class="mt-1 text-xs text-gray-500">Email cannot be changed</p>
                    </div>
                </div>

                <!-- Currency Settings -->
                <div class="pb-6 mb-6 border-b border-gray-200">
                    <h2 class="mb-4 text-lg font-semibold text-gray-800">Currency Preferences</h2>

                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Default Currency</label>
                        <select x-model="form.currency" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <template x-for="currency in currencies" :key="currency.code">
                                <option :value="currency.code" x-text="`${currency.code} - ${currency.name} (${currency.symbol})`"></option>
                            </template>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">All amounts will be displayed in this currency</p>
                    </div>

                    <!-- Currency Preview -->
                    <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-center mb-2">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-sm font-medium text-blue-800">Preview</span>
                        </div>
                        <p class="text-sm text-blue-700">
                            Example: <span class="font-semibold" x-text="formatPreview(1234.56)"></span>
                        </p>
                    </div>
                </div>

                <!-- Data Statistics -->
                <div class="pb-6 mb-6 border-b border-gray-200">
                    <h2 class="mb-4 text-lg font-semibold text-gray-800">Account Statistics</h2>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <p class="text-sm text-gray-600">Total Categories</p>
                            <p class="text-2xl font-bold text-gray-900" x-text="stats.categories"></p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <p class="text-sm text-gray-600">Total Transactions</p>
                            <p class="text-2xl font-bold text-gray-900" x-text="stats.transactions"></p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <p class="text-sm text-gray-600">Active Budgets</p>
                            <p class="text-2xl font-bold text-gray-900" x-text="stats.budgets"></p>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="flex justify-end">
                    <button type="submit"
                            class="px-6 py-2 text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700"
                            :disabled="saving">
                        <span x-show="!saving">Save Changes</span>
                        <span x-show="saving">Saving...</span>
                    </button>
                </div>
            </form>

            <!-- Success Message -->
            <div x-show="showSuccess"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed bottom-4 right-4 px-6 py-3 bg-green-500 text-white rounded-lg shadow-lg"
                 style="display: none;">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Settings saved successfully!
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function settingsData() {
    return {
        settings: {
            name: '',
            email: '',
            currency: 'USD'
        },
        form: {
            user_id: 1,
            name: '',
            currency: 'USD'
        },
        currencies: [],
        stats: {
            categories: 0,
            transactions: 0,
            budgets: 0
        },
        saving: false,
        showSuccess: false,
        async init() {
            await this.fetchCurrencies();
            await this.fetchSettings();
            await this.fetchStats();
        },
        async fetchSettings() {
            try {
                const response = await fetch('/api/v1/settings?user_id=1');
                if (response.ok) {
                    this.settings = await response.json();
                    this.form.name = this.settings.name;
                    this.form.currency = this.settings.currency;

                    // Store currency globally for other pages
                    localStorage.setItem('userCurrency', this.settings.currency);
                }
            } catch (error) {
                console.error('Error fetching settings:', error);
            }
        },
        async fetchCurrencies() {
            try {
                const response = await fetch('/api/v1/settings/currencies');
                if (response.ok) {
                    this.currencies = await response.json();
                }
            } catch (error) {
                console.error('Error fetching currencies:', error);
            }
        },
        async fetchStats() {
            try {
                const [categories, expenses, budgets] = await Promise.all([
                    fetch('/api/v1/categories').then(r => r.json()),
                    fetch('/api/v1/expenses').then(r => r.json()),
                    fetch('/api/v1/budgets?user_id=1').then(r => r.json())
                ]);

                this.stats.categories = categories.length || 0;
                this.stats.transactions = expenses.total || 0;
                this.stats.budgets = budgets.filter(b => b.is_active).length || 0;
            } catch (error) {
                console.error('Error fetching stats:', error);
            }
        },
        async saveSettings() {
            this.saving = true;
            try {
                const response = await fetch('/api/v1/settings', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.form)
                });

                if (response.ok) {
                    const data = await response.json();
                    this.settings.currency = data.currency;
                    localStorage.setItem('userCurrency', data.currency);

                    // Show success message
                    this.showSuccess = true;
                    setTimeout(() => {
                        this.showSuccess = false;
                    }, 3000);

                    // Reload page to apply currency changes
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            } catch (error) {
                console.error('Error saving settings:', error);
                alert('Failed to save settings');
            } finally {
                this.saving = false;
            }
        },
        formatPreview(amount) {
            const currency = this.currencies.find(c => c.code === this.form.currency);
            if (!currency) return amount;

            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: this.form.currency
            }).format(amount);
        }
    }
}
</script>
@endsection
