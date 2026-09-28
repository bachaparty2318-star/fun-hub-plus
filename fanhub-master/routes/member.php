<?php

use App\Http\Controllers\Member\AuthController;
use App\Http\Controllers\Member\BookmarkController;
use App\Http\Controllers\Member\CatalogController;
use App\Http\Controllers\Member\DashboardController;
use App\Http\Controllers\Member\EventController;
use App\Http\Controllers\Member\FeedbackController;
use App\Http\Controllers\Member\ProfileController;
use App\Http\Controllers\Member\RatingController;
use App\Http\Controllers\Member\SubmissionController;
use App\Http\Middleware\MemberResponseHeaders;
use App\Http\Middleware\RequireActiveUser;
use App\Http\Middleware\RequireVerifiedEmail;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('user/login', fn () => view('member.login', ['mode' => 'login']))->name('member.login');

Route::get('member/assets/{path}', function (string $path) {
    abort_if(str_contains($path, '..'), 404);
    $file = base_path('template/fanhub-member-template/assets/'.$path);
    abort_unless(is_file($file), 404);
    $contentType = match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
        'css' => 'text/css; charset=UTF-8',
        'js' => 'application/javascript; charset=UTF-8',
        default => 'application/octet-stream',
    };

    return response()->file($file, ['Content-Type' => $contentType]);
})->where('path', '.*');

Route::get('user', function (Request $request) {
    if (! $request->user() || $request->user()->role !== 'registered' || ! $request->user()->is_active) {
        return redirect()->route('member.login');
    }

    ob_start();
    include base_path('template/fanhub-member-template/index.php');

    return response(ob_get_clean());
})->name('member.dashboard');

Route::prefix('user/api')->middleware([MemberResponseHeaders::class, 'throttle:member-api'])->group(function () {
    Route::get('auth/csrf', [AuthController::class, 'csrf']);
    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:member-registration');
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:member-login');
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:member-recovery');
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:member-recovery');
    Route::get('auth/reset-password/{token}', [AuthController::class, 'resetInfo'])->middleware('throttle:member-recovery')->name('member.password.reset');

    Route::get('categories', [CatalogController::class, 'categories']);
    Route::get('tags', [CatalogController::class, 'tags']);
    Route::get('explore', [CatalogController::class, 'index']);
    Route::get('upcoming', [CatalogController::class, 'index'])->name('member.upcoming');
    Route::get('catalog/{type}/{id}', [CatalogController::class, 'show'])->where('type', 'content|article|character|media|merchandise')->whereNumber('id')->name('member.catalog.show');
    Route::get('share/{type}/{id}', [CatalogController::class, 'share'])->where('type', 'content|article|character|media|merchandise')->whereNumber('id');
    Route::get('events', [EventController::class, 'index']);
    Route::get('events/{id}', [EventController::class, 'show'])->whereNumber('id');
    Route::post('feedback', [FeedbackController::class, 'store'])->middleware('throttle:member-feedback');

    Route::middleware(RequireActiveUser::class)->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::put('auth/password', [AuthController::class, 'changePassword']);
        Route::put('auth/email', [AuthController::class, 'changeEmail']);
        Route::post('auth/email/resend', [AuthController::class, 'resendVerification'])->middleware('throttle:member-verification');
        Route::get('auth/email/verify/{id}/{hash}', [AuthController::class, 'verify'])
            ->middleware(['signed', 'throttle:member-verification'])->whereNumber('id')
            ->where('hash', '[a-f0-9]{40}')->name('member.verification.verify');
        Route::get('auth/sessions', [AuthController::class, 'sessions']);
        Route::delete('auth/sessions/{id}', [AuthController::class, 'revokeSession'])->where('id', '[A-Za-z0-9_-]+');

        Route::get('profile', [ProfileController::class, 'show']);
        Route::patch('profile', [ProfileController::class, 'update']);
        Route::post('profile/avatar', [ProfileController::class, 'avatar'])->middleware(['throttle:member-uploads', RequireVerifiedEmail::class]);

        Route::middleware(RequireVerifiedEmail::class)->group(function () {
            Route::get('dashboard', [DashboardController::class, 'index']);
            Route::get('activity', [DashboardController::class, 'activity']);
            Route::get('bookmarks', [BookmarkController::class, 'index']);
            Route::post('bookmarks', [BookmarkController::class, 'store']);
            Route::patch('bookmarks/{id}', [BookmarkController::class, 'update'])->whereNumber('id');
            Route::delete('bookmarks/{id}', [BookmarkController::class, 'destroy'])->whereNumber('id');

            Route::get('media/{id}/rating', [RatingController::class, 'show'])->whereNumber('id');
            Route::put('media/{id}/rating', [RatingController::class, 'store'])->whereNumber('id')->middleware('throttle:member-contributions');
            Route::delete('media/{id}/rating', [RatingController::class, 'destroy'])->whereNumber('id');

            Route::get('submissions', [SubmissionController::class, 'index']);
            Route::post('submissions', [SubmissionController::class, 'store'])->middleware('throttle:member-contributions');
            Route::post('submissions/image', [SubmissionController::class, 'image'])->middleware('throttle:member-uploads');
            Route::get('submissions/{id}', [SubmissionController::class, 'show'])->whereNumber('id');
            Route::patch('submissions/{id}', [SubmissionController::class, 'update'])->whereNumber('id')->middleware('throttle:member-contributions');
            Route::delete('submissions/{id}', [SubmissionController::class, 'destroy'])->whereNumber('id');
            Route::get('feedback', [FeedbackController::class, 'index']);
            Route::get('feedback/{id}', [FeedbackController::class, 'show'])->whereNumber('id');
        });
    });
});
