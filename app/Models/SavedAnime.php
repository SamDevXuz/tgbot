<?php
/**
 * Saved Anime Model (Favorites)
 * Author: SamDevX
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedAnime extends Model
{
    protected $table = 'saved_animes';

    protected $fillable = [
        'user_id',
        'anime_id',
    ];

    public function anime()
    {
        return $this->belongsTo(Anime::class);
    }
}
