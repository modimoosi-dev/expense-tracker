<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BudgetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Budget::with(['category', 'user']);

        if ($request->has('user_id')) {
            $query->forUser($request->user_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('current')) {
            $query->current();
        }

        $budgets = $query->orderBy('created_at', 'desc')->get();

        // Add calculated fields
        $budgets->each(function ($budget) {
            $budget->spent_amount = $budget->getSpentAmount();
            $budget->remaining_amount = $budget->getRemainingAmount();
            $budget->percentage_used = $budget->getPercentageUsed();
        });

        return response()->json($budgets);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'period' => 'required|in:monthly,quarterly,yearly',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'boolean',
        ]);

        $budget = Budget::create($validated);
        $budget->load(['category', 'user']);
        $budget->spent_amount = $budget->getSpentAmount();
        $budget->remaining_amount = $budget->getRemainingAmount();
        $budget->percentage_used = $budget->getPercentageUsed();

        return response()->json($budget, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Budget $budget): JsonResponse
    {
        $budget->load(['category', 'user']);
        $budget->spent_amount = $budget->getSpentAmount();
        $budget->remaining_amount = $budget->getRemainingAmount();
        $budget->percentage_used = $budget->getPercentageUsed();

        return response()->json($budget);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Budget $budget): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'sometimes|required|string|max:255',
            'amount' => 'sometimes|required|numeric|min:0.01',
            'period' => 'sometimes|required|in:monthly,quarterly,yearly',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after:start_date',
            'is_active' => 'boolean',
        ]);

        $budget->update($validated);
        $budget->load(['category', 'user']);
        $budget->spent_amount = $budget->getSpentAmount();
        $budget->remaining_amount = $budget->getRemainingAmount();
        $budget->percentage_used = $budget->getPercentageUsed();

        return response()->json($budget);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Budget $budget): JsonResponse
    {
        $budget->delete();

        return response()->json(['message' => 'Budget deleted successfully'], 200);
    }
}
