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
                    title="Import from SMS">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                </svg>
                Import SMS
            </button>
            <!-- Bank statement import button -->
            <button @click="openStatementModal()"
                    class="px-3 py-2 text-white transition-colors bg-orange-500 hover:bg-orange-600 rounded-lg flex items-center gap-2 text-sm"
                    title="Import from bank statement CSV">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Import Statement
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
             class="w-full max-w-lg bg-white rounded-xl shadow-xl">
            <div class="p-5 border-b border-gray-100">
                <h2 class="text-lg font-bold text-gray-800">Import from SMS</h2>
                <p class="mt-1 text-sm text-gray-500">Copy a transaction SMS from your messages app, then paste it below.</p>
            </div>

            <div class="p-5">
                <!-- Clipboard button (on native especially) -->
                <button type="button" @click="pasteFromClipboard()"
                        class="w-full mb-3 flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-50 text-indigo-700 font-medium text-sm rounded-lg hover:bg-indigo-100 transition-colors border border-indigo-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    Paste from Clipboard
                </button>

                <textarea x-model="smsText" rows="4" placeholder="Or paste SMS text here manually…"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-sm font-mono resize-none"></textarea>

                <div x-show="smsPreview" class="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg text-sm">
                    <p class="font-semibold text-green-800 mb-1">Parsed successfully</p>
                    <p x-text="`${smsPreview?.type?.toUpperCase()} · ${smsPreview?.category} · ${smsPreview?.amount}`" class="text-green-700"></p>
                    <p x-text="smsPreview?.description" class="text-green-600 text-xs mt-1"></p>
                </div>

                <div x-show="smsError" class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700" x-text="smsError"></div>
            </div>

            <div class="px-5 pb-5 flex justify-end gap-3">
                <button type="button" @click="showSmsModal = false"
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 text-sm">Cancel</button>
                <button type="button" @click="previewSms()" :disabled="!smsText.trim()"
                        class="px-4 py-2 text-white bg-gray-600 rounded-lg hover:bg-gray-700 disabled:opacity-40 text-sm">Preview</button>
                <button type="button" @click="importSms()" :disabled="!smsPreview"
                        class="px-4 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-40 text-sm">Import</button>
            </div>
        </div>
    </div>

    <!-- Bank Statement Import Modal -->
    <div x-show="showStatementModal"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900 bg-opacity-50"
         style="display: none;">
        <div @click.away="showStatementModal = false"
             class="w-full max-w-2xl bg-white rounded-xl shadow-xl flex flex-col"
             style="max-height: 90vh;">

            <!-- Header -->
            <div class="p-5 border-b border-gray-100">
                <h2 class="text-lg font-bold text-gray-800">Import Bank Statement</h2>
                <p class="mt-1 text-sm text-gray-500">Upload a CSV file exported from your bank. Supports FNB, Absa, Standard Chartered, Stanbic and most banks.</p>
            </div>

            <div class="flex-1 overflow-y-auto p-5">
                <!-- File upload -->
                <div x-show="statementRows.length === 0 && !statementLoading">
                    <label class="flex flex-col items-center justify-center w-full h-36 border-2 border-dashed border-orange-300 rounded-xl cursor-pointer bg-orange-50 hover:bg-orange-100 transition-colors">
                        <svg class="w-8 h-8 text-orange-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <p class="text-sm font-semibold text-orange-600">Tap to upload PDF or CSV</p>
                        <p class="text-xs text-orange-400 mt-1">Export statement from your bank's internet banking</p>
                        <input type="file" accept=".csv,.txt,.pdf" class="hidden" @change="uploadStatement($event)">
                    </label>
                    <div class="mt-4 p-3 bg-gray-50 rounded-lg text-xs text-gray-500 space-y-1">
                        <p class="font-semibold text-gray-600 mb-1">How to export:</p>
                        <p>• <strong>FNB:</strong> Online Banking → Accounts → Statement → Download → CSV</p>
                        <p>• <strong>Absa:</strong> Online Banking → Accounts → Download Statement → CSV</p>
                        <p>• <strong>Standard Chartered:</strong> Online Banking → Accounts → Export → CSV</p>
                        <p>• <strong>Stanbic:</strong> Online Banking → Statements → Export</p>
                    </div>
                </div>

                <!-- Loading -->
                <div x-show="statementLoading" class="py-12 text-center text-gray-500 text-sm">
                    <svg class="animate-spin mx-auto mb-3 w-8 h-8 text-orange-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    Parsing statement…
                </div>

                <!-- Password required -->
                <div x-show="statementNeedsPassword" class="mb-4">
                    <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                        <p class="text-sm font-semibold text-amber-800 mb-1">This PDF is password protected</p>
                        <p class="text-xs text-amber-600 mb-3">FNB statements are usually protected with your ID number or a custom password you set.</p>
                        <div class="flex gap-2">
                            <input type="password" x-model="statementPassword"
                                   placeholder="Enter PDF password…"
                                   @keydown.enter="submitStatementPassword()"
                                   class="flex-1 px-3 py-2 border border-amber-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                            <button type="button" @click="submitStatementPassword()"
                                    class="px-4 py-2 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600">
                                Unlock
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Error -->
                <div x-show="statementError && !statementNeedsPassword" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700" x-text="statementError"></div>
                <div x-show="statementError && statementNeedsPassword" class="mb-2 text-sm text-red-600" x-text="statementError"></div>

                <!-- Transaction preview table -->
                <div x-show="statementRows.length > 0">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-semibold text-gray-700">
                            <span x-text="statementSelected.length"></span> of <span x-text="statementRows.length"></span> transactions selected
                        </p>
                        <div class="flex gap-2">
                            <button type="button" @click="selectAllStatement()" class="text-xs text-indigo-600 hover:underline">All</button>
                            <span class="text-gray-300">|</span>
                            <button type="button" @click="deselectAllStatement()" class="text-xs text-gray-500 hover:underline">None</button>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <template x-for="(row, idx) in statementRows" :key="idx">
                            <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-colors"
                                   :class="statementSelected.includes(idx) ? 'border-orange-300 bg-orange-50' : 'border-gray-100 bg-white hover:bg-gray-50'">
                                <input type="checkbox" class="mt-0.5 shrink-0 accent-orange-500"
                                       :checked="statementSelected.includes(idx)"
                                       @change="toggleStatementRow(idx)">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-sm font-medium text-gray-800 truncate" x-text="row.description"></p>
                                        <span class="text-sm font-bold shrink-0"
                                              :class="row.type === 'income' ? 'text-green-600' : 'text-red-500'"
                                              x-text="(row.type === 'income' ? '+' : '-') + formatCurrency(row.amount)"></span>
                                    </div>
                                    <div class="flex items-center gap-3 mt-0.5">
                                        <span class="text-xs text-gray-400" x-text="row.date"></span>
                                        <span class="text-xs px-1.5 py-0.5 rounded-full font-medium"
                                              :class="row.type === 'income' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'"
                                              x-text="row.type"></span>
                                    </div>
                                </div>
                            </label>
                        </template>
                    </div>
                    <!-- Upload different file -->
                    <label class="mt-4 flex items-center gap-2 text-xs text-indigo-600 hover:underline cursor-pointer w-fit">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                        </svg>
                        Upload a different file
                        <input type="file" accept=".csv,.txt,.pdf" class="hidden" @change="uploadStatement($event)">
                    </label>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-4 border-t border-gray-100 flex justify-end gap-3">
                <button type="button" @click="showStatementModal = false"
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 text-sm">Cancel</button>
                <button type="button" @click="importStatement()"
                        :disabled="statementSelected.length === 0 || statementImporting"
                        class="px-4 py-2 text-white bg-orange-500 rounded-lg hover:bg-orange-600 disabled:opacity-40 text-sm flex items-center gap-2">
                    <svg x-show="statementImporting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span x-text="statementImporting ? 'Importing…' : `Import ${statementSelected.length} transactions`"></span>
                </button>
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
