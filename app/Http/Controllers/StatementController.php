<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StatementController extends Controller
{
    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $content = file_get_contents($request->file('file')->getRealPath());

        // Normalize line endings
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        // Strip BOM if present
        $content = ltrim($content, "\xEF\xBB\xBF");

        // Detect delimiter
        $firstLine = strtok($content, "\n");
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $lines = array_filter(explode("\n", trim($content)), fn($l) => trim($l) !== '');
        $rows  = array_map(fn($l) => str_getcsv($l, $delimiter), array_values($lines));

        if (count($rows) < 2) {
            return response()->json(['error' => 'File appears empty or has only one row.'], 422);
        }

        $rawHeaders = $rows[0];
        $headers    = array_map(fn($h) => strtolower(trim($h)), $rawHeaders);

        $dateCol   = $this->findCol($headers, ['date', 'transaction date', 'trans date', 'value date', 'posting date']);
        $descCol   = $this->findCol($headers, ['description', 'narration', 'details', 'particulars', 'reference', 'memo', 'trans description', 'transaction description', 'narrative']);
        $amountCol = $this->findCol($headers, ['amount', 'transaction amount', 'net amount']);
        $debitCol  = $this->findCol($headers, ['debit', 'debit amount', 'withdrawals', 'dr', 'debit (bwp)', 'money out']);
        $creditCol = $this->findCol($headers, ['credit', 'credit amount', 'deposits', 'cr', 'credit (bwp)', 'money in']);

        $hasAmount  = $amountCol !== null;
        $hasDebitCredit = $debitCol !== null || $creditCol !== null;

        if ($dateCol === null || $descCol === null || (!$hasAmount && !$hasDebitCredit)) {
            return response()->json([
                'error' => 'Could not detect required columns. Expected: date, description, and amount (or debit/credit). ' .
                           'Detected headers: ' . implode(', ', $rawHeaders),
            ], 422);
        }

        $transactions = [];

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $maxIdx = max(array_filter([$dateCol, $descCol, $amountCol, $debitCol, $creditCol], fn($v) => $v !== null));
            if (count($row) <= $maxIdx) continue;

            $rawDate = trim($row[$dateCol] ?? '');
            $desc    = trim($row[$descCol] ?? '');
            $date    = $this->parseDate($rawDate);

            if (!$date || !$desc || $desc === '') continue;

            $amount = 0;
            $type   = 'expense';

            if ($hasAmount) {
                $val    = $this->parseAmount($row[$amountCol] ?? '0');
                $type   = $val >= 0 ? 'income' : 'expense';
                $amount = abs($val);
            } else {
                $debit  = abs($this->parseAmount($row[$debitCol]  ?? '0'));
                $credit = abs($this->parseAmount($row[$creditCol] ?? '0'));
                if ($credit > 0)      { $amount = $credit; $type = 'income'; }
                elseif ($debit > 0)   { $amount = $debit;  $type = 'expense'; }
                else continue;
            }

            if ($amount <= 0) continue;

            $transactions[] = [
                'date'        => $date,
                'description' => $desc,
                'amount'      => round($amount, 2),
                'type'        => $type,
            ];
        }

        if (empty($transactions)) {
            return response()->json(['error' => 'No valid transactions found in the file.'], 422);
        }

        return response()->json([
            'transactions' => $transactions,
            'count'        => count($transactions),
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function findCol(array $headers, array $candidates): ?int
    {
        foreach ($candidates as $name) {
            $idx = array_search($name, $headers, true);
            if ($idx !== false) return (int) $idx;
        }
        // Partial match fallback
        foreach ($headers as $i => $h) {
            foreach ($candidates as $name) {
                if (str_contains($h, $name)) return $i;
            }
        }
        return null;
    }

    private function parseDate(string $raw): ?string
    {
        $raw = trim($raw);
        if (!$raw) return null;

        $formats = [
            'd/m/Y', 'm/d/Y', 'Y-m-d', 'd-m-Y', 'm-d-Y',
            'd M Y', 'd M y', 'j M Y', 'j M y',
            'Y/m/d', 'd.m.Y', 'M d, Y', 'd/m/y', 'm/d/y',
        ];

        foreach ($formats as $fmt) {
            $dt = \DateTime::createFromFormat($fmt, $raw);
            if ($dt && $dt->format(str_replace(['d','m','Y','y','j','M'], ['%', '%', '%', '%', '%', '%'], $fmt)) !== false) {
                return $dt->format('Y-m-d');
            }
        }

        // Last resort: strtotime
        $ts = strtotime($raw);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    private function parseAmount(string $raw): float
    {
        // Remove currency symbols, spaces, quotes
        $clean = preg_replace('/[^0-9.\-,]/', '', trim($raw));

        // Handle European format (1.234,56 → 1234.56)
        if (preg_match('/,\d{2}$/', $clean) && str_contains($clean, '.')) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } else {
            $clean = str_replace(',', '', $clean);
        }

        return (float) $clean;
    }
}
