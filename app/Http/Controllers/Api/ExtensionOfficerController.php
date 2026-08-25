<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ExtensionOfficerController extends Controller
{
    /**
     * FR-11.1
     * Create an Extension Officer account.
     *
     * Only the System Administrator should be able
     * to create Extension Officer accounts.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => [
                'required',
                'string',
                'max:255',
            ],

            'mobile' => [
                'nullable',
                'string',
                'max:30',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],

            'district' => [
                'required',
                'string',
                'max:255',
            ],

            'province' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $officer = User::create([
            'full_name' => $validated['full_name'],
            'mobile' => $validated['mobile'] ?? null,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'extension_officer',
            'district' => $validated['district'],
            'province' => $validated['province'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Extension Officer account created successfully.',
            'data' => [
                'id' => $officer->id,
                'full_name' => $officer->full_name,
                'email' => $officer->email,
                'role' => $officer->role,
                'district' => $officer->district,
                'province' => $officer->province,
            ],
        ], 201);
    }

        /**
     * FR-11.2
     * Get read-only list of farmers in the
     * Extension Officer's assigned district.
     */
    /**
 * FR-11.2
 * Get read-only list of farmers in the
 * Extension Officer's assigned district.
 */
public function farmers(Request $request)
{
    $officer = $request->user();

    // Only Extension Officers can access this endpoint.
    if ($officer->role !== 'extension_officer') {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Extension Officer access required.',
        ], 403);
    }

    // Officer must have an assigned district.
    if (empty($officer->district)) {
        return response()->json([
            'success' => false,
            'message' => 'No district is assigned to this Extension Officer.',
        ], 422);
    }

    $farmers = User::query()
        ->where('role', 'farmer')
        ->whereRaw('LOWER(district) = ?', [
            strtolower($officer->district)
        ])
        ->select([
            'id',
            'full_name',
            'mobile',
            'email',
            'district',
            'province',
            'farm_name',
            'profile_photo',
            'created_at',
        ])
        ->orderBy('full_name')
        ->get();

    return response()->json([
        'success' => true,
        'message' => 'Farmers retrieved successfully.',
        'data' => [
            'district' => $officer->district,
            'total_farmers' => $farmers->count(),
            'farmers' => $farmers,
        ],
    ]);
}


/**
 * FR-11.4
 * Broadcast an advisory message to all farmers
 * in the Extension Officer's assigned district.
 */
public function broadcast(Request $request)
{
    $officer = $request->user();

    // Only Extension Officers can broadcast advisories.
    if ($officer->role !== 'extension_officer') {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Extension Officer access required.',
        ], 403);
    }

    $validated = $request->validate([
        'title' => [
            'required',
            'string',
            'max:255',
        ],

        'message' => [
            'required',
            'string',
            'max:2000',
        ],
    ]);

    // Find all farmers in the officer's district.
    $farmers = User::query()
        ->where('role', 'farmer')
        ->where('district', $officer->district)
        ->get();

    $notificationCount = 0;

    foreach ($farmers as $farmer) {
        $farmer->notifications()->create([
            'title' => $validated['title'],
            'message' => $validated['message'],
            'type' => 'advisory',
            'is_read' => false,
        ]);

        $notificationCount++;
    }

    return response()->json([
        'success' => true,
        'message' => 'Advisory broadcast sent successfully.',
        'data' => [
            'district' => $officer->district,
            'title' => $validated['title'],
            'message' => $validated['message'],
            'farmers_notified' => $notificationCount,
        ],
    ]);
}
/**
 * FR-11.3
 * Get aggregated read-only dashboard for the
 * Extension Officer's assigned district.
 */
public function dashboard(Request $request)
{
    $officer = $request->user();

    // Only Extension Officers can access this endpoint.
    if ($officer->role !== 'extension_officer') {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Extension Officer access required.',
        ], 403);
    }

    // Officer must have an assigned district.
    if (empty($officer->district)) {
        return response()->json([
            'success' => false,
            'message' => 'No district is assigned to this Extension Officer.',
        ], 422);
    }

    /*
    |--------------------------------------------------------------------------
    | Get farmers in the officer's district
    |--------------------------------------------------------------------------
    */

    $farmers = User::query()
        ->where('role', 'farmer')
        ->whereRaw('LOWER(district) = ?', [
            strtolower($officer->district)
        ])
        ->get();

    $farmerIds = $farmers->pluck('id');

    /*
    |--------------------------------------------------------------------------
    | Get crops belonging to farmers in this district
    |--------------------------------------------------------------------------
    |
    | Farm -> User
    | Farm -> Plot
    | Plot -> Crop
    |
    */

    $crops = \App\Models\Crop::query()
        ->whereHas('plot.farm', function ($query) use ($farmerIds) {
            $query->whereIn('user_id', $farmerIds);
        })
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Calculate crop performance
    |--------------------------------------------------------------------------
    */

    $cropPerformance = $crops->map(function ($crop) {

        // Revenue
        $revenue = \App\Models\Sale::query()
            ->whereHas('harvest', function ($query) use ($crop) {
                $query->where('crop_id', $crop->id);
            })
            ->get()
            ->sum(function ($sale) {
                return (float) $sale->quantity_sold
                    * (float) $sale->price_per_unit;
            });

        // Expenses
        $expenses = \App\Models\Expense::query()
            ->where('crop_id', $crop->id)
            ->where('category', '!=', 'Post-Harvest Loss')
            ->sum('amount');

        // Post-harvest losses
        $postHarvestLoss = \App\Models\PostHarvestLoss::query()
            ->whereHas('harvest', function ($query) use ($crop) {
                $query->where('crop_id', $crop->id);
            })
            ->sum('loss_amount');

        $totalExpenses =
            (float) $expenses +
            (float) $postHarvestLoss;

        $profit =
            (float) $revenue -
            $totalExpenses;

        return [
            'crop_id' => $crop->id,
            'crop_name' => $crop->crop_name,
            'total_revenue' => round($revenue, 2),
            'total_expenses' => round($totalExpenses, 2),
            'profit' => round($profit, 2),
        ];
    })->values();

    /*
    |--------------------------------------------------------------------------
    | Overall calculations
    |--------------------------------------------------------------------------
    */

    $totalCrops = $cropPerformance->count();

    $totalRevenue = $cropPerformance->sum('total_revenue');

    $totalExpenses = $cropPerformance->sum('total_expenses');

    $totalProfit = $cropPerformance->sum('profit');

    $averageProfit = $totalCrops > 0
        ? $totalProfit / $totalCrops
        : 0;

    /*
    |--------------------------------------------------------------------------
    | Top Crops
    |--------------------------------------------------------------------------
    */

    $topCrops = $cropPerformance
        ->sortByDesc('profit')
        ->take(5)
        ->values()
        ->map(function ($crop) {
            return [
                'crop_id' => $crop['crop_id'],
                'crop_name' => $crop['crop_name'],
                'total_profit' => $crop['profit'],
            ];
        });

    /*
    |--------------------------------------------------------------------------
    | Response
    |--------------------------------------------------------------------------
    */

    return response()->json([
        'success' => true,
        'message' => 'District dashboard retrieved successfully.',
        'data' => [
            'district' => $officer->district,

            'summary' => [
                'total_farmers' => $farmers->count(),
                'total_crops' => $totalCrops,
                'total_revenue' => round($totalRevenue, 2),
                'total_expenses' => round($totalExpenses, 2),
                'total_profit' => round($totalProfit, 2),
                'average_profit' => round($averageProfit, 2),
            ],

            'top_crops' => $topCrops,
        ],
    ]);
}
}