<?php
/**
 * AnimeVote Model
 * Author: SamDevX
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnimeVote extends Model
{
    protected $table = 'anime_votes';

    protected $fillable = [
        'user_id',
        'anime_id',
        'type', // 'like' or 'dislike'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'telegram_id');
    }

    public function anime()
    {
        return $this->belongsTo(Anime::class);
    }
}
