<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\CharacterProfile;
use App\Models\Content;
use App\Models\Media;
use App\Models\MerchandiseItem;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Relation::morphMap([
            'content' => Content::class, 'article' => Article::class,
            'character' => CharacterProfile::class, 'media' => Media::class,
            'merchandise' => MerchandiseItem::class,
        ]);
        RateLimiter::for('admin-login', fn (Request $request) => [
            Limit::perMinute(5)->by('login:'.Str::lower(is_string($request->input('email')) ? $request->input('email') : '').'|'.$request->ip()),
            Limit::perMinute(30)->by('login-ip:'.$request->ip()),
        ]);
        RateLimiter::for('admin-recovery', fn (Request $request) => Limit::perMinute(5)->by('recovery:'.$request->ip()));
        RateLimiter::for('admin-api', fn (Request $request) => Limit::perMinute(120)->by('admin:'.($request->user()?->getKey() ?? $request->ip())));
        RateLimiter::for('admin-uploads', fn (Request $request) => Limit::perMinute(15)->by('upload:'.($request->user()?->getKey() ?? $request->ip())));
        RateLimiter::for('member-api', fn (Request $request) => Limit::perMinute(180)->by('member-api:'.($request->user()?->getKey() ?? $request->ip())));
        RateLimiter::for('member-registration', fn (Request $request) => Limit::perMinute(5)->by('member-register:'.$request->ip()));
        RateLimiter::for('member-login', fn (Request $request) => [
            Limit::perMinute(5)->by('member-login:'.Str::lower(is_string($request->input('email')) ? $request->input('email') : '').'|'.$request->ip()),
            Limit::perMinute(30)->by('member-login-ip:'.$request->ip()),
        ]);
        RateLimiter::for('member-recovery', fn (Request $request) => Limit::perMinute(5)->by('member-reset:'.$request->ip()));
        RateLimiter::for('member-verification', fn (Request $request) => Limit::perMinute(5)->by('verify:'.($request->user()?->getKey() ?? $request->ip())));
        RateLimiter::for('member-uploads', fn (Request $request) => Limit::perMinute(10)->by('member-upload:'.($request->user()?->getKey() ?? $request->ip())));
        RateLimiter::for('member-contributions', fn (Request $request) => Limit::perMinute(20)->by('member-write:'.($request->user()?->getKey() ?? $request->ip())));
        RateLimiter::for('member-feedback', fn (Request $request) => Limit::perMinute(5)->by('feedback:'.($request->user()?->getKey() ?? $request->ip())));
        VerifyEmail::createUrlUsing(fn ($user) => URL::temporarySignedRoute(
            'member.verification.verify', now()->addMinutes(60),
            ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())],
        ));
        ResetPassword::createUrlUsing(fn ($user, string $token) => route($user->role === 'admin' ? 'admin.password.reset' : 'member.password.reset', ['token' => $token, 'email' => $user->email]));
    }
}
