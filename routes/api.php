<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\BookmarkController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\CompanyMembershipController;
use App\Http\Controllers\CompanyReviewController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VacancyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// stripe envía el aviso de pago sin iniciar sesión en nextstep.
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);

// postman utiliza cookies de sesión y x-csrf-token, igual que los formularios web.
Route::middleware('web')->group(function () {
    Route::get('/session', function (Request $request) {
        $user = $request->user('web');

        return response()->json([
            'csrf_token' => csrf_token(),
            'user' => $user ? ['id' => (string) $user->getKey(), 'email' => $user->email, 'role' => $user->role] : null,
        ])->header('Cache-Control', 'no-store, private');
    });
    Route::get('/memberships', [MembershipController::class, 'index']);

    Route::prefix('admin')->middleware(['auth:web', 'role:admin'])->group(function () {
        Route::get('/students', [AdminController::class, 'students']);
        Route::get('/students/{id}', [AdminController::class, 'student'])->where('id', '[a-f0-9]{24}');
        Route::get('/students/{id}/{document}', [AdminController::class, 'document'])->where('id', '[a-f0-9]{24}')->where('document', 'cv|kardex');
        Route::get('/companies', [AdminController::class, 'companies']);
        Route::get('/companies/{id}', [AdminController::class, 'company'])->where('id', '[a-f0-9]{24}');
    });
    Route::prefix('company')->middleware(['auth:web', 'role:company'])->group(function () {
        Route::get('/membership', [CompanyMembershipController::class, 'index']);
    });

    // otras rutas de api usadas por el administrador
    Route::middleware(['auth:web', 'role:admin'])->group(function () {
        Route::apiResource('users', UserController::class);
        Route::apiResource('vacancies', VacancyController::class);
        Route::apiResource('applications', ApplicationController::class);
        Route::apiResource('company_reviews', CompanyReviewController::class);
        Route::apiResource('bookmarks', BookmarkController::class)->only(['index', 'store', 'show', 'destroy']);
    });
});
