<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function getMonthlyTrends(Request $request): JsonResponse
    {
        $userId = $request->get('user_id', 1);
        $months = $request->get('months', 12);

        $trends = Expense::where('user_id', $userId)
            ->where('date', '>=', now()->subMonths($months))
            ->select(
                DB::raw('DATE_FORMAT(date, "%Y-%m") as month'),
                DB::raw('SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as total_income'),
                DB::raw('SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as total_expense')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json($trends);
    }

    public function getCategoryBreakdown(Request $request): JsonResponse
    {
        $userId = $request->get('user_id', 1);
        $type = $request->get('type', 'expense');
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());

        $breakdown = Expense::with('category')
            ->where('user_id', $userId)
            ->where('type', $type)
            ->whereBetween('date', [$startDate, $endDate])
            ->select('category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->get();

        return response()->json($breakdown);
    }

    public function getTopExpenses(Request $request): JsonResponse
    {
        $userId = $request->get('user_id', 1);
        $limit = $request->get('limit', 10);
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());

        $topExpenses = Expense::with('category')
            ->where('user_id', $userId)
            ->where('type', 'expense')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderByDesc('amount')
            ->limit($limit)
            ->get();

        return response()->json($topExpenses);
    }

    public function getYearlyComparison(Request $request): JsonResponse
    {
        $userId = $request->get('user_id', 1);
        $currentYear = now()->year;
        $previousYear = $currentYear - 1;

        $current = Expense::where('user_id', $userId)
            ->whereYear('date', $currentYear)
            ->select(
                DB::raw('SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as total_income'),
                DB::raw('SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as total_expense')
            )
            ->first();

        $previous = Expense::where('user_id', $userId)
            ->whereYear('date', $previousYear)
            ->select(
                DB::raw('SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as total_income'),
                DB::raw('SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as total_expense')
            )
            ->first();

        return response()->json([
            'current_year' => [
                'year' => $currentYear,
                'income' => $current->total_income ?? 0,
                'expense' => $current->total_expense ?? 0,
                'balance' => ($current->total_income ?? 0) - ($current->total_expense ?? 0),
            ],
            'previous_year' => [
                'year' => $previousYear,
                'income' => $previous->total_income ?? 0,
                'expense' => $previous->total_expense ?? 0,
                'balance' => ($previous->total_income ?? 0) - ($previous->total_expense ?? 0),
            ],
        ]);
    }

    public function exportToCsv(Request $request)
    {
        $userId = $request->get('user_id', 1);
        $startDate = $request->get('start_date', now()->startOfYear());
        $endDate = $request->get('end_date', now());

        $expenses = Expense::with(['category', 'user'])
            ->where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();

        $filename = 'expenses_' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($expenses) {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['Date', 'Type', 'Category', 'Amount', 'Description', 'Payment Method', 'Reference']);

            foreach ($expenses as $expense) {
                fputcsv($file, [
                    $expense->date->format('Y-m-d'),
                    ucfirst($expense->type),
                    $expense->category->name ?? 'N/A',
                    $expense->amount,
                    $expense->description ?? '',
                    $expense->payment_method ?? '',
                    $expense->reference ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
