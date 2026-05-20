<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetOtp extends Model
{
    protected $table = 'password_reset_otps';

    protected $fillable = [
        'email',
        'otp',
        'attempts',
        'lockout_phase',
        'locked_until',
        'expires_at',
    ];

    protected $casts = [
        'locked_until' => 'datetime',
        'expires_at' => 'datetime',
    ];
}
