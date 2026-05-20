<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Handle User Registration
     */
    public function register(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'student' // Default role
        ]);

        // Auto-send verification OTP upon signup
        $this->sendVerificationOtp($user->email);

        return [
            'user' => $user,
            'verified' => false
        ];
    }

    /**
     * Handle User Login
     */
    public function login(array $credentials)
    {
        if (!$token = JWTAuth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        $user = auth()->user();

        // If email is not verified, block authentication, send new OTP and return status
        if (is_null($user->email_verified_at)) {
            try {
                $this->sendVerificationOtp($user->email);
            } catch (\Exception $e) {
                // If locked out, keep the lockout message intact
            }

            return [
                'status' => 'unverified',
                'email' => $user->email,
                'message' => 'Please verify your email address. A fresh OTP code has been sent to your email.'
            ];
        }

        return [
            'status' => 'success',
            'user' => $user,
            'token' => $token
        ];
    }

    /**
     * Send Verification OTP
     */
    public function sendVerificationOtp(string $email)
    {
        // 1. Fetch or create OTP record
        $otpRecord = \App\Models\PasswordResetOtp::firstOrNew(['email' => $email]);

        // 2. Check for active lockout
        if ($otpRecord->locked_until && $otpRecord->locked_until->isFuture()) {
            $remainingSeconds = $otpRecord->locked_until->diffInSeconds(now());
            throw ValidationException::withMessages([
                'email' => ['Too many failed attempts. Try again in ' . $remainingSeconds . ' seconds.'],
            ]);
        }

        // 3. Reset attempts if lockout duration has passed
        if ($otpRecord->locked_until && $otpRecord->locked_until->isPast()) {
            $otpRecord->attempts = 0;
            $otpRecord->locked_until = null;
        }

        // 4. Generate secure 6-digit OTP
        $otp = sprintf("%06d", mt_rand(100000, 999999));

        // 5. Save/update OTP
        $otpRecord->otp = Hash::make($otp); // Store hashed for security
        $otpRecord->expires_at = now()->addMinutes(10);
        $otpRecord->save();

        // 6. Send beautifully styled brand email
        \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($email, $otp) {
            $message->to($email)
                ->subject('Verify your email - OTP Verification')
                ->html('
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <style>
                            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #fafafa; padding: 20px; color: #333; }
                            .card { background-color: #fff; border-radius: 12px; padding: 30px; max-width: 500px; margin: 0 auto; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
                            .header { text-align: center; font-size: 24px; font-weight: bold; color: #EF7E16; margin-bottom: 20px; }
                            .otp { font-size: 32px; font-weight: bold; letter-spacing: 5px; text-align: center; color: #EF7E16; margin: 20px 0; padding: 10px; background-color: #F9E5D4; border-radius: 8px; }
                            .footer { text-align: center; font-size: 12px; color: #999; margin-top: 30px; }
                        </style>
                    </head>
                    <body>
                        <div class="card">
                            <div class="header">KainTAU</div>
                            <p>Hello!</p>
                            <p>Thank you for registering with KainTAU.</p>
                            <p>Please use the following 6-digit One Time Password (OTP) to verify your email address and activate your account:</p>
                            <div class="otp">' . $otp . '</div>
                            <p>This verification code will expire in 10 minutes.</p>
                            <p>If you did not request this, please ignore this email.</p>
                            <div class="footer">© 2026 KainTAU. All rights reserved.</div>
                        </div>
                    </body>
                    </html>
                ');
        });

        return true;
    }

    /**
     * Verify Email OTP and Activate Account
     */
    public function verifyEmailOtp(array $data)
    {
        $email = $data['email'];
        $otp = $data['otp'];

        // 1. Fetch OTP record
        $otpRecord = \App\Models\PasswordResetOtp::where('email', $email)->first();
        if (!$otpRecord) {
            throw ValidationException::withMessages([
                'email' => ['No active verification request found.'],
            ]);
        }

        // 2. Check for active lockout
        if ($otpRecord->locked_until && $otpRecord->locked_until->isFuture()) {
            $remainingSeconds = $otpRecord->locked_until->diffInSeconds(now());
            throw ValidationException::withMessages([
                'email' => ['Too many failed attempts. Try again in ' . $remainingSeconds . ' seconds.'],
            ]);
        }

        // 3. Check for expiration
        if ($otpRecord->expires_at && $otpRecord->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'email' => ['This OTP has expired. Please request a new code.'],
            ]);
        }

        // 4. Verify OTP code
        if (!Hash::check($otp, $otpRecord->otp)) {
            // Increment failed attempts
            $otpRecord->attempts += 1;
            
            if ($otpRecord->attempts >= 3) {
                $otpRecord->lockout_phase += 1;
                $otpRecord->attempts = 0; // Reset attempts for next phase
                
                if ($otpRecord->lockout_phase == 1) {
                    $otpRecord->locked_until = now()->addMinute();
                    $otpRecord->save();
                    throw ValidationException::withMessages([
                        'email' => ['Too many failed attempts. You are locked out for 1 minute.'],
                    ]);
                } elseif ($otpRecord->lockout_phase == 2) {
                    $otpRecord->locked_until = now()->addMinutes(2);
                    $otpRecord->save();
                    throw ValidationException::withMessages([
                        'email' => ['Too many failed attempts. You are locked out for 2 minutes.'],
                    ]);
                } else {
                    $otpRecord->locked_until = now()->addHours(24);
                    $otpRecord->save();
                    throw ValidationException::withMessages([
                        'email' => ['Account locked for 24 hours due to multiple failed attempts.'],
                    ]);
                }
            } else {
                $otpRecord->save();
                $remaining = 3 - $otpRecord->attempts;
                throw ValidationException::withMessages([
                    'email' => ['Incorrect OTP. You have ' . $remaining . ' ' . ($remaining == 1 ? 'attempt' : 'attempts') . ' remaining.'],
                ]);
            }
        }

        // OTP is correct! Clear lockout record
        $otpRecord->delete();

        // 5. Mark user email as verified
        $user = User::where('email', $email)->first();
        if ($user) {
            $user->email_verified_at = now();
            $user->save();

            // Log user in and return JWT token
            $token = JWTAuth::fromUser($user);

            return [
                'user' => $user,
                'token' => $token
            ];
        }

        throw ValidationException::withMessages([
            'email' => ['User not found.'],
        ]);
    }

    /**
     * Send Password Reset OTP
     */
    public function sendResetOtp(string $email)
    {
        // 1. Verify user exists
        $user = User::where('email', $email)->first();
        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['We cannot find a user with that email address.'],
            ]);
        }

        // 2. Fetch or create OTP record
        $otpRecord = \App\Models\PasswordResetOtp::firstOrNew(['email' => $email]);

        // 3. Check for active lockout
        if ($otpRecord->locked_until && $otpRecord->locked_until->isFuture()) {
            $remainingSeconds = $otpRecord->locked_until->diffInSeconds(now());
            throw ValidationException::withMessages([
                'email' => ['Too many failed attempts. Try again in ' . $remainingSeconds . ' seconds.'],
            ]);
        }

        // 4. Reset attempts if lockout duration has passed
        if ($otpRecord->locked_until && $otpRecord->locked_until->isPast()) {
            $otpRecord->attempts = 0;
            $otpRecord->locked_until = null;
        }

        // 5. Generate secure 6-digit OTP
        $otp = sprintf("%06d", mt_rand(100000, 999999));

        // 6. Save/update OTP
        $otpRecord->otp = Hash::make($otp); // Store hashed for security
        $otpRecord->expires_at = now()->addMinutes(10);
        $otpRecord->save();

        // 7. Send beautifully styled brand email
        \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($email, $otp) {
            $message->to($email)
                ->subject('Reset your password - OTP Verification')
                ->html('
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <style>
                            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #fafafa; padding: 20px; color: #333; }
                            .card { background-color: #fff; border-radius: 12px; padding: 30px; max-width: 500px; margin: 0 auto; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
                            .header { text-align: center; font-size: 24px; font-weight: bold; color: #EF7E16; margin-bottom: 20px; }
                            .otp { font-size: 32px; font-weight: bold; letter-spacing: 5px; text-align: center; color: #EF7E16; margin: 20px 0; padding: 10px; background-color: #F9E5D4; border-radius: 8px; }
                            .footer { text-align: center; font-size: 12px; color: #999; margin-top: 30px; }
                        </style>
                    </head>
                    <body>
                        <div class="card">
                            <div class="header">KainTAU</div>
                            <p>Hello!</p>
                            <p>You are receiving this email because we received a password reset request for your account.</p>
                            <p>Use the following 6-digit One Time Password (OTP) to reset your password:</p>
                            <div class="otp">' . $otp . '</div>
                            <p>This code will expire in 10 minutes.</p>
                            <p>If you did not request a password reset, no further action is required.</p>
                            <div class="footer">© 2026 KainTAU. All rights reserved.</div>
                        </div>
                    </body>
                    </html>
                ');
        });

        return true;
    }

    /**
     * Verify Password Reset OTP and Generate One-Time Reset Token
     */
    public function verifyResetOtp(array $data)
    {
        $email = $data['email'];
        $otp = $data['otp'];

        // 1. Fetch OTP record
        $otpRecord = \App\Models\PasswordResetOtp::where('email', $email)->first();
        if (!$otpRecord) {
            throw ValidationException::withMessages([
                'email' => ['No active password reset request found.'],
            ]);
        }

        // 2. Check for active lockout
        if ($otpRecord->locked_until && $otpRecord->locked_until->isFuture()) {
            $remainingSeconds = $otpRecord->locked_until->diffInSeconds(now());
            throw ValidationException::withMessages([
                'email' => ['Too many failed attempts. Try again in ' . $remainingSeconds . ' seconds.'],
            ]);
        }

        // 3. Check for expiration
        if ($otpRecord->expires_at && $otpRecord->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'email' => ['This OTP has expired. Please request a new code.'],
            ]);
        }

        // 4. Verify OTP code
        if (!Hash::check($otp, $otpRecord->otp)) {
            $otpRecord->attempts += 1;
            
            if ($otpRecord->attempts >= 3) {
                $otpRecord->lockout_phase += 1;
                $otpRecord->attempts = 0;
                
                if ($otpRecord->lockout_phase == 1) {
                    $otpRecord->locked_until = now()->addMinute();
                    $otpRecord->save();
                    throw ValidationException::withMessages([
                        'email' => ['Too many failed attempts. You are locked out for 1 minute.'],
                    ]);
                } elseif ($otpRecord->lockout_phase == 2) {
                    $otpRecord->locked_until = now()->addMinutes(2);
                    $otpRecord->save();
                    throw ValidationException::withMessages([
                        'email' => ['Too many failed attempts. You are locked out for 2 minutes.'],
                    ]);
                } else {
                    $otpRecord->locked_until = now()->addHours(24);
                    $otpRecord->save();
                    throw ValidationException::withMessages([
                        'email' => ['Account locked for 24 hours due to multiple failed attempts.'],
                    ]);
                }
            } else {
                $otpRecord->save();
                $remaining = 3 - $otpRecord->attempts;
                throw ValidationException::withMessages([
                    'email' => ['Incorrect OTP. You have ' . $remaining . ' ' . ($remaining == 1 ? 'attempt' : 'attempts') . ' remaining.'],
                ]);
            }
        }

        // OTP is correct! Clear lockout and delete code record
        $otpRecord->delete();

        // 5. Generate secure one-time password reset token
        $token = \Illuminate\Support\Str::random(60);

        // Store inside password_reset_tokens
        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($token),
                'created_at' => now()
            ]
        );

        return [
            'reset_token' => $token
        ];
    }

    /**
     * Reset Password using Verified One-Time Token
     */
    public function resetPasswordWithToken(array $credentials)
    {
        $email = $credentials['email'];
        $token = $credentials['reset_token'];
        $password = $credentials['password'];

        // 1. Fetch token record
        $tokenRecord = \Illuminate\Support\Facades\DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$tokenRecord) {
            throw ValidationException::withMessages([
                'email' => ['Invalid or expired password reset token.'],
            ]);
        }

        // Check if token is older than 10 minutes
        if (now()->diffInMinutes(\Carbon\Carbon::parse($tokenRecord->created_at)) > 10) {
            \Illuminate\Support\Facades\DB::table('password_reset_tokens')->where('email', $email)->delete();
            throw ValidationException::withMessages([
                'email' => ['Your password reset token has expired. Please verify your OTP again.'],
            ]);
        }

        // Verify token matches hashed token in DB
        if (!Hash::check($token, $tokenRecord->token)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid password reset token.'],
            ]);
        }

        // 2. Fetch User and update password
        $user = User::where('email', $email)->first();
        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['User not found.'],
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($password)
        ])->setRememberToken(\Illuminate\Support\Str::random(60));
        $user->save();

        // 3. Delete reset token so it can never be reused (single-use constraint)
        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->where('email', $email)->delete();

        // Fire standard reset event
        event(new \Illuminate\Auth\Events\PasswordReset($user));

        return true;
    }
}
