<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
