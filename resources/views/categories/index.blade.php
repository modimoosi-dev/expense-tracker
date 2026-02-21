@extends('layouts.app')

@section('title', 'Categories - Expense Tracker')
@section('page-title', 'Categories')

@section('content')
<div x-data="categoriesData()" x-init="init()">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Categories</h1>
            <p class="text-gray-600">Manage your income and expense categories</p>
        </div>
        <button @click="openModal()" class="px-4 py-2 text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700">
            <svg class="inline-block w-5 h-5 mr-2 -mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Add Category
        </button>
    </div>

    <!-- Filter Tabs -->
    <div class="mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px space-x-8">
                <button @click="filterType = 'all'; fetchCategories()"
                        :class="filterType === 'all' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="px-1 py-4 text-sm font-medium border-b-2">
                    All
                </button>
                <button @click="filterType = 'income'; fetchCategories()"
                        :class="filterType === 'income' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="px-1 py-4 text-sm font-medium border-b-2">
                    Income
                </button>
                <button @click="filterType = 'expense'; fetchCategories()"
                        :class="filterType === 'expense' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="px-1 py-4 text-sm font-medium border-b-2">
                    Expense
                </button>
            </nav>
        </div>
    </div>

    <!-- Categories Grid -->
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        <template x-for="category in categories" :key="category.id">
            <div class="p-6 transition-shadow bg-white border border-gray-200 rounded-lg hover:shadow-md">
                <div class="flex items-start justify-between">
                    <div class="flex items-center flex-1">
                        <div class="w-12 h-12 rounded-lg flex items-center justify-center mr-4" :style="`background-color: ${category.color}20`">
                            <div class="w-6 h-6 rounded-full" :style="`background-color: ${category.color}`"></div>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800" x-text="category.name"></h3>
                            <span class="inline-block px-2 py-1 mt-1 text-xs font-medium rounded-full"
                                  :class="category.type === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                  x-text="category.type.charAt(0).toUpperCase() + category.type.slice(1)"></span>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <button @click="editCategory(category)" class="text-gray-400 hover:text-blue-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </button>
                        <button @click="deleteCategory(category.id)" class="text-gray-400 hover:text-red-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <div x-show="categories.length === 0" class="col-span-full py-12 text-center text-gray-500">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
            </svg>
            <p class="text-lg font-medium">No categories found</p>
            <p class="text-sm">Create your first category to get started</p>
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
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900 bg-opacity-50"
         style="display: none;">
        <div @click.away="closeModal()"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-95"
             class="w-full max-w-md p-6 bg-white rounded-lg shadow-xl">
            <h2 class="mb-4 text-xl font-bold text-gray-800" x-text="editingCategory ? 'Edit Category' : 'Add Category'"></h2>

            <form @submit.prevent="saveCategory()">
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-700">Name</label>
                    <input type="text" x-model="form.name" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-700">Type</label>
                    <select x-model="form.type" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="income">Income</option>
                        <option value="expense">Expense</option>
                    </select>
                </div>

                <div class="mb-6">
                    <label class="block mb-2 text-sm font-medium text-gray-700">Color</label>
                    <div class="flex items-center space-x-2">
                        <input type="color" x-model="form.color"
                               class="w-16 h-10 border border-gray-300 rounded cursor-pointer">
                        <input type="text" x-model="form.color" placeholder="#3B82F6"
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" @click="closeModal()"
                            class="px-4 py-2 text-gray-700 transition-colors bg-gray-100 rounded-lg hover:bg-gray-200">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700">
                        <span x-text="editingCategory ? 'Update' : 'Create'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function categoriesData() {
    return {
        categories: [],
        showModal: false,
        editingCategory: null,
        filterType: 'all',
        form: {
            name: '',
            type: 'expense',
            color: '#3B82F6'
        },
        async init() {
            await this.fetchCategories();
        },
        async fetchCategories() {
            try {
                const url = this.filterType === 'all'
                    ? '/api/v1/categories'
                    : `/api/v1/categories?type=${this.filterType}`;
                const response = await fetch(url);
                if (response.ok) {
                    this.categories = await response.json();
                }
            } catch (error) {
                console.error('Error fetching categories:', error);
            }
        },
        openModal() {
            this.showModal = true;
            this.editingCategory = null;
            this.form = { name: '', type: 'expense', color: '#3B82F6' };
        },
        closeModal() {
            this.showModal = false;
            this.editingCategory = null;
        },
        editCategory(category) {
            this.editingCategory = category;
            this.form = { ...category };
            this.showModal = true;
        },
        async saveCategory() {
            try {
                const url = this.editingCategory
                    ? `/api/v1/categories/${this.editingCategory.id}`
                    : '/api/v1/categories';
                const method = this.editingCategory ? 'PUT' : 'POST';

                const response = await window.fetchWithCsrf(url, {
                    method: method,
                    body: JSON.stringify(this.form)
                });

                if (response.ok) {
                    await this.fetchCategories();
                    this.closeModal();
                }
            } catch (error) {
                console.error('Error saving category:', error);
            }
        },
        async deleteCategory(id) {
            if (!confirm('Are you sure you want to delete this category?')) return;

            try {
                const response = await window.fetchWithCsrf(`/api/v1/categories/${id}`, {
                    method: 'DELETE'
                });

                if (response.ok) {
                    await this.fetchCategories();
                }
            } catch (error) {
                console.error('Error deleting category:', error);
            }
        }
    }
}
</script>
@endsection
