<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class SetAdminPassword extends Command
{
    protected $signature = 'admin:set-password {email=admin@fanhubplus.com}';

    protected $description = 'Set an existing active administrator password through a hidden prompt';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->where('role', 'admin')->where('is_active', true)->first();
        if (! $user) {
            $this->error('Active administrator not found.');

            return self::FAILURE;
        }
        $password = $this->secret('New password (12+ characters, upper/lowercase and a number)');
        $confirmation = $this->secret('Confirm password');
        $validator = Validator::make(['password' => $password, 'password_confirmation' => $confirmation], [
            'password' => ['required', 'confirmed', 'max:255', Password::min(12)->letters()->mixedCase()->numbers()],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }
        DB::transaction(function () use ($user, $password) {
            $user->forceFill(['password_hash' => $password, 'remember_token' => Str::random(60)])->save();
            DB::table('sessions')->where('user_id', $user->getKey())->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        });
        $this->info('Administrator password updated; existing sessions revoked.');

        return self::SUCCESS;
    }
}
