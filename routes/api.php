<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
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
            'user' => $request->user()
        ]);
    });


    /*
    |--------------------------------------------------------------------------
    | Email Verification
    |--------------------------------------------------------------------------
    */

    Route::get('/email/verify/{id}/{hash}', function (
        EmailVerificationRequest $request
    ) {
        $request->fulfill();

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully.'
        ]);
    })->middleware('signed')
      ->name('verification.verify');


    Route::post('/email/verification-notification', function (
        Request $request
    ) {

        if ($request->user()->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Email is already verified.'
            ], 400);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json([
            'success' => true,
            'message' => 'Verification email sent successfully.'
        ]);
    });


    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */

    Route::get('/profile', [ProfileController::class, 'show']);

    Route::put('/profile', [ProfileController::class, 'update']);

    Route::post('/change-password', [
        ProfileController::class,
        'changePassword'
    ]);

    Route::post('/profile/photo', [
        ProfileController::class,
        'uploadPhoto'
    ]);
    
    Route::apiResource('farms', FarmController::class);
    Route::apiResource('farms.plots', PlotController::class);

    Route::apiResource(
    'farms.plots.crops',
    CropController::class
    );

    Route::apiResource(
    'farms.expenses',
    ExpenseController::class
    );

    Route::apiResource(
    'crops.input-applications',
    InputApplicationController::class
    );

    Route::apiResource(
    'agrochemical-products',
    AgrochemicalProductController::class
    );

    Route::get(
    '/crops/{crop}/input-summary',
    [InputApplicationController::class, 'summary']
    )->name('crops.input-summary');

    Route::apiResource(
    'crops/{crop}/harvests',
    HarvestController::class
    );

    Route::apiResource(
    'harvests/{harvest}/sales',
    SaleController::class
    );

    Route::get(
    'crops/{crop}/harvest-summary',
    [HarvestSummaryController::class, 'show']
    );

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

Route::get(
    'crops/{crop}/financial-summary',
    [CropFinancialSummaryController::class, 'show']
)->name('crops.financial-summary');

});