<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static where(string $string, String $userName)
 * @method static create(array $userData)
 */
class UserAnalytics extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users_analytics';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'password',
        'streamers'
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

}
