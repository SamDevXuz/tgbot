<?php
/**
 * User Model
 * Author: SamDevX
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';

    protected $fillable = [
        'telegram_id',
        'name',
        'username',
        'balance',
        'balance_bonus',
        'referrals_count',
        'status',
        'ban_status',
        'vip_expires_at',
        'referrer_id',
    ];

    protected $casts = [
        'vip_expires_at' => 'datetime',
    ];

    public function state()
    {
        return $this->hasOne(UserState::class, 'telegram_id', 'telegram_id');
    }

    public function savedAnimes()
    {
        return $this->hasMany(SavedAnime::class, 'user_id', 'telegram_id');
    }

    public function votes()
    {
        return $this->hasMany(AnimeVote::class, 'user_id', 'telegram_id');
    }
}
