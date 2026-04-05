<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->id() }}">
    <meta name="theme-color" content="#4f46e5">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="manifest" href="/manifest.json">
    <title>@yield('title', 'Expense Tracker')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Hide sidebar on mobile before JS loads — eliminates flash on navigation */
        @media (max-width: 1023px) {
            #app-sidebar { transform: translateX(-100%); }
        }
    </style>
    <script>
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-gradient-to-br from-gray-50 via-blue-50/30 to-purple-50/30 dark:from-gray-950 dark:via-gray-900 dark:to-gray-950 antialiased">
    <div x-data="layoutData()" x-init="init()" class="min-h-screen">
        <!-- Sidebar -->
        <aside id="app-sidebar"
               class="fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-gray-900 shadow-2xl dark:shadow-gray-950 transform lg:translate-x-0"
               :class="{ 'transition-transform duration-300 ease-in-out': initialized }"
               :style="isMobile ? (sidebarOpen ? 'transform: translateX(0)' : 'transform: translateX(-100%)') : ''"
               >
            <div class="flex flex-col h-full">
                <!-- Logo -->
                <div class="flex items-center justify-between h-16 px-6 bg-gradient-to-r from-indigo-600 to-purple-600">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h1 class="text-lg font-bold text-white">Expense Tracker</h1>
                    </div>
                    <button @click="sidebarOpen = false" class="lg:hidden text-white hover:text-gray-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-3 py-6 space-y-1 overflow-y-auto">
                    <a href="{{ route('dashboard') }}" class="group flex items-center px-4 py-3 text-gray-700 rounded-xl transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg shadow-indigo-500/50' : 'hover:bg-gray-50 dark:hover:bg-gray-800 hover:translate-x-1' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        Dashboard
                    </a>
                    <a href="{{ route('expenses.index') }}" class="group flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-xl transition-all duration-200 hover:bg-gray-50 dark:hover:bg-gray-800 hover:translate-x-1 {{ request()->routeIs('expenses.*') ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg shadow-indigo-500/50' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"></path>
                        </svg>
                        Transactions
                    </a>
                    <a href="{{ route('categories.index') }}" class="group flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-xl transition-all duration-200 hover:bg-gray-50 dark:hover:bg-gray-800 hover:translate-x-1 {{ request()->routeIs('categories.*') ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg shadow-indigo-500/50' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        Categories
                    </a>
                    <a href="{{ route('budgets.index') }}" class="group flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-xl transition-all duration-200 hover:bg-gray-50 dark:hover:bg-gray-800 hover:translate-x-1 {{ request()->routeIs('budgets.*') ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg shadow-indigo-500/50' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        Budgets
                    </a>
                    <a href="{{ route('recurring.index') }}" class="group flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-xl transition-all duration-200 hover:bg-gray-50 dark:hover:bg-gray-800 hover:translate-x-1 {{ request()->routeIs('recurring.*') ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg shadow-indigo-500/50' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Recurring
                    </a>
                    <a href="{{ route('reports.index') }}" class="group flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-xl transition-all duration-200 hover:bg-gray-50 dark:hover:bg-gray-800 hover:translate-x-1 {{ request()->routeIs('reports.*') ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg shadow-indigo-500/50' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Reports
                    </a>
                    <a href="{{ route('settings.index') }}" class="group flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-xl transition-all duration-200 hover:bg-gray-50 dark:hover:bg-gray-800 hover:translate-x-1 {{ request()->routeIs('settings.*') ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg shadow-indigo-500/50' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Settings
                    </a>

                    <!-- Quick Actions (mobile only) -->
                    <div class="lg:hidden mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Quick Actions</p>
                        <a href="{{ route('expenses.index') }}?type=expense" @click="sidebarOpen = false"
                           class="flex items-center px-4 py-2.5 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-red-50 dark:hover:bg-gray-800 hover:text-red-600 transition-all">
                            <span class="w-7 h-7 mr-3 bg-gradient-to-br from-red-500 to-pink-600 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                            </span>
                            Add Expense
                        </a>
                        <a href="{{ route('expenses.index') }}?type=income" @click="sidebarOpen = false"
                           class="flex items-center px-4 py-2.5 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-green-50 dark:hover:bg-gray-800 hover:text-green-600 transition-all">
                            <span class="w-7 h-7 mr-3 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            </span>
                            Add Income
                        </a>
                        <a href="{{ route('expenses.index') }}?import=statement" @click="sidebarOpen = false"
                           class="flex items-center px-4 py-2.5 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-orange-50 dark:hover:bg-gray-800 hover:text-orange-600 transition-all">
                            <span class="w-7 h-7 mr-3 bg-gradient-to-br from-orange-500 to-amber-500 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </span>
                            Import Statement
                        </a>
                        <a href="{{ route('recurring.index') }}" @click="sidebarOpen = false"
                           class="flex items-center px-4 py-2.5 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-purple-50 dark:hover:bg-gray-800 hover:text-purple-600 transition-all">
                            <span class="w-7 h-7 mr-3 bg-gradient-to-br from-purple-500 to-violet-600 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            </span>
                            Recurring
                        </a>
                    </div>
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="lg:pl-64">
            <!-- Top Bar: full on desktop, minimal app-style on mobile -->
            <header class="sticky top-0 z-40 bg-white/90 dark:bg-gray-900/90 backdrop-blur-lg border-b border-gray-200/50 dark:border-gray-700/50 shadow-sm">
                <!-- Desktop header -->
                <div class="hidden lg:flex items-center justify-between h-16 px-6">
                    <h2 class="text-xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">@yield('page-title', 'Dashboard')</h2>
                    <div class="flex items-center space-x-2">
                        <a href="{{ route('settings.index') }}" class="flex items-center space-x-3 px-4 py-2 rounded-xl hover:bg-gray-50 transition-all group">
                            <span class="text-sm font-medium text-gray-600 group-hover:text-gray-900" x-text="'Welcome, ' + userName"></span>
                            <div class="relative">
                                <img :src="userProfilePicture || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(userName) + '&size=80&background=6366F1&color=fff'"
                                     class="w-10 h-10 rounded-full object-cover ring-2 ring-gray-200 group-hover:ring-indigo-500 transition-all"
                                     :alt="userName">
                                <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 rounded-full border-2 border-white"></div>
                            </div>
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="p-2 text-gray-600 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all" title="Logout">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
                <!-- Mobile header: app-style status bar -->
                <div class="lg:hidden flex items-center justify-between h-14 px-4">
                    <button @click="sidebarOpen = true" class="p-2 -ml-2 text-gray-500 active:bg-gray-100 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">@yield('page-title', 'Dashboard')</h2>
                    <div class="flex items-center gap-1">
                        <button @click="toggleDark()" class="p-2 text-gray-500 dark:text-gray-400 active:bg-gray-100 dark:active:bg-gray-800 rounded-lg" title="Toggle dark mode">
                            <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                            </svg>
                            <svg x-show="darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </button>
                        <a href="{{ route('settings.index') }}" class="p-1">
                            <img :src="userProfilePicture || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(userName) + '&size=80&background=6366F1&color=fff'"
                                 class="w-8 h-8 rounded-full object-cover ring-2 ring-indigo-200"
                                 :alt="userName">
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="p-2 text-gray-500 dark:text-gray-400 active:bg-gray-100 dark:active:bg-gray-800 rounded-lg" title="Logout">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-6 pb-24 lg:pb-6">
                @yield('content')
            </main>
        </div>

        <!-- Bottom Navigation (mobile only) -->
        <nav class="lg:hidden fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200 shadow-2xl safe-area-pb">
            <div class="flex items-center justify-around h-16 px-2">

                <!-- Dashboard -->
                <a href="{{ route('dashboard') }}"
                   class="flex flex-col items-center justify-center flex-1 h-full pt-1 gap-0.5 {{ request()->routeIs('dashboard') ? 'text-indigo-600' : 'text-gray-400' }}">
                    <svg class="w-6 h-6" fill="{{ request()->routeIs('dashboard') ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span class="text-xs font-medium">Home</span>
                </a>

                <!-- Transactions -->
                <a href="{{ route('expenses.index') }}"
                   class="flex flex-col items-center justify-center flex-1 h-full pt-1 gap-0.5 {{ request()->routeIs('expenses.*') ? 'text-indigo-600' : 'text-gray-400' }}">
                    <svg class="w-6 h-6" fill="{{ request()->routeIs('expenses.*') ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                    </svg>
                    <span class="text-xs font-medium">Transactions</span>
                </a>

                <!-- FAB: Quick Add -->
                <a href="{{ route('expenses.index') }}?type=expense"
                   class="flex flex-col items-center justify-center flex-1 h-full">
                    <div class="w-14 h-14 -mt-6 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-full flex items-center justify-center shadow-lg shadow-indigo-500/40 active:scale-95 transition-transform">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    </div>
                </a>

                <!-- Budgets -->
                <a href="{{ route('budgets.index') }}"
                   class="flex flex-col items-center justify-center flex-1 h-full pt-1 gap-0.5 {{ request()->routeIs('budgets.*') ? 'text-indigo-600' : 'text-gray-400' }}">
                    <svg class="w-6 h-6" fill="{{ request()->routeIs('budgets.*') ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <span class="text-xs font-medium">Budgets</span>
                </a>

                <!-- Reports -->
                <a href="{{ route('reports.index') }}"
                   class="flex flex-col items-center justify-center flex-1 h-full pt-1 gap-0.5 {{ request()->routeIs('reports.*') ? 'text-indigo-600' : 'text-gray-400' }}">
                    <svg class="w-6 h-6" fill="{{ request()->routeIs('reports.*') ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span class="text-xs font-medium">Reports</span>
                </a>

            </div>
        </nav>

        <!-- Custom Delete Confirmation Modal -->
        <div x-data="confirmModal()"
             x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-4"
             style="display:none">
            <div class="absolute inset-0 bg-black/50" @click="cancel()"></div>
            <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl overflow-hidden"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0">
                <div class="p-6">
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 text-center" x-text="title"></h3>
                    <p class="text-sm text-gray-500 text-center mt-1" x-text="message"></p>
                </div>
                <div class="flex border-t border-gray-100">
                    <button @click="cancel()"
                            class="flex-1 py-4 text-sm font-medium text-gray-600 hover:bg-gray-50 active:bg-gray-100 transition-colors">
                        Cancel
                    </button>
                    <div class="w-px bg-gray-100"></div>
                    <button @click="confirm()"
                            class="flex-1 py-4 text-sm font-semibold text-red-600 hover:bg-red-50 active:bg-red-100 transition-colors">
                        Delete
                    </button>
                </div>
            </div>
        </div>

        <!-- Overlay for mobile -->
        <div x-show="sidebarOpen"
             @click="sidebarOpen = false"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 lg:hidden"
             style="display: none;"></div>
    </div>

    <script>
    function confirmModal() {
        return {
            open: false,
            title: 'Delete item',
            message: 'This action cannot be undone.',
            _resolve: null,
            show(title, message) {
                this.title = title;
                this.message = message;
                this.open = true;
                return new Promise(resolve => this._resolve = resolve);
            },
            confirm() { this.open = false; this._resolve(true); },
            cancel()  { this.open = false; this._resolve(false); },
        };
    }

    // Global helper — call from any component: await confirmDelete('Category name')
    window.confirmDelete = function(itemName) {
        const modal = window._confirmModalInstance;
        if (!modal) return Promise.resolve(window.confirm(`Delete "${itemName}"?`));
        return modal.show(`Delete "${itemName}"?`, 'This action cannot be undone.');
    };

    document.addEventListener('alpine:init', () => {
        // Capture the modal instance once Alpine mounts it
        setTimeout(() => {
            const el = document.querySelector('[x-data="confirmModal()"]');
            if (el && el._x_dataStack) window._confirmModalInstance = el._x_dataStack[0];
        }, 100);
    });

    function layoutData() {
        return {
            sidebarOpen: false,
            isMobile: window.innerWidth < 1024,
            initialized: false,
            darkMode: localStorage.getItem('darkMode') === 'true',
            toggleDark() {
                this.darkMode = !this.darkMode;
                localStorage.setItem('darkMode', this.darkMode);
                document.documentElement.classList.toggle('dark', this.darkMode);
            },
            userName: '{{ addslashes(auth()->user()->name ?? 'User') }}',
            userProfilePicture: '{{ auth()->user()->profile_picture ? (str_starts_with(auth()->user()->profile_picture, 'http') ? auth()->user()->profile_picture : asset('storage/' . auth()->user()->profile_picture)) : '' }}',
            userId: {{ auth()->id() ?? 1 }},
            async init() {
                // Let Alpine render the correct closed state first, then enable transitions
                await this.$nextTick();
                this.initialized = true;
                await this.fetchUserProfile();

                // Listen for profile picture updates
                window.addEventListener('profile-updated', (event) => {
                    this.userProfilePicture = event.detail.profile_picture || '';
                });
            },
            async fetchUserProfile() {
                try {
                    const response = await fetch('/api/v1/settings?user_id=' + this.userId);
                    if (response.ok) {
                        const data = await response.json();
                        this.userName = data.name || 'User';
                        this.userProfilePicture = data.profile_picture || '';
                    }
                } catch (error) {
                    console.error('Error fetching user profile:', error);
                }
            }
        }
    }
    </script>
</body>
</html>
