<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.register');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            if ($request->expectsJson()) {
                return response()->json(['redirect' => route('dashboard')]);
            }
            return redirect()->intended(route('dashboard'));
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Invalid email or password.'], 422);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'currency' => 'BWP',
        ]);

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    public function googleRedirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function googleCallback()
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        $user = User::firstOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name'     => $googleUser->getName(),
                'password' => Hash::make(Str::random(24)),
                'currency' => 'BWP',
            ]
        );

        // Update avatar if changed
        if ($googleUser->getAvatar()) {
            $user->update(['profile_picture' => $googleUser->getAvatar()]);
        }

        Auth::login($user, true);

        // If request wants a deep link (mobile app), return one-time token
        $redirectTo = request()->query('redirect_to');
        if ($redirectTo === 'app') {
            $token = Str::random(40);
            Cache::put('oauth_token_' . $token, $user->id, now()->addMinutes(2));
            return redirect('com.expensetracker.bw://auth?token=' . $token);
        }

        return redirect()->route('dashboard');
    }

    public function verifyOAuthToken(Request $request)
    {
        $token = $request->input('token');
        $userId = Cache::pull('oauth_token_' . $token);

        if (!$userId) {
            return response()->json(['message' => 'Invalid or expired token.'], 401);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return response()->json(['success' => true]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
