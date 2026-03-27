<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Budget;
use App\Models\Expense;
use App\Notifications\BudgetAlertNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Expense::with(['category', 'user']);

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->inDateRange($request->start_date, $request->end_date);
        }

        if ($request->has('user_id')) {
            $query->forUser($request->user_id);
        }

        $expenses = $query->orderBy('date', 'desc')->paginate(15);

        return response()->json($expenses);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $expense = Expense::create($request->validated());
        $expense->load(['category', 'user']);

        if ($expense->type === 'expense') {
            $this->checkBudgetAlerts($expense);
        }

        return response()->json($expense, 201);
    }

    private function checkBudgetAlerts(Expense $expense): void
    {
        $budgets = Budget::with(['category', 'user'])
            ->where('user_id', $expense->user_id)
            ->where('is_active', true)
            ->where('start_date', '<=', $expense->date)
            ->where('end_date', '>=', $expense->date)
            ->where(function ($q) use ($expense) {
                $q->whereNull('category_id')
                  ->orWhere('category_id', $expense->category_id);
            })
            ->get();

        foreach ($budgets as $budget) {
            $pct = $budget->getPercentageUsed();
            $user = $budget->user;
            $alreadyNotified = $user->notifications()
                ->where('type', BudgetAlertNotification::class)
                ->whereJsonContains('data->budget_id', $budget->id)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year);

            if ($pct >= 100 && !$alreadyNotified->clone()->whereJsonContains('data->threshold', '100')->exists()) {
                $user->notify(new BudgetAlertNotification($budget, $pct, '100'));
            } elseif ($pct >= 80 && !$alreadyNotified->clone()->whereJsonContains('data->threshold', '80')->exists()) {
                $user->notify(new BudgetAlertNotification($budget, $pct, '80'));
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Expense $expense): JsonResponse
    {
        $expense->load(['category', 'user']);
        return response()->json($expense);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateExpenseRequest $request, Expense $expense): JsonResponse
    {
        $expense->update($request->validated());
        $expense->load(['category', 'user']);

        return response()->json($expense);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expense $expense): JsonResponse
    {
        $expense->delete();

        return response()->json(['message' => 'Expense deleted successfully'], 200);
    }

    /**
     * Get expense statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        $query = Expense::query();

        if ($request->has('user_id')) {
            $query->forUser($request->user_id);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->inDateRange($request->start_date, $request->end_date);
        }

        $totalIncome = (clone $query)->income()->sum('amount');
        $totalExpense = (clone $query)->expense()->sum('amount');
        $balance = $totalIncome - $totalExpense;

        $expensesByCategory = (clone $query)
            ->expense()
            ->selectRaw('category_id, sum(amount) as total')
            ->groupBy('category_id')
            ->with('category:id,name,color')
            ->get();

        $incomeByCategory = (clone $query)
            ->income()
            ->selectRaw('category_id, sum(amount) as total')
            ->groupBy('category_id')
            ->with('category:id,name,color')
            ->get();

        return response()->json([
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'balance' => $balance,
            'expenses_by_category' => $expensesByCategory,
            'income_by_category' => $incomeByCategory,
        ]);
    }
}
