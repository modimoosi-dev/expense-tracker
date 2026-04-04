<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StatementController extends Controller
{
    public function preview(Request $request)
    {
        $request->validate([
            'file'     => 'required|file|mimes:csv,txt,pdf|max:10240',
            'password' => 'nullable|string|max:255',
        ]);

        $file = $request->file('file');
        $mime = $file->getMimeType();

        if ($mime === 'application/pdf' || strtolower($file->getClientOriginalExtension()) === 'pdf') {
            return $this->previewPdf($file, $request->input('password'));
        }

        return $this->previewCsv($file);
    }

    private function previewPdf($file, ?string $password = null)
    {
        try {
            $config = new \Smalot\PdfParser\Config();
            $parser = new \Smalot\PdfParser\Parser([], $config);
            $pdf    = $parser->parseFile($file->getRealPath(), $password ?? '');
            $text   = $pdf->getText();
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            // Detect encryption errors from smalot/pdfparser
            if (stripos($msg, 'secured') !== false || stripos($msg, 'encrypt') !== false || stripos($msg, 'password') !== false) {
                return response()->json(['error' => 'password_required'], 422);
            }
            return response()->json(['error' => 'Could not read PDF: ' . $msg], 422);
        }

        $transactions = $this->parsePdfText($text);

        if (empty($transactions)) {
            return response()->json([
                'error' => 'No transactions found in PDF. Make sure it is a bank statement with transaction history.',
            ], 422);
        }

        return response()->json([
            'transactions' => $transactions,
            'count'        => count($transactions),
        ]);
    }

    private function parsePdfText(string $text): array
    {
        $lines        = explode("\n", $text);
        $transactions = [];

        // Date patterns: 01/03/2026 | 01-03-2026 | 01 Mar 2026 | 1 Mar 26
        $datePattern = '/^(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4}|\d{1,2}\s+[A-Za-z]{3}\s+\d{2,4})/';

        // Amount pattern: 1,234.56 or 1234.56
        $amountPattern = '/([\d,]+\.\d{2})/';

        foreach ($lines as $line) {
            $line = trim($line);
            if (!preg_match($datePattern, $line, $dateMatch)) continue;

            $rawDate = $dateMatch[1];
            $date    = $this->parseDate($rawDate);
            if (!$date) continue;

            // Find all amounts in the line
            preg_match_all($amountPattern, $line, $amountMatches);
            $amounts = array_map(fn($a) => $this->parseAmount($a), $amountMatches[1]);
            $amounts = array_filter($amounts, fn($a) => $a > 0);
            $amounts = array_values($amounts);

            if (empty($amounts)) continue;

            // Strip the date and any trailing amounts from description
            $desc = preg_replace($datePattern, '', $line);
            $desc = preg_replace($amountPattern, '', $desc);
            $desc = preg_replace('/\s{2,}/', ' ', trim($desc));
            $desc = trim($desc, " \t-,");

            if (strlen($desc) < 2) continue;

            // Heuristic: if there are 2+ amounts, the first non-balance is the transaction
            // Last amount is usually the running balance — ignore it if 3+ amounts
            $txAmount = count($amounts) >= 3 ? $amounts[1] : $amounts[0];

            // Detect credit/income keywords in description
            $lower = strtolower($desc);
            $type  = 'expense';
            if (preg_match('/\b(credit|salary|payment received|transfer in|deposit|refund|reversal|cr)\b/', $lower)) {
                $type = 'income';
            }
            // Also check if line contains "CR" suffix on amounts
            if (preg_match('/[\d,]+\.\d{2}\s*CR\b/i', $line)) {
                $type = 'income';
            }

            if ($txAmount <= 0) continue;

            $transactions[] = [
                'date'        => $date,
                'description' => $desc,
                'amount'      => round($txAmount, 2),
                'type'        => $type,
            ];
        }

        return $transactions;
    }

    private function previewCsv($file)
    {
        $content = file_get_contents($file->getRealPath());

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
