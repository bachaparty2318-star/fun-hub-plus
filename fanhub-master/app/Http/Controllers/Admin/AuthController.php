<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function csrf(Request $request)
    {
        return response()->json(['csrf_token' => $request->session()->token()])
            ->header('Cache-Control', 'no-store');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|max:150', 'password' => 'required|string|max:255']);
        $data['role'] = 'admin';
        $data['is_active'] = true;
        if (! Auth::attempt($data)) {
            throw ValidationException::withMessages(['email' => 'The login details are invalid.']);
        }
        $request->session()->regenerate();
        UserActivityLog::create(['user_id' => Auth::id(), 'activity_type' => 'login']);

        return response()->json(['data' => Auth::user(), 'csrf_token' => $request->session()->token()]);
    }

    public function me(Request $request)
    {
        return response()->json(['data' => $request->user()->load('profile')]);
    }

    public function avatar(Request $request)
    {
        $request->validate(['file' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:5120']);
        $path = $request->file('file')->store('avatars/'.$request->user()->getKey(), 'public');
        abort_unless($path, 500, 'Avatar could not be saved.');
        $url = Storage::disk('public')->url($path);
        $request->user()->profile()->updateOrCreate(['user_id' => $request->user()->getKey()], ['avatar_url' => $url]);

        return response()->json(['data' => ['avatar_url' => $url]], 201);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Signed out.']);
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
        Password::sendResetLink(array_merge($data, ['role' => 'admin', 'is_active' => true]));

        return response()->json(['message' => 'If an active admin account matches, a password reset link has been sent.']);
    }

    public function resetInfo(Request $request, string $token)
    {
        return response()->json([
            'token' => $token, 'email' => $request->query('email'),
            'message' => 'Submit token, email, password and password_confirmation to POST admin/api/auth/reset-password.',
        ])->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string|max:255', 'email' => 'required|email|max:150',
            'password' => ['required', 'confirmed', 'max:255', PasswordRule::min(12)->letters()->mixedCase()->numbers()],
        ]);
        $status = Password::reset(array_merge($data, ['role' => 'admin', 'is_active' => true]), function (User $user, string $password) {
            DB::transaction(function () use ($user, $password) {
                $user->forceFill(['password_hash' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
                DB::table('sessions')->where('user_id', $user->getKey())->delete();
            });
            event(new PasswordReset($user));
        });
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'The reset link is invalid or expired.']);
        }

        return response()->json(['message' => 'Password reset. Please sign in.']);
    }
}
