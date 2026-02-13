<?php
/**
 * Channel Model (For join requests / forced subs)
 * Author: SamDevX
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    protected $table = 'channels';

    protected $fillable = [
        'channel_id',
        'type',
        'link',
        'required_members',
        'current_members',
    ];
}
