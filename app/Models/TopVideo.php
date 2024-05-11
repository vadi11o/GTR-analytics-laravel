<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TopVideo extends Model
{
    use HasFactory;
    protected $table      = 'topVideos';
    protected $primaryKey = 'video_id';
    public $incrementing  = false;
    protected $fillable   = ['video_id','game_id', 'title', 'views', 'user_name', 'duration', 'created_at'];
    public $timestamps    = false;
}
