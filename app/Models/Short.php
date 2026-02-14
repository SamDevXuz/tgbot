<?php
/**
 * Short Model
 * Author: SamDevX
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Short extends Model
{
    protected $table = 'shorts';

    protected $fillable = [
        'file_id',
        'file_type',
        'name',
        'time',
        'anime_id',
    ];
}
