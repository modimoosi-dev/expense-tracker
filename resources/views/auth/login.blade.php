<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#4f46e5">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Login - Expense Tracker</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Ensure full height works in Capacitor WebView */
        html, body {
            height: 100%;
            overflow-x: hidden;
        }
        /* Prevent input zoom on iOS/Android */
        input, select, textarea {
            font-size: 16px !important;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500 min-h-screen flex flex-col">

    <!-- Top decorative area -->
    <div class="flex-1 flex flex-col items-center justify-center px-6 pt-12 pb-6">

        <!-- Logo -->
        <div class="flex flex-col items-center mb-8">
            <div class="w-20 h-20 bg-white rounded-3xl shadow-2xl flex items-center justify-center mb-4">
                <svg class="w-12 h-12 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-white tracking-tight">Expense Tracker</h1>
            <p class="text-indigo-200 text-sm mt-1">Track your money, your way</p>
        </div>

        <!-- Card -->
        <div class="w-full max-w-sm bg-white rounded-3xl shadow-2xl overflow-hidden">
            <div class="px-6 pt-6 pb-2">
                <h2 class="text-xl font-bold text-gray-800">Welcome back</h2>
                <p class="text-gray-500 text-sm mt-1">Sign in to your account</p>
            </div>

            <form method="POST" action="{{ route('login') }}" class="px-6 pb-6 pt-4 space-y-4">
                @csrf

                @if ($errors->any())
                    <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3">
                        <p class="text-red-600 text-sm">{{ $errors->first() }}</p>
                    </div>
                @endif

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                               autocomplete="email" inputmode="email"
                               class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('email') border-red-400 bg-red-50 @enderror"
                               placeholder="you@example.com">
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <input type="password" name="password" id="password" required
                               autocomplete="current-password"
                               class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('password') border-red-400 bg-red-50 @enderror"
                               placeholder="••••••••">
                    </div>
                </div>

                <!-- Remember -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded text-indigo-600 border-gray-300">
                        <span class="text-sm text-gray-600">Remember me</span>
                    </label>
                </div>

                <!-- Submit -->
                <button type="submit"
                        class="w-full py-3.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-xl shadow-lg shadow-indigo-500/30 active:scale-95 transition-transform">
                    Sign In
                </button>

                <!-- Divider -->
                <div class="flex items-center gap-3">
                    <div class="flex-1 h-px bg-gray-200"></div>
                    <span class="text-xs text-gray-400">don't have an account?</span>
                    <div class="flex-1 h-px bg-gray-200"></div>
                </div>

                <!-- Register -->
                <a href="{{ route('register') }}"
                   class="w-full flex justify-center py-3.5 border-2 border-indigo-500 text-indigo-600 font-semibold rounded-xl active:scale-95 transition-transform">
                    Create Account
                </a>
            </form>
        </div>

        <p class="mt-6 text-indigo-200 text-xs">&copy; {{ date('Y') }} Expense Tracker</p>
    </div>

</body>
</html>
