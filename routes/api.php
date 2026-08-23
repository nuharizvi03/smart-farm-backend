<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\FarmController;
use App\Http\Controllers\Api\PlotController;
use App\Http\Controllers\Api\CropController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\InputApplicationController;
use App\Http\Controllers\Api\AgrochemicalProductController;
use App\Http\Controllers\Api\HarvestController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\HarvestSummaryController;
use App\Http\Controllers\Api\PostHarvestLossController;
use App\Http\Controllers\Api\CropFinancialSummaryController;
use App\Http\Controllers\Api\SeasonProfitController;
use App\Http\Controllers\Api\AnnualProfitController;
use App\Http\Controllers\Api\CropProfitComparisonController;
use App\Http\Controllers\Api\CropTypeProfitAnalysisController;
use App\Http\Controllers\Api\BreakEvenAnalysisController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProfitTrendController;
use App\Http\Controllers\Api\ExpenseDistributionController;
use App\Http\Controllers\Api\RevenueExpenseChartController;
use App\Http\Controllers\Api\CropPerformanceController;
use App\Http\Controllers\Api\DashboardExportController;
use App\Http\Controllers\Api\SeasonAnnualSummaryController;
use App\Http\Controllers\Api\WeatherController;


/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);


/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'user' => $request->user(),
        ]);
    });


    /*
    |--------------------------------------------------------------------------
    | Email Verification
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/email/verify/{id}/{hash}',
        function (EmailVerificationRequest $request) {

            $request->fulfill();

            return response()->json([
                'success' => true,
                'message' => 'Email verified successfully.',
            ]);
        }
    )
    ->middleware('signed')
    ->name('verification.verify');


    Route::post(
        '/email/verification-notification',
        function (Request $request) {

            if ($request->user()->hasVerifiedEmail()) {

                return response()->json([
                    'success' => false,
                    'message' => 'Email is already verified.',
                ], 400);
            }

            $request->user()
                ->sendEmailVerificationNotification();

            return response()->json([
                'success' => true,
                'message' => 'Verification email sent successfully.',
            ]);
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/profile',
        [ProfileController::class, 'show']
    );

    Route::put(
        '/profile',
        [ProfileController::class, 'update']
    );

    Route::post(
        '/change-password',
        [ProfileController::class, 'changePassword']
    );

    Route::post(
        '/profile/photo',
        [ProfileController::class, 'uploadPhoto']
    );


    /*
    |--------------------------------------------------------------------------
    | Farm Routes
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'farms',
        FarmController::class
    );


    /*
    |--------------------------------------------------------------------------
    | Farm Weather
    |--------------------------------------------------------------------------
    |
    | Weather is automatically based on the farm's
    | current district/location.
    |
    */

    Route::get(
        '/farms/{farm}/weather',
        [WeatherController::class, 'farmWeather']
    )->name('farms.weather');


    /*
    |--------------------------------------------------------------------------
    | Plot Routes
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'farms.plots',
        PlotController::class
    );


    /*
    |--------------------------------------------------------------------------
    | Crop Routes
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'farms.plots.crops',
        CropController::class
    );


    /*
    |--------------------------------------------------------------------------
    | Expense Routes
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'farms.expenses',
        ExpenseController::class
    );


    /*
    |--------------------------------------------------------------------------
    | Input Application Routes
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'crops.input-applications',
        InputApplicationController::class
    );

    Route::get(
        '/crops/{crop}/input-summary',
        [InputApplicationController::class, 'summary']
    )->name('crops.input-summary');


    /*
    |--------------------------------------------------------------------------
    | Agrochemical Product Routes
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'agrochemical-products',
        AgrochemicalProductController::class
    );


    /*
    |--------------------------------------------------------------------------
    | Harvest Routes
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'crops/{crop}/harvests',
        HarvestController::class
    );


    /*
    |--------------------------------------------------------------------------
    | Sale Routes
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'harvests/{harvest}/sales',
        SaleController::class
    );


    /*
    |--------------------------------------------------------------------------
    | Harvest Summary
    |--------------------------------------------------------------------------
    */

    Route::get(
        'crops/{crop}/harvest-summary',
        [HarvestSummaryController::class, 'show']
    );


    /*
    |--------------------------------------------------------------------------
    | Post Harvest Loss Routes
    |--------------------------------------------------------------------------
    */

    Route::get(
        'harvests/{harvest}/post-harvest-losses',
        [PostHarvestLossController::class, 'index']
    )->name('post-harvest-losses.index');

    Route::post(
        'harvests/{harvest}/post-harvest-losses',
        [PostHarvestLossController::class, 'store']
    )->name('post-harvest-losses.store');

    Route::get(
        'harvests/{harvest}/post-harvest-losses/{postHarvestLoss}',
        [PostHarvestLossController::class, 'show']
    )->name('post-harvest-losses.show');

    Route::put(
        'harvests/{harvest}/post-harvest-losses/{postHarvestLoss}',
        [PostHarvestLossController::class, 'update']
    )->name('post-harvest-losses.update');

    Route::delete(
        'harvests/{harvest}/post-harvest-losses/{postHarvestLoss}',
        [PostHarvestLossController::class, 'destroy']
    )->name('post-harvest-losses.destroy');


    /*
    |--------------------------------------------------------------------------
    | Financial Summary
    |--------------------------------------------------------------------------
    */

    Route::get(
        'crops/{crop}/financial-summary',
        [CropFinancialSummaryController::class, 'show']
    )->name('crops.financial-summary');


    /*
    |--------------------------------------------------------------------------
    | Profit Analysis
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/seasons/{season}/profit',
        [SeasonProfitController::class, 'show']
    );

    Route::get(
        '/annual/{year}/profit',
        [AnnualProfitController::class, 'show']
    );

    Route::get(
        '/crops/profit-comparison',
        [CropProfitComparisonController::class, 'compare']
    );

    Route::get(
        '/profit-analysis/crop-types',
        [CropTypeProfitAnalysisController::class, 'index']
    );

    Route::get(
        '/crops/{crop}/break-even',
        [BreakEvenAnalysisController::class, 'show']
    );


    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/dashboard',
        [DashboardController::class, 'index']
    );

    Route::get(
        '/dashboard/profit-trend',
        [ProfitTrendController::class, 'index']
    );

    Route::get(
        '/dashboard/expense-distribution',
        [ExpenseDistributionController::class, 'index']
    );

    Route::get(
        '/dashboard/revenue-vs-expenses',
        [RevenueExpenseChartController::class, 'index']
    );

    Route::get(
        '/dashboard/revenue-expenses',
        [RevenueExpenseChartController::class, 'index']
    );

    Route::get(
        '/dashboard/crop-performance',
        [CropPerformanceController::class, 'index']
    );


    /*
    |--------------------------------------------------------------------------
    | Dashboard CSV Export Routes
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/dashboard/profit-trend/export/csv',
        [DashboardExportController::class, 'profitTrend']
    );

    Route::get(
        '/dashboard/expense-distribution/export/csv',
        [DashboardExportController::class, 'expenseDistribution']
    );

    Route::get(
        '/dashboard/revenue-vs-expenses/export/csv',
        [DashboardExportController::class, 'revenueExpenses']
    );

    Route::get(
        '/dashboard/crop-performance/export/csv',
        [DashboardExportController::class, 'cropPerformance']
    );


    /*
    |--------------------------------------------------------------------------
    | Dashboard Summary
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/dashboard/summary/season',
        [SeasonAnnualSummaryController::class, 'season']
    );

    Route::get(
        '/dashboard/summary/annual/{year}',
        [SeasonAnnualSummaryController::class, 'annual']
    );


    /*
    |--------------------------------------------------------------------------
    | General Weather Route
    |--------------------------------------------------------------------------
    |
    | Example:
    | GET /api/weather?district=Kandy
    |
    */

    Route::get(
        '/weather',
        [WeatherController::class, 'index']
    );
});