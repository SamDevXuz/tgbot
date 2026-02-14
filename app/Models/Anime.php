<?php
/**
 * Anime Model
 * Author: SamDevX
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Anime extends Model
{
    protected $table = 'animes';

    protected $fillable = [
        'name',
        'file_id',
        'episodes_count',
        'country',
        'language',
        'year',
        'genre',
        'views',
        'likes',
        'dislikes',
    ];

    public function episodes()
    {
        return $this->hasMany(AnimeEpisode::class);
    }

    public function votes()
    {
        return $this->hasMany(AnimeVote::class);
    }

    public function getMediaTypeAttribute()
    {
        return str_starts_with($this->file_id, 'B') ? 'video' : 'photo';
    }
}
