<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function csrf(Request $request)
    {
        return response()->json(['csrf_token' => $request->session()->token()])->header('Cache-Control', 'no-store');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100', 'email' => 'required|email|max:150|unique:users,email',
            'password' => ['required', 'confirmed', 'max:255', PasswordRule::min(12)->letters()->mixedCase()->numbers()],
        ]);
        $user = DB::transaction(function () use ($data) {
            $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password_hash' => $data['password']]);
            $user->profile()->create([]);

            return $user->refresh();
        });
        Auth::login($user);
        $request->session()->regenerate();
        event(new Registered($user));

        return response()->json([
            'data' => $user->load('profile'), 'csrf_token' => $request->session()->token(),
            'message' => 'Account created. Check your email for the verification link.',
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|max:150', 'password' => 'required|string|max:255']);
        if (! Auth::attempt(array_merge($data, ['role' => 'registered', 'is_active' => true]))) {
            throw ValidationException::withMessages(['email' => 'The login details are invalid.']);
        }
        $request->session()->regenerate();
        UserActivityLog::create(['user_id' => Auth::id(), 'activity_type' => 'login']);

        return response()->json(['data' => Auth::user()->load('profile'), 'csrf_token' => $request->session()->token()]);
    }

    public function me(Request $request)
    {
        return response()->json(['data' => $request->user()->load(['profile', 'favoriteCategories'])]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Signed out.']);
    }

    public function resendVerification(Request $request)
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }

        return response()->json(['message' => 'If verification is needed, a fresh link has been sent.']);
    }

    public function verify(EmailVerificationRequest $request)
    {
        $request->fulfill();

        return response()->json(['message' => 'Email verified.', 'data' => $request->user()]);
    }

    public function changeEmail(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'current_password' => 'required|current_password:web',
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->getKey(), 'user_id')],
        ]);
        if ($data['email'] === $user->email) {
            return response()->json(['data' => $user, 'csrf_token' => $request->session()->token()]);
        }
        DB::transaction(function () use ($user, $data) {
            DB::table('password_reset_tokens')->whereIn('email', [$user->email, $data['email']])->delete();
            $user->forceFill(['email' => $data['email'], 'email_verified_at' => null, 'remember_token' => Str::random(60)])->save();
            DB::table('sessions')->where('user_id', $user->getKey())->delete();
        });
        $request->session()->regenerate();
        $user->sendEmailVerificationNotification();

        return response()->json(['data' => $user, 'csrf_token' => $request->session()->token(), 'message' => 'Email updated. Verify the new address.']);
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|current_password:web',
            'password' => ['required', 'confirmed', 'max:255', PasswordRule::min(12)->letters()->mixedCase()->numbers()],
        ]);
        DB::transaction(function () use ($request, $data) {
            $request->user()->forceFill(['password_hash' => $data['password'], 'remember_token' => Str::random(60)])->save();
            DB::table('sessions')->where('user_id', $request->user()->getKey())->delete();
            DB::table('password_reset_tokens')->where('email', $request->user()->email)->delete();
        });
        $request->session()->regenerate();

        return response()->json(['message' => 'Password changed. Other sessions revoked.', 'csrf_token' => $request->session()->token()]);
    }

    public function forgotPassword(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|max:150']);
        Password::sendResetLink(array_merge($data, ['role' => 'registered', 'is_active' => true]));

        return response()->json(['message' => 'If an active registered account matches, a reset link has been sent.']);
    }

    public function resetInfo(Request $request, string $token)
    {
        $prefix = $request->is('visitor/api/*') ? 'visitor' : 'user';

        return response()->json(['token' => $token, 'email' => $request->query('email'), 'submit_to' => url('/'.$prefix.'/api/auth/reset-password')])
            ->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string|max:255', 'email' => 'required|email|max:150',
            'password' => ['required', 'confirmed', 'max:255', PasswordRule::min(12)->letters()->mixedCase()->numbers()],
        ]);
        $status = Password::reset(array_merge($data, ['role' => 'registered', 'is_active' => true]), function (User $user, string $password) {
            DB::transaction(function () use ($user, $password) {
                $user->forceFill(['password_hash' => $password, 'remember_token' => Str::random(60)])->save();
                DB::table('sessions')->where('user_id', $user->getKey())->delete();
            });
            event(new PasswordReset($user));
        });
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'The reset link is invalid or expired.']);
        }

        return response()->json(['message' => 'Password reset. Please sign in.']);
    }

    public function sessions(Request $request)
    {
        $sessions = DB::table('sessions')->where('user_id', $request->user()->getKey())
            ->where('last_activity', '>=', now()->subMinutes((int) config('session.lifetime'))->timestamp)
            ->orderByDesc('last_activity')->get(['id', 'ip_address', 'user_agent', 'last_activity']);
        foreach ($sessions as $session) {
            $session->is_current = $session->id === $request->session()->getId();
        }

        return response()->json(['data' => $sessions]);
    }

    public function revokeSession(Request $request, string $id)
    {
        $deleted = DB::table('sessions')->where('user_id', $request->user()->getKey())->where('id', $id)->delete();
        abort_unless($deleted, 404, 'Session not found.');
        if ($id === $request->session()->getId()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->noContent();
    }
}
