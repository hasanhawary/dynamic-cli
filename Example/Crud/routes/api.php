<?php

use App\Http\Controllers\API\Auth\ForgetPasswordController;
use App\Http\Controllers\API\Auth\LoginController;
use App\Http\Controllers\API\Auth\ResetPasswordController;
use App\Http\Controllers\API\DataEntry\CountryController;
use App\Http\Controllers\API\Example\CrudController;
use App\Http\Controllers\API\Global\Chunk\ChunkFileController;
use App\Http\Controllers\API\Global\Export\ExportController;
use App\Http\Controllers\API\Global\Help\HelpController;
use App\Http\Controllers\API\Global\Notification\NotificationController;
use App\Http\Controllers\API\Global\Report\ReportController;
use App\Http\Controllers\API\Global\Setting\ActivityLogController;
use App\Http\Controllers\API\Global\Setting\CaptchaController;
use App\Http\Controllers\API\Global\Setting\SettingController;
use App\Http\Controllers\API\User\PermissionController;
use App\Http\Controllers\API\User\ProfileController;
use App\Http\Controllers\API\User\RoleController;
use App\Http\Controllers\API\User\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Captcha Routes
|--------------------------------------------------------------------------
*/
Route::prefix('captcha')->group(function () {
    Route::get('/', [CaptchaController::class, 'generateCaptcha']);
    Route::post('/verify', [CaptchaController::class, 'verifyCaptcha']);
});

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/
Route::post('login', [LoginController::class, 'login']);
Route::post('forget', [ForgetPasswordController::class, 'forget'])->name('forget');
Route::post('verify-otp', [ForgetPasswordController::class, 'verify'])->name('verify');
Route::post('reset', [ResetPasswordController::class, 'reset'])->name('reset');
Route::apiResource('countries', CountryController::class)->only(['index', 'show']);

Route::middleware(['auth:sanctum'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | activity log Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('get-activity-logs')->group(function () {
        Route::get('/', [ActivityLogController::class, 'index']);
        Route::get('/{activity}', [ActivityLogController::class, 'show']);
    });

    /*
    |--------------------------------------------------------------------------
    | User Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('users')->name('users.')->group(function () {
        Route::delete('delete-all', [UserController::class, 'destroyAll'])->name('destroyAll');
        Route::post('{id}/restore', [UserController::class, 'restore'])->name('restore');
        Route::post('{user}/change-status', [UserController::class, 'changeStatus'])->name('changeStatus');
        Route::delete('{id}/force-delete', [UserController::class, 'forceDelete'])->name('forceDelete');

        Route::apiResource('/', UserController::class)->parameters(['' => 'user']);
    });

    //Role Routes
    Route::apiResource('roles', RoleController::class);

    Route::delete('permissions/delete-all', [PermissionController::class, 'destroyAll']);
    Route::apiResource('permissions', PermissionController::class);

    //Profile Routes
    Route::get('me', [ProfileController::class, 'user']);
    Route::post('update-profile', [ProfileController::class, 'updateProfile']);
    Route::post('destroy-avatar', [ProfileController::class, 'destroyAvatar']);
    Route::post('logout', [LoginController::class, 'logout']);

    /*
    |--------------------------------------------------------------------------
    | Global Routes
    |--------------------------------------------------------------------------
    */
    Route::post('chunk-file', ChunkFileController::class);

    Route::get('help-models', [HelpController::class, 'models']);
    Route::get('help-enums', [HelpController::class, 'enums']);

    Route::put('notifications', [NotificationController::class, 'update']);
    Route::get('notifications', [NotificationController::class, 'index']);

    Route::get('report', ReportController::class);
    Route::get('export', ExportController::class);

    /*
    |--------------------------------------------------------------------------
    | Data Entry Routes
    |--------------------------------------------------------------------------
    */
    // Countries
    Route::post('countries/restore', [CountryController::class, 'restore']);
    Route::delete('countries/delete', [CountryController::class, 'destroy']);
    Route::delete('countries/force-delete', [CountryController::class, 'forceDelete']);
    Route::apiResource('countries', CountryController::class);

    /*
    |--------------------------------------------------------------------------
    | Setting Routes
    |--------------------------------------------------------------------------
    */
    Route::get('settings', [SettingController::class, 'index']);
    Route::get('settings', [SettingController::class, 'publicSetting']);
    Route::post('set-settings', [SettingController::class, 'setConfigForUser']);
    Route::post('send-test-mail', [SettingController::class, 'testMailCredentials']);

    /*
     |--------------------------------------------------------------------------
     | Example Routes
     |--------------------------------------------------------------------------
     */
    Route::post('countries/restore', [CountryController::class, 'restore']);
    Route::delete('countries/delete', [CountryController::class, 'destroy']);
    Route::delete('countries/force-delete', [CountryController::class, 'forceDelete']);
    Route::apiResource('cruds', CrudController::class);
});

// Generated by dynamic-cli on 2025-09-21 19:19:44
Route::apiResource('tests', \App\Http\Controllers\TestController::class);
