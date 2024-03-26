<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserTa extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users_ta';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'twitch_id',
        'login',
        'display_name',
        'type',
        'broadcaster_type',
        'description',
        'profile_image_url',
        'offline_image_url',
        'view_count',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
}

