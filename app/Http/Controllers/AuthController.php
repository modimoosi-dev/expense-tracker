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

        return redirect()->route('dashboard');
    }

    public function pollAuthStatus(Request $request)
    {
        $key = $request->query('key', '');
        if (!$key) {
            return response()->json(['ready' => false]);
        }

        $token = Cache::get('oauth_ready_' . $key);
        if (!$token) {
            return response()->json(['ready' => false]);
        }

        return response()->json(['ready' => true, 'token' => $token]);
    }

    public function googleNativeCallback(Request $request)
    {
        $idToken = $request->input('id_token');

        try {
            $verifiedToken = app('firebase.auth')->verifyIdToken($idToken);
            $uid   = $verifiedToken->claims()->get('sub');
            $email = $verifiedToken->claims()->get('email');
            $name  = $verifiedToken->claims()->get('name') ?? explode('@', $email)[0];
            $photo = $verifiedToken->claims()->get('picture');
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Invalid token.'], 401);
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make(Str::random(24)), 'currency' => 'BWP']
        );

        if ($photo) $user->update(['profile_picture' => $photo]);

        Auth::login($user, true);
        $request->session()->regenerate();

        return response()->json(['success' => true]);
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

    public function googleGisCallback(Request $request)
    {
        try {
        $credential = $request->input('credential');
        if (!$credential) {
            return response()->json(['message' => 'No credential provided.'], 422);
        }

        // Verify the Google ID token via Google's tokeninfo endpoint
        $response = \Illuminate\Support\Facades\Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $credential,
        ]);

        if (!$response->successful()) {
            return response()->json(['message' => 'Invalid token.'], 401);
        }

        $payload = $response->json();
        $projectNumber = '394540692942';

        // Ensure the token belongs to our Google project
        $aud = $payload['aud'] ?? '';
        if (!str_starts_with($aud, $projectNumber . '-')) {
            return response()->json(['message' => 'Token not issued for this project.'], 401);
        }

        $email = $payload['email'] ?? null;
        if (!$email) {
            return response()->json(['message' => 'No email in token.'], 401);
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'     => $payload['name'] ?? explode('@', $email)[0],
                'password' => Hash::make(Str::random(24)),
                'currency' => 'BWP',
            ]
        );

        if (!empty($payload['picture'])) {
            $user->update(['profile_picture' => $payload['picture']]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
