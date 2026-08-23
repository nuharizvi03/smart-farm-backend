<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Farm;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'full_name',
        'mobile',
        'email',
        'password',
        'role',
        'district',
        'province',
        'farm_name',
        'profile_photo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function farms(): HasMany
{
    return $this->hasMany(Farm::class);
}

/**
 * User notifications.
 */
public function notifications(): HasMany
{
    return $this->hasMany(Notification::class);
}

/**
 * User notification preferences.
 */
public function notificationPreference(): HasOne
{
    return $this->hasOne(NotificationPreference::class);
}
}