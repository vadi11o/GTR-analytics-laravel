<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static truncate()
 * @method static create(array $array)
 * @method static shouldReceive(string $string)
 */
class TopGame extends Model
{
    use HasFactory;

    protected $table      = 'topGames';
    protected $primaryKey = 'game_id';
    public $incrementing  = false;
    protected $fillable   = ['game_id', 'game_name'];

    public $timestamps = false;
}
