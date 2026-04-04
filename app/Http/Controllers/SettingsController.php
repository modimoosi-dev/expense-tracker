<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function getSettings(Request $request): JsonResponse
    {
        $userId = $request->get('user_id', 1);
        $user = User::findOrFail($userId);

        return response()->json([
            'currency' => $user->currency ?? 'BWP',
            'name' => $user->name,
            'email' => $user->email,
            'profile_picture' => $this->profilePictureUrl($user->profile_picture),
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'currency' => 'required|string|size:3',
            'name' => 'sometimes|string|max:255',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $user->update([
            'currency' => $validated['currency'],
            'name' => $validated['name'] ?? $user->name,
        ]);

        return response()->json([
            'message' => 'Settings updated successfully',
            'currency' => $user->currency,
            'profile_picture' => $this->profilePictureUrl($user->profile_picture),
        ]);
    }

    public function uploadProfilePicture(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = User::findOrFail($validated['user_id']);

        // Delete old profile picture if exists
        if ($user->profile_picture) {
            Storage::disk('public')->delete($user->profile_picture);
        }

        // Store new profile picture
        $path = $request->file('profile_picture')->store('profile-pictures', 'public');

        $user->update([
            'profile_picture' => $path,
        ]);

        return response()->json([
            'message' => 'Profile picture updated successfully',
            'profile_picture' => $this->profilePictureUrl($path),
        ]);
    }

    private function profilePictureUrl(?string $picture): ?string
    {
        if (!$picture) return null;
        return str_starts_with($picture, 'http') ? $picture : asset('storage/' . $picture);
    }

    public function deleteProfilePicture(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($validated['user_id']);

        if ($user->profile_picture) {
            Storage::disk('public')->delete($user->profile_picture);
            $user->update(['profile_picture' => null]);
        }

        return response()->json([
            'message' => 'Profile picture removed successfully',
        ]);
    }

    /**
     * Return approximate exchange rates relative to BWP.
     * Rates are fetched live from exchangerate-api.com (free tier, no key needed for open endpoint).
     * Falls back to static rates when the service is unavailable.
     */
    public function getExchangeRates(Request $request): JsonResponse
    {
        $base = strtoupper($request->get('base', 'BWP'));

        $cacheKey = "exchange_rates_{$base}";

        $rates = cache()->remember($cacheKey, now()->addHours(6), function () use ($base) {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(5)
                    ->get("https://open.er-api.com/v6/latest/{$base}");

                if ($response->successful()) {
                    return $response->json('rates');
                }
            } catch (\Throwable) {
                // fall through to static fallback
            }

            // Static fallback rates (approximate, BWP-based)
            $bwpRates = [
                'BWP' => 1.0,
                'USD' => 0.073,
                'ZAR' => 1.37,
                'EUR' => 0.067,
                'GBP' => 0.058,
                'ZMW' => 1.87,
                'NAD' => 1.37,
                'ZWL' => 93.5,
                'KES' => 9.45,
                'GHS' => 1.10,
                'NGN' => 110.0,
                'TZS' => 188.0,
                'UGX' => 274.0,
                'MWK' => 126.0,
            ];

            if ($base === 'BWP') {
                return $bwpRates;
            }

            // Convert to requested base
            $bwpPerBase = 1 / ($bwpRates[$base] ?? 1);
            return collect($bwpRates)->mapWithKeys(
                fn($rate, $code) => [$code => round($rate * $bwpPerBase, 6)]
            )->all();
        });

        return response()->json([
            'base'      => $base,
            'rates'     => $rates,
            'source'    => 'open.er-api.com (cached 6h)',
            'cached_at' => now()->toIso8601String(),
        ]);
    }

    public function getSupportedCurrencies(): JsonResponse
    {
        return response()->json([
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£'],
            ['code' => 'BWP', 'name' => 'Botswana Pula', 'symbol' => 'P'],
            ['code' => 'ZAR', 'name' => 'South African Rand', 'symbol' => 'R'],
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥'],
            ['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => '¥'],
            ['code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => '₹'],
            ['code' => 'AUD', 'name' => 'Australian Dollar', 'symbol' => 'A$'],
            ['code' => 'CAD', 'name' => 'Canadian Dollar', 'symbol' => 'C$'],
            ['code' => 'CHF', 'name' => 'Swiss Franc', 'symbol' => 'CHF'],
            ['code' => 'SEK', 'name' => 'Swedish Krona', 'symbol' => 'kr'],
            ['code' => 'NZD', 'name' => 'New Zealand Dollar', 'symbol' => 'NZ$'],
            ['code' => 'BRL', 'name' => 'Brazilian Real', 'symbol' => 'R$'],
            ['code' => 'MXN', 'name' => 'Mexican Peso', 'symbol' => '$'],
            ['code' => 'SGD', 'name' => 'Singapore Dollar', 'symbol' => 'S$'],
            ['code' => 'HKD', 'name' => 'Hong Kong Dollar', 'symbol' => 'HK$'],
            ['code' => 'NOK', 'name' => 'Norwegian Krone', 'symbol' => 'kr'],
            ['code' => 'KRW', 'name' => 'South Korean Won', 'symbol' => '₩'],
            ['code' => 'TRY', 'name' => 'Turkish Lira', 'symbol' => '₺'],
            ['code' => 'RUB', 'name' => 'Russian Ruble', 'symbol' => '₽'],
        ]);
    }
}
