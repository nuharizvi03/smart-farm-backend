<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminExpenseCategoryController extends Controller
{
    private function authorizeAdmin(Request $request): ?JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        return null;
    }

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizeAdmin($request)) {
            return $response;
        }

        $categories = ExpenseCategory::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'message' => 'Expense categories retrieved successfully.',
            'data' => $categories,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if ($response = $this->authorizeAdmin($request)) {
            return $response;
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:expense_categories,name',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $category = ExpenseCategory::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Expense category created successfully.',
            'data' => $category,
        ], 201);
    }

    public function update(
        Request $request,
        ExpenseCategory $expenseCategory
    ): JsonResponse {
        if ($response = $this->authorizeAdmin($request)) {
            return $response;
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('expense_categories', 'name')
                    ->ignore($expenseCategory->id),
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $expenseCategory->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Expense category updated successfully.',
            'data' => $expenseCategory->fresh(),
        ]);
    }

    public function activate(
        Request $request,
        ExpenseCategory $expenseCategory
    ): JsonResponse {
        if ($response = $this->authorizeAdmin($request)) {
            return $response;
        }

        $expenseCategory->update([
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Expense category activated successfully.',
            'data' => $expenseCategory->fresh(),
        ]);
    }

    public function deactivate(
        Request $request,
        ExpenseCategory $expenseCategory
    ): JsonResponse {
        if ($response = $this->authorizeAdmin($request)) {
            return $response;
        }

        $expenseCategory->update([
            'is_active' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Expense category deactivated successfully.',
            'data' => $expenseCategory->fresh(),
        ]);
    }
}