<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Models\Crop;
use App\Models\Expense;
use App\Models\Farm;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    /**
     * Display all expenses for a farm.
     */
    public function index(Request $request, Farm $farm)
    {
        // Ensure the farm belongs to the authenticated user
        if ($farm->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this farm.'
            ], 403);
        }

        $expenses = $farm->expenses()
            ->with('crop')
            ->latest('expense_date')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Expenses retrieved successfully.',
            'data' => $expenses,
        ]);
    }

    /**
     * Store a new expense for a farm.
     */
    public function store(
        StoreExpenseRequest $request,
        Farm $farm
    ) {
        // Ensure the farm belongs to the authenticated user
        if ($farm->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this farm.'
            ], 403);
        }

        $data = $request->validated();

        /*
         * If crop_id is provided, make sure that crop
         * belongs to a plot inside this farm.
         */
        if (!empty($data['crop_id'])) {

            $crop = Crop::with('plot')
                ->find($data['crop_id']);

            if (!$crop || $crop->plot->farm_id !== $farm->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected crop does not belong to this farm.'
                ], 422);
            }
        }

        $expense = $farm->expenses()->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Expense created successfully.',
            'data' => $expense->load('crop'),
        ], 201);
    }

    /**
     * Display a specific expense.
     */
    public function show(
        Request $request,
        Farm $farm,
        Expense $expense
    ) {
        if ($farm->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this farm.'
            ], 403);
        }

        if ($expense->farm_id !== $farm->id) {
            return response()->json([
                'success' => false,
                'message' => 'Expense does not belong to this farm.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $expense->load('crop'),
        ]);
    }

    /**
     * Update an expense.
     */
    public function update(
        StoreExpenseRequest $request,
        Farm $farm,
        Expense $expense
    ) {
        if ($farm->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this farm.'
            ], 403);
        }

        if ($expense->farm_id !== $farm->id) {
            return response()->json([
                'success' => false,
                'message' => 'Expense does not belong to this farm.'
            ], 404);
        }

        $data = $request->validated();

        // Validate crop ownership if crop_id is changed
        if (!empty($data['crop_id'])) {

            $crop = Crop::with('plot')
                ->find($data['crop_id']);

            if (!$crop || $crop->plot->farm_id !== $farm->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected crop does not belong to this farm.'
                ], 422);
            }
        }

        $expense->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Expense updated successfully.',
            'data' => $expense->fresh()->load('crop'),
        ]);
    }

    /**
     * Delete an expense.
     */
    public function destroy(
        Request $request,
        Farm $farm,
        Expense $expense
    ) {
        if ($farm->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this farm.'
            ], 403);
        }

        if ($expense->farm_id !== $farm->id) {
            return response()->json([
                'success' => false,
                'message' => 'Expense does not belong to this farm.'
            ], 404);
        }

        $expense->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expense deleted successfully.',
        ]);
    }
}