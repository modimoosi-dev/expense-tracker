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

            <form id="login-form" class="px-6 pb-6 pt-4 space-y-4" onsubmit="handleLogin(event)">
                @csrf

                <div id="login-error" class="hidden bg-red-50 border border-red-200 rounded-xl px-4 py-3">
                    <p class="text-red-600 text-sm" id="login-error-msg"></p>
                </div>

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
                <button type="submit" id="login-btn"
                        class="w-full py-3.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-xl shadow-lg shadow-indigo-500/30 active:scale-95 transition-transform">
                    Sign In
                </button>

                <!-- Divider -->
                <div class="flex items-center gap-3">
                    <div class="flex-1 h-px bg-gray-200"></div>
                    <span class="text-xs text-gray-400 whitespace-nowrap">or continue with</span>
                    <div class="flex-1 h-px bg-gray-200"></div>
                </div>

                <!-- Google Sign-In (web only — opens external browser on mobile) -->
                <div id="google-btn-wrapper">
                    <a href="{{ route('auth.google') }}"
                       class="w-full flex items-center justify-center gap-3 py-3.5 bg-white border border-gray-300 rounded-xl shadow-sm hover:shadow-md active:scale-95 transition-all font-medium text-gray-700">
                        <svg class="w-5 h-5" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        Continue with Google
                    </a>
                </div>
                <script>
                    if (window.Capacitor) document.getElementById('google-btn-wrapper').style.display = 'none';
                </script>

                <!-- Register link -->
                <p class="text-center text-sm text-gray-500">
                    Don't have an account?
                    <a href="{{ route('register') }}" class="text-indigo-600 font-semibold">Create one</a>
                </p>
            </form>
        </div>

        <p class="mt-6 text-indigo-200 text-xs">&copy; {{ date('Y') }} Expense Tracker</p>
    </div>

    <script>
    async function handleLogin(e) {
        e.preventDefault();
        const btn = document.getElementById('login-btn');
        const errBox = document.getElementById('login-error');
        const errMsg = document.getElementById('login-error-msg');
        const form = document.getElementById('login-form');

        btn.disabled = true;
        btn.textContent = 'Signing in…';
        errBox.classList.add('hidden');

        try {
            const res = await fetch('{{ route('login') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    email: form.email.value,
                    password: form.password.value,
                    remember: form.remember?.checked || false,
                }),
            });

            if (res.ok || res.redirected) {
                window.location.href = '/dashboard';
            } else {
                const data = await res.json().catch(() => ({}));
                errMsg.textContent = data.message || 'Invalid email or password.';
                errBox.classList.remove('hidden');
                btn.disabled = false;
                btn.textContent = 'Sign In';
            }
        } catch (err) {
            errMsg.textContent = 'Connection error. Please try again.';
            errBox.classList.remove('hidden');
            btn.disabled = false;
            btn.textContent = 'Sign In';
        }
    }
    </script>
</body>
</html>
