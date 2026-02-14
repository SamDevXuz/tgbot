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
        'file_type',
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
        if (!empty($this->attributes['file_type'])) {
            return $this->attributes['file_type'];
        }
        // Fallback for old data
        return str_starts_with($this->file_id, 'B') ? 'video' : 'photo';
    }
}
