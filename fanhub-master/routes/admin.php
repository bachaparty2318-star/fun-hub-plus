<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FeedbackController;
use App\Http\Controllers\Admin\ResourceController;
use App\Http\Controllers\Admin\SubmissionController;
use App\Http\Controllers\Admin\UploadController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Middleware\RequireAdmin;
use App\Services\AdminResources;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

Route::get('admin/login', fn () => view('admin.login'))->name('admin.login');

Route::post('admin/login', function (Request $request) {
    $data = $request->validate([
        'email' => 'required|email|max:150',
        'password' => 'required|string|max:255',
    ]);

    if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password'], 'role' => 'admin', 'is_active' => true])) {
        return back()->withErrors(['email' => 'The login details are invalid.'])->withInput();
    }

    $request->session()->regenerate();

    return redirect()->route('admin.dashboard');
})->name('admin.login.submit');

Route::get('admin/assets/{path}', function (string $path) {
    abort_if(str_contains($path, '..'), 404);
    $file = base_path('template/fanhub-admin-template/assets/'.$path);
    abort_unless(is_file($file), 404);

    $contentType = match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
        'css' => 'text/css; charset=UTF-8',
        'js' => 'application/javascript; charset=UTF-8',
        'json' => 'application/json; charset=UTF-8',
        default => 'application/octet-stream',
    };

    return response()->file($file, ['Content-Type' => $contentType, 'Cache-Control' => 'no-store, no-cache, must-revalidate']);
})->where('path', '.*');

Route::get('admin', function (Request $request) {
    if (! $request->user() || $request->user()->role !== 'admin' || ! $request->user()->is_active) {
        return redirect()->route('admin.login');
    }

    ob_start();
    include base_path('template/fanhub-admin-template/index.php');

    return response(ob_get_clean());
})->name('admin.dashboard');

Route::prefix('admin/api')->group(function () {
    Route::get('auth/csrf', [AuthController::class, 'csrf']);
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:admin-login');
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:admin-recovery');
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:admin-recovery');
    Route::get('auth/reset-password/{token}', [AuthController::class, 'resetInfo'])->name('admin.password.reset')->middleware('throttle:admin-recovery');

    Route::middleware([RequireAdmin::class, 'throttle:admin-api'])->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/avatar', [AuthController::class, 'avatar'])->middleware('throttle:admin-uploads');
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::put('auth/password', [AuthController::class, 'changePassword']);
        Route::get('dashboard', [DashboardController::class, 'index']);
        Route::get('activity', [DashboardController::class, 'activity']);
        Route::get('ratings', [DashboardController::class, 'ratings']);
        Route::get('media/{id}/ratings', [DashboardController::class, 'ratingSummary'])->whereNumber('id');
        Route::post('uploads', [UploadController::class, 'store'])->middleware('throttle:admin-uploads');

        Route::get('users', [UserController::class, 'index']);
        Route::post('users', [UserController::class, 'store']);
        Route::get('users/{id}', [UserController::class, 'show'])->whereNumber('id');
        Route::match(['put', 'patch'], 'users/{id}', [UserController::class, 'update'])->whereNumber('id');
        Route::match(['put', 'patch'], 'users/{id}/profile', [UserController::class, 'updateProfile'])->whereNumber('id');
        Route::delete('users/{id}', [UserController::class, 'destroy'])->whereNumber('id');

        Route::get('submissions', [SubmissionController::class, 'index']);
        Route::get('submissions/{id}', [SubmissionController::class, 'show'])->whereNumber('id');
        Route::post('submissions/{id}/review', [SubmissionController::class, 'review'])->whereNumber('id');
        Route::delete('submissions/{id}', [SubmissionController::class, 'destroy'])->whereNumber('id');
        Route::get('feedback', [FeedbackController::class, 'index']);
        Route::get('feedback/{id}', [FeedbackController::class, 'show'])->whereNumber('id');
        Route::match(['put', 'patch'], 'feedback/{id}', [FeedbackController::class, 'update'])->whereNumber('id');
        Route::delete('feedback/{id}', [FeedbackController::class, 'destroy'])->whereNumber('id');

        $resources = implode('|', array_keys(app(AdminResources::class)->all()));
        Route::get('{resource}', [ResourceController::class, 'index'])->where('resource', $resources);
        Route::post('{resource}', [ResourceController::class, 'store'])->where('resource', $resources);
        Route::get('{resource}/{id}', [ResourceController::class, 'show'])->where('resource', $resources)->whereNumber('id');
        Route::match(['put', 'patch'], '{resource}/{id}', [ResourceController::class, 'update'])->where('resource', $resources)->whereNumber('id');
        Route::delete('{resource}/{id}', [ResourceController::class, 'destroy'])->where('resource', $resources)->whereNumber('id');
    });
});
