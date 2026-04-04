@extends('layouts.app')

@section('title', 'Transactions - Expense Tracker')
@section('page-title', 'Transactions')

@section('content')
<div x-data="expensesData">
    <!-- Header -->
    <div class="flex flex-col mb-6 space-y-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Transactions</h1>
            <p class="text-gray-600">Track all your income and expenses</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <!-- Voice input — hidden on native Capacitor (not supported in WebView) -->
            <button x-show="!isNative" @click="startVoiceInput()"
                    :class="listening ? 'bg-red-500 hover:bg-red-600 animate-pulse' : 'bg-purple-600 hover:bg-purple-700'"
                    class="px-3 py-2 text-white transition-colors rounded-lg flex items-center gap-2 text-sm"
                    title="Voice: say 'Add expense, lunch, P85'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-7a3 3 0 01-3-3V5a3 3 0 016 0v6a3 3 0 01-3 3z"></path>
                </svg>
                <span x-text="listening ? 'Listening…' : 'Voice'"></span>
            </button>
            <!-- SMS import button -->
            <button @click="openSmsModal()"
                    class="px-3 py-2 text-white transition-colors bg-green-600 hover:bg-green-700 rounded-lg flex items-center gap-2 text-sm"
                    title="Copy an Orange Money or MyZaka SMS and paste it here">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                </svg>
                Import SMS
            </button>
            <button @click="openModal()" class="px-3 py-2 text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700 flex items-center gap-2 text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Add Transaction
            </button>
        </div>
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

    <!-- Voice hint toast -->
    <div x-show="voiceHint" x-transition
         class="fixed bottom-6 left-1/2 -translate-x-1/2 bg-gray-900 text-white px-5 py-3 rounded-xl shadow-lg z-50 text-sm"
         style="display:none">
        <span x-text="voiceHint"></span>
    </div>

    <!-- SMS Import Modal -->
    <div x-show="showSmsModal"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900 bg-opacity-50"
         style="display: none;">
        <div @click.away="showSmsModal = false"
             class="w-full max-w-lg bg-white rounded-lg shadow-xl flex flex-col"
             style="max-height: 85vh;">
            <!-- Header -->
            <div class="p-5 border-b border-gray-100">
                <h2 class="text-xl font-bold text-gray-800">Import from SMS</h2>
                <p class="mt-1 text-sm text-gray-500" x-text="isNative ? 'Tap a transaction SMS to import it.' : 'Paste an SMS notification below.'"></p>
            </div>

            <!-- Native: SMS list picker -->
            <template x-if="isNative">
                <div class="flex-1 overflow-y-auto">
                    <!-- Loading -->
                    <div x-show="smsLoading" class="p-6 text-center text-gray-500 text-sm">
                        <svg class="animate-spin mx-auto mb-2 w-6 h-6 text-green-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        Reading SMS inbox…
                    </div>

                    <!-- SMS list -->
                    <div x-show="!smsLoading && smsList.length > 0 && !smsPreview" class="divide-y divide-gray-100">
                        <template x-for="msg in smsList" :key="msg.id">
                            <button type="button" @click="selectSms(msg.body)"
                                    class="w-full text-left px-4 py-3 hover:bg-green-50 active:bg-green-100 transition-colors">
                                <p class="text-xs text-gray-400 mb-1" x-text="new Date(msg.date).toLocaleString()"></p>
                                <p class="text-sm text-gray-800 font-medium" x-text="msg.address"></p>
                                <p class="text-xs text-gray-600 mt-0.5 line-clamp-2" x-text="msg.body"></p>
                            </button>
                        </template>
                    </div>

                    <!-- Preview (after selecting) -->
                    <div x-show="smsPreview && !smsLoading" class="p-4">
                        <p class="text-xs text-gray-500 font-mono bg-gray-50 rounded p-2 mb-3 line-clamp-3" x-text="smsText"></p>
                        <div class="p-3 bg-green-50 border border-green-200 rounded-lg text-sm">
                            <p class="font-semibold text-green-800 mb-1">Parsed successfully:</p>
                            <p x-text="`${smsPreview?.type?.toUpperCase()} · ${smsPreview?.category} · P${smsPreview?.amount}`" class="text-green-700"></p>
                            <p x-text="smsPreview?.description" class="text-green-600 text-xs mt-1"></p>
                        </div>
                        <button type="button" @click="smsPreview = null; smsText = ''"
                                class="mt-3 text-xs text-blue-600 hover:underline">← Pick a different SMS</button>
                    </div>
                </div>
            </template>

            <!-- Web: paste textarea -->
            <template x-if="!isNative">
                <div class="p-5 flex-1">
                    <textarea x-model="smsText" rows="5" placeholder="Paste SMS here…"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-sm font-mono"></textarea>
                    <div x-show="smsPreview" class="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg text-sm">
                        <p class="font-semibold text-green-800 mb-1">Parsed successfully:</p>
                        <p x-text="`${smsPreview?.type?.toUpperCase()} · ${smsPreview?.category} · P${smsPreview?.amount}`" class="text-green-700"></p>
                        <p x-text="smsPreview?.description" class="text-green-600 text-xs mt-1"></p>
                    </div>
                </div>
            </template>

            <!-- Error -->
            <div x-show="smsError" class="mx-5 mb-2 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700" x-text="smsError"></div>

            <!-- Footer -->
            <div class="p-4 border-t border-gray-100 flex justify-end gap-3">
                <button type="button" @click="showSmsModal = false"
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Cancel</button>
                <template x-if="!isNative">
                    <button type="button" @click="previewSms()"
                            class="px-4 py-2 text-white bg-gray-600 rounded-lg hover:bg-gray-700">Preview</button>
                </template>
                <button type="button" @click="importSms()" :disabled="!smsPreview"
                        class="px-4 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-50">Import</button>
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


@endsection
