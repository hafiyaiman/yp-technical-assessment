<?php

namespace App\Services\Auth;

use App\Models\LoginOtp;
use App\Models\User;
use App\Notifications\LoginOtpNotification;
use Illuminate\Support\Facades\Hash;

class LoginOtpService
{
    public const SESSION_USER_ID = 'login_otp.user_id';

    public const SESSION_REMEMBER = 'login_otp.remember';

    public function send(User $user, bool $remember = false): void
    {
        $code = (string) random_int(100000, 999999);

        LoginOtp::query()
            ->where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->delete();

        LoginOtp::query()->create([
            'user_id' => $user->id,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ]);

        session([
            self::SESSION_USER_ID => $user->id,
            self::SESSION_REMEMBER => $remember,
        ]);

        $user->notify(new LoginOtpNotification($code));
    }
}
