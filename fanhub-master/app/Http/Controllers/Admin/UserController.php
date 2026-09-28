<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\SafeUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'q' => 'nullable|string|max:150', 'role' => 'sometimes|in:admin,registered',
            'is_active' => 'sometimes|boolean', 'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
        ]);
        $query = User::with('profile')->orderByDesc('user_id');
        if (! empty($data['q'])) {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$data['q'].'%')->orWhere('email', 'like', '%'.$data['q'].'%'));
        }
        foreach (['role', 'is_active'] as $field) {
            if (isset($data[$field])) {
                $query->where($field, $data[$field]);
            }
        }

        $paginator = $query->paginate($data['per_page'] ?? 20)->withQueryString();
        $onlineSince = now()->subMinutes(15)->timestamp;
        $paginator->getCollection()->transform(function (User $user) use ($onlineSince) {
            $user->setAttribute('is_online', DB::table('sessions')->where('user_id', $user->getKey())->where('last_activity', '>=', $onlineSince)->exists());
            return $user;
        });

        return $paginator;
    }

    public function show(int $id)
    {
        return response()->json(['data' => User::with(['profile', 'favoriteCategories'])->withCount(['bookmarks', 'submissions'])->findOrFail($id)]);
    }

    public function store(Request $request)
    {
        $data = $this->validateUser($request);

        return DB::transaction(function () use ($data) {
            $user = new User;
            $user->forceFill($this->attributes($data))->save();
            $user->profile()->create([]);

            return response()->json(['data' => $user->refresh()->load('profile')], 201);
        });
    }

    public function update(Request $request, int $id)
    {
        return DB::transaction(function () use ($request, $id) {
            // Serialize changes to administrators so concurrent requests cannot remove the final admin.
            User::where('role', 'admin')->orderBy('user_id')->lockForUpdate()->get();
            $user = User::lockForUpdate()->findOrFail($id);
            $data = $this->validateUser($request, $user);
            $newRole = $data['role'] ?? $user->role;
            $newActive = $data['is_active'] ?? $user->is_active;
            if ($id === $request->user()->getKey()) {
                abort_if($newRole !== 'admin' || ! $newActive, 409, 'You cannot deactivate or demote your own account.');
                abort_if(isset($data['password']), 422, 'Use the change-password endpoint to change your own password.');
            }
            $this->protectLastAdmin($user, $newRole, (bool) $newActive);
            $oldEmail = $user->email;
            $oldRole = $user->role;
            $attributes = $this->attributes($data);
            if (isset($data['email']) && $data['email'] !== $oldEmail && ! array_key_exists('email_verified', $data)) {
                $attributes['email_verified_at'] = null;
            }
            $user->forceFill($attributes)->save();
            if (isset($data['password']) || $newRole !== $oldRole || ! $newActive || $oldEmail !== $user->email) {
                $this->revoke($user, $oldEmail);
            }

            return response()->json(['data' => $user->refresh()->load('profile')]);
        });
    }

    public function updateProfile(Request $request, int $id)
    {
        $user = User::findOrFail($id);
        $data = $request->validate([
            'avatar_url' => ['nullable', 'string', 'max:500', new SafeUrl],
            'bio' => 'nullable|string|max:500', 'dark_mode_enabled' => 'sometimes|boolean',
            'font_size' => 'sometimes|in:small,medium,large', 'favorite_fandoms' => 'nullable|array|max:50',
            'favorite_fandoms.*' => 'string|max:150|distinct',
            'category_ids' => 'sometimes|array|max:50', 'category_ids.*' => 'integer|distinct|exists:categories,category_id',
        ]);

        return DB::transaction(function () use ($user, $data) {
            $categories = $data['category_ids'] ?? null;
            unset($data['category_ids']);
            $user->profile()->updateOrCreate(['user_id' => $user->getKey()], $data);
            if ($categories !== null) {
                $user->favoriteCategories()->sync($categories);
            }

            return response()->json(['data' => $user->load(['profile', 'favoriteCategories'])]);
        });
    }

    public function destroy(Request $request, int $id)
    {
        return DB::transaction(function () use ($request, $id) {
            User::where('role', 'admin')->orderBy('user_id')->lockForUpdate()->get();
            $user = User::lockForUpdate()->findOrFail($id);
            abort_if($id === $request->user()->getKey(), 409, 'You cannot delete your own account.');
            $this->protectLastAdmin($user, 'registered', false);
            $this->revoke($user, $user->email);
            $user->delete();

            return response()->noContent();
        });
    }

    private function protectLastAdmin(User $user, string $role, bool $active): void
    {
        if ($user->role === 'admin' && $user->is_active && ($role !== 'admin' || ! $active)) {
            abort_if(User::where('role', 'admin')->where('is_active', true)->where('user_id', '<>', $user->getKey())->count() === 0, 409, 'At least one active administrator must remain.');
        }
    }

    private function revoke(User $user, string $oldEmail): void
    {
        $user->forceFill(['remember_token' => Str::random(60)])->save();
        DB::table('sessions')->where('user_id', $user->getKey())->delete();
        DB::table('password_reset_tokens')->whereIn('email', [$oldEmail, $user->email])->delete();
    }

    private function attributes(array $data): array
    {
        if (isset($data['password'])) {
            $data['password_hash'] = $data['password'];
            unset($data['password']);
        }
        if (array_key_exists('email_verified', $data)) {
            $data['email_verified_at'] = $data['email_verified'] ? now() : null;
            unset($data['email_verified']);
        }

        return $data;
    }

    private function validateUser(Request $request, ?User $user = null): array
    {
        $presence = $user ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$presence, 'required', 'string', 'max:100'],
            'email' => [$presence, 'required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user?->getKey(), 'user_id')],
            'password' => [$presence, 'required', 'string', 'max:255', Password::min(12)->letters()->mixedCase()->numbers()],
            'role' => 'sometimes|in:admin,registered', 'is_active' => 'sometimes|boolean',
            'email_verified' => 'sometimes|boolean',
        ]);
    }
}
