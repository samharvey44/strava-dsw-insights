<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function stravaConnection(): HasOne
    {
        return $this->hasOne(StravaConnection::class, 'user_id');
    }

    public function gear(): HasMany
    {
        return $this->hasMany(Gear::class, 'user_id');
    }

    public function hasActiveStravaConnection(): bool
    {
        return $this->stravaConnection()->where('active', true)->exists();
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'admin' => 'boolean',
        ];
    }
}
