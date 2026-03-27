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

    public function getSpendingInsights(Request $request): JsonResponse
    {
        $userId = $request->get('user_id', 1);

        $thisMonth = [now()->startOfMonth(), now()->endOfMonth()];
        $lastMonth = [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()];

        $buildCategoryTotals = fn($start, $end) => Expense::with('category:id,name,color')
            ->where('user_id', $userId)
            ->where('type', 'expense')
            ->whereBetween('date', [$start, $end])
            ->select('category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('category_id')
            ->get()
            ->keyBy('category_id');

        $thisMonthData = $buildCategoryTotals(...$thisMonth);
        $lastMonthData = $buildCategoryTotals(...$lastMonth);

        $allCategoryIds = $thisMonthData->keys()->merge($lastMonthData->keys())->unique();

        $insights = $allCategoryIds->map(function ($categoryId) use ($thisMonthData, $lastMonthData) {
            $thisTotal = (float) ($thisMonthData[$categoryId]->total ?? 0);
            $lastTotal = (float) ($lastMonthData[$categoryId]->total ?? 0);
            $category = ($thisMonthData[$categoryId] ?? $lastMonthData[$categoryId])->category;

            $changePct = $lastTotal > 0
                ? round((($thisTotal - $lastTotal) / $lastTotal) * 100, 1)
                : ($thisTotal > 0 ? 100 : 0);

            return [
                'category_id'   => $categoryId,
                'category_name' => $category?->name ?? 'Unknown',
                'category_color' => $category?->color ?? '#6B7280',
                'this_month'    => $thisTotal,
                'last_month'    => $lastTotal,
                'change_pct'    => $changePct,
                'trend'         => $changePct > 0 ? 'up' : ($changePct < 0 ? 'down' : 'same'),
            ];
        })->sortByDesc('change_pct')->values();

        $thisMonthTotal = $thisMonthData->sum('total');
        $lastMonthTotal = $lastMonthData->sum('total');
        $overallChangePct = $lastMonthTotal > 0
            ? round((($thisMonthTotal - $lastMonthTotal) / $lastMonthTotal) * 100, 1)
            : 0;

        return response()->json([
            'this_month_total'  => $thisMonthTotal,
            'last_month_total'  => $lastMonthTotal,
            'overall_change_pct' => $overallChangePct,
            'overall_trend'     => $overallChangePct > 0 ? 'up' : ($overallChangePct < 0 ? 'down' : 'same'),
            'categories'        => $insights,
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
