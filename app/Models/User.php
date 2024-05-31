<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static where(string $string, String $userId)
 * @method static create(array $userData)
 */
class User extends Model
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
        'created_at'
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
}
