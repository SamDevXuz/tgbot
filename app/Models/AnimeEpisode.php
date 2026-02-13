<?php
/**
 * Anime Episode Model
 * Author: SamDevX
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnimeEpisode extends Model
{
    protected $table = 'anime_episodes';

    protected $fillable = [
        'anime_id',
        'file_id',
        'episode_number',
    ];

    public function anime()
    {
        return $this->belongsTo(Anime::class);
    }
}
