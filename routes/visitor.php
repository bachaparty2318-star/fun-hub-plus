<?php

use App\Http\Controllers\Member\AuthController;
use App\Http\Controllers\Member\CatalogController;
use App\Http\Controllers\Member\EventController;
use App\Http\Controllers\Member\FeedbackController;
use App\Http\Controllers\Visitor\PageController;
use App\Http\Middleware\MemberResponseHeaders;
use Illuminate\Support\Facades\Route;

Route::get('category/{slug}', [PageController::class, 'category'])->name('visitor.category');
Route::get('visitor/categories/{slug}', [PageController::class, 'category']);
Route::get('content/{id}', [PageController::class, 'contentById'])->whereNumber('id')->name('visitor.content');
Route::get('content/{type}/{id}', [PageController::class, 'content'])->where('type', 'content|article|media|merchandise')->whereNumber('id')->name('visitor.content.typed');
Route::get('characters', [PageController::class, 'characters'])->name('visitor.characters');
Route::get('character/{id}', [PageController::class, 'character'])->whereNumber('id')->name('visitor.character');
Route::get('articles', [PageController::class, 'articles'])->name('visitor.articles');
Route::get('article/{id}', [PageController::class, 'article'])->whereNumber('id')->name('visitor.article');
Route::get('merchandise', [PageController::class, 'merchandise'])->name('visitor.merchandise');
Route::get('merchandise/{id}', [PageController::class, 'merchandiseDetail'])->whereNumber('id')->name('visitor.merchandise.detail');
Route::get('events', [PageController::class, 'events'])->name('visitor.events');
Route::get('explore', [PageController::class, 'explore'])->name('visitor.explore');
Route::get('login', fn () => redirect('/user/login'))->name('visitor.login');
Route::get('register', fn () => view('member.login', ['mode' => 'register']))->name('visitor.register');
Route::get('forgot-password', fn () => view('member.login', ['mode' => 'forgot']))->name('visitor.forgot');
Route::get('reset-password/{token}', fn (string $token) => view('member.login', ['mode' => 'reset', 'token' => $token]))->name('visitor.reset');
Route::get('feedback', fn () => redirect('/user/login')->with('message', 'Please log in or sign up to send feedback.'))->name('visitor.feedback');
Route::get('submissions', fn () => redirect('/user/login')->with('message', 'Please log in or sign up to submit fan content.'))->name('visitor.submissions');

Route::prefix('visitor/api')->middleware([MemberResponseHeaders::class, 'throttle:member-api'])->group(function () {
    Route::get('auth/csrf', [AuthController::class, 'csrf']);
    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:member-registration');
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:member-login');
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:member-recovery');
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:member-recovery');
    Route::get('auth/reset-password/{token}', [AuthController::class, 'resetInfo'])->middleware('throttle:member-recovery')->name('visitor.password.reset');

    Route::get('categories', [CatalogController::class, 'categories']);
    Route::get('tags', [CatalogController::class, 'tags']);
    Route::get('explore', [CatalogController::class, 'index']);
    Route::get('upcoming', [CatalogController::class, 'index'])->name('visitor.upcoming');
    Route::get('catalog/{type}/{id}', [CatalogController::class, 'show'])->where('type', 'content|article|character|media|merchandise')->whereNumber('id')->name('visitor.catalog.show');
    Route::get('share/{type}/{id}', [CatalogController::class, 'share'])->where('type', 'content|article|character|media|merchandise')->whereNumber('id');
    Route::get('events', [EventController::class, 'index']);
    Route::get('events/{id}', [EventController::class, 'show'])->whereNumber('id');
    Route::post('feedback', [FeedbackController::class, 'store'])->middleware('throttle:member-feedback');
});
