<?php

namespace App\Http\Controllers;

use App\Models\RecurringExpense;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RecurringExpenseController extends Controller
{
    /**
     * Display a listing of recurring expenses.
     */
    public function index(Request $request): JsonResponse
    {
        $query = RecurringExpense::with('category')
            ->where('user_id', $request->user_id ?? 1)
            ->orderBy('created_at', 'desc');

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('frequency')) {
            $query->where('frequency', $request->frequency);
        }

        $recurringExpenses = $query->paginate(15);

        return response()->json($recurringExpenses);
    }

    /**
     * Store a newly created recurring expense.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:income,expense',
            'description' => 'nullable|string|max:500',
            'payment_method' => 'nullable|string|max:100',
            'frequency' => 'required|in:daily,weekly,monthly,yearly',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'day_of_week' => 'nullable|integer|min:0|max:6',
            'is_active' => 'boolean'
        ]);

        $recurringExpense = RecurringExpense::create($validated);

        return response()->json($recurringExpense->load('category'), 201);
    }

    /**
     * Display the specified recurring expense.
     */
    public function show(RecurringExpense $recurringExpense): JsonResponse
    {
        return response()->json($recurringExpense->load('category'));
    }

    /**
     * Update the specified recurring expense.
     */
    public function update(Request $request, RecurringExpense $recurringExpense): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'amount' => 'sometimes|numeric|min:0.01',
            'type' => 'sometimes|in:income,expense',
            'description' => 'nullable|string|max:500',
            'payment_method' => 'nullable|string|max:100',
            'frequency' => 'sometimes|in:daily,weekly,monthly,yearly',
            'start_date' => 'sometimes|date',
            'end_date' => 'nullable|date|after:start_date',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'day_of_week' => 'nullable|integer|min:0|max:6',
            'is_active' => 'boolean'
        ]);

        $recurringExpense->update($validated);

        return response()->json($recurringExpense->load('category'));
    }

    /**
     * Remove the specified recurring expense.
     */
    public function destroy(RecurringExpense $recurringExpense): JsonResponse
    {
        $recurringExpense->delete();

        return response()->json(['message' => 'Recurring expense deleted successfully']);
    }

    /**
     * Toggle active status of recurring expense.
     */
    public function toggleStatus(RecurringExpense $recurringExpense): JsonResponse
    {
        $recurringExpense->update([
            'is_active' => !$recurringExpense->is_active
        ]);

        return response()->json($recurringExpense);
    }

    /**
     * Manually generate an expense from a recurring expense.
     */
    public function generateNow(RecurringExpense $recurringExpense): JsonResponse
    {
        if (!$recurringExpense->is_active) {
            return response()->json([
                'message' => 'Cannot generate expense from inactive recurring expense'
            ], 400);
        }

        $expense = $recurringExpense->generateExpense();

        return response()->json([
            'message' => 'Expense generated successfully',
            'expense' => $expense
        ]);
    }
}
