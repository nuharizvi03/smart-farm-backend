<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\FarmController;
use App\Http\Controllers\Api\PlotController;

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

});