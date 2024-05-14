<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TopOfTheTop extends Model
{
    use HasFactory;
    protected $table      = 'topOfTheTops';
    protected $primaryKey = 'game_id';
    public $incrementing  = false;
    protected $fillable   = ['game_id','game_name', 'user_name', 'total_videos', 'total_views', 'most_viewed_title', 'most_viewed_views', 'most_viewed_duration', 'most_viewed_created_at', 'ultima_actualizacion'];
    public $timestamps    = false;
}
