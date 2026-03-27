<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SmsImportController extends Controller
{
    /**
     * Parse an SMS string and auto-create an expense/income transaction.
     *
     * Supported formats:
     *  - Orange Money (BW): "You have received P150.00 from 77123456 on 26/03/2026..."
     *  - Orange Money send: "P150.00 sent to 77123456. Your new balance is P1,100.00. Ref: OM123"
     *  - MyZaka: "Confirmed. P200.00 received from John Doe..."
     *  - MyZaka payment: "Confirmed. P200.00 sent to ShopName..."
     *  - Airtime top-up: "You have successfully recharged P50.00 airtime..."
     */
    public function parse(Request $request): JsonResponse
    {
        $request->validate([
            'sms'     => 'required|string|max:1000',
            'user_id' => 'required|exists:users,id',
        ]);

        $sms    = $request->input('sms');
        $userId = $request->input('user_id');

        $parsed = $this->parseSms($sms);

        if (!$parsed) {
            return response()->json(['error' => 'Could not parse this SMS format. Please add the transaction manually.'], 422);
        }

        // Find or fallback to a category
        $category = Category::where('name', $parsed['category'])->first()
            ?? Category::where('type', $parsed['type'])->first();

        $expense = Expense::create([
            'user_id'        => $userId,
            'category_id'    => $category?->id,
            'amount'         => $parsed['amount'],
            'type'           => $parsed['type'],
            'description'    => $parsed['description'],
            'date'           => $parsed['date'],
            'payment_method' => $parsed['payment_method'],
            'reference'      => $parsed['reference'],
        ]);

        $expense->load(['category', 'user']);

        return response()->json([
            'message' => 'Transaction imported from SMS.',
            'parsed'  => $parsed,
            'expense' => $expense,
        ], 201);
    }

    /**
     * Parse-only endpoint — returns what was extracted without saving.
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate(['sms' => 'required|string|max:1000']);

        $parsed = $this->parseSms($request->input('sms'));

        if (!$parsed) {
            return response()->json(['error' => 'Could not parse this SMS format.'], 422);
        }

        return response()->json($parsed);
    }

    // -----------------------------------------------------------------------

    private function parseSms(string $sms): ?array
    {
        $sms = trim($sms);

        // --- Orange Money: received ---
        // "You have received P150.00 from 77123456 on 26/03/2026 at 10:30. Your new balance is P1,250.00. Ref: OM123456"
        if (preg_match('/You have received P([\d,]+\.?\d*) from ([\w\s]+) on (\d{2}\/\d{2}\/\d{4})/i', $sms, $m)) {
            return [
                'amount'         => (float) str_replace(',', '', $m[1]),
                'type'           => 'income',
                'category'       => 'Mobile Money',
                'description'    => "Orange Money received from {$m[2]}",
                'date'           => $this->parseDate($m[3]),
                'payment_method' => 'orange_money',
                'reference'      => $this->extractRef($sms),
                'source'         => 'orange_money',
            ];
        }

        // --- Orange Money: sent / payment ---
        // "P150.00 sent to ShopName. Your new balance is P1,100.00. Ref: OM123"
        if (preg_match('/P([\d,]+\.?\d*) sent to ([\w\s]+)\./i', $sms, $m)) {
            return [
                'amount'         => (float) str_replace(',', '', $m[1]),
                'type'           => 'expense',
                'category'       => 'Mobile Money',
                'description'    => "Orange Money payment to {$m[2]}",
                'date'           => now()->toDateString(),
                'payment_method' => 'orange_money',
                'reference'      => $this->extractRef($sms),
                'source'         => 'orange_money',
            ];
        }

        // --- Orange Money: airtime recharge ---
        // "You have successfully recharged P50.00 airtime on 77123456"
        if (preg_match('/recharged P([\d,]+\.?\d*) airtime/i', $sms, $m)) {
            return [
                'amount'         => (float) str_replace(',', '', $m[1]),
                'type'           => 'expense',
                'category'       => 'Airtime',
                'description'    => 'Airtime recharge',
                'date'           => now()->toDateString(),
                'payment_method' => 'orange_money',
                'reference'      => $this->extractRef($sms),
                'source'         => 'orange_money',
            ];
        }

        // --- MyZaka: received ---
        // "Confirmed. P200.00 received from John Doe. 26/03/2026 10:30:00. Transaction ID: MZ789012"
        if (preg_match('/Confirmed\.\s*P([\d,]+\.?\d*) received from ([\w\s]+)\.\s*(\d{2}\/\d{2}\/\d{4})/i', $sms, $m)) {
            return [
                'amount'         => (float) str_replace(',', '', $m[1]),
                'type'           => 'income',
                'category'       => 'Mobile Money',
                'description'    => "MyZaka received from {$m[2]}",
                'date'           => $this->parseDate($m[3]),
                'payment_method' => 'myzaka',
                'reference'      => $this->extractRef($sms),
                'source'         => 'myzaka',
            ];
        }

        // --- MyZaka: sent / paid ---
        // "Confirmed. P200.00 sent to ShopName. 26/03/2026 10:30:00. Transaction ID: MZ789012"
        if (preg_match('/Confirmed\.\s*P([\d,]+\.?\d*) sent to ([\w\s]+)\.\s*(\d{2}\/\d{2}\/\d{4})/i', $sms, $m)) {
            return [
                'amount'         => (float) str_replace(',', '', $m[1]),
                'type'           => 'expense',
                'category'       => 'Mobile Money',
                'description'    => "MyZaka payment to {$m[2]}",
                'date'           => $this->parseDate($m[3]),
                'payment_method' => 'myzaka',
                'reference'      => $this->extractRef($sms),
                'source'         => 'myzaka',
            ];
        }

        // --- MyZaka / BTC: data bundle ---
        // "You have successfully purchased a 1GB data bundle for P25.00"
        if (preg_match('/purchased (?:a )?([\w\s]+data bundle) for P([\d,]+\.?\d*)/i', $sms, $m)) {
            return [
                'amount'         => (float) str_replace(',', '', $m[2]),
                'type'           => 'expense',
                'category'       => 'Data',
                'description'    => "Data bundle: {$m[1]}",
                'date'           => now()->toDateString(),
                'payment_method' => 'mobile_money',
                'reference'      => $this->extractRef($sms),
                'source'         => 'generic',
            ];
        }

        return null;
    }

    private function parseDate(string $dmy): string
    {
        // Convert DD/MM/YYYY → YYYY-MM-DD
        [$d, $m, $y] = explode('/', $dmy);
        return "{$y}-{$m}-{$d}";
    }

    private function extractRef(string $sms): ?string
    {
        if (preg_match('/(?:Ref|Reference|Transaction ID|Trans ID)[:\s#]+([A-Z0-9\-]+)/i', $sms, $m)) {
            return $m[1];
        }
        return null;
    }
}
