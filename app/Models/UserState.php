<?php
/**
 * User State Model
 * Author: SamDevX
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserState extends Model
{
    protected $table = 'user_states';

    protected $fillable = [
        'telegram_id',
        'step',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];
}
