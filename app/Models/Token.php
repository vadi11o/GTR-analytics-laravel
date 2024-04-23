<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Token extends Model
{
    use HasFactory;

    protected $table = 'tokens';

    protected $fillable = [
        'access_token',
        'last_updated',
    ];

    protected $casts = [
        'last_updated' => 'datetime',
    ];

    public $incrementing = true;
    protected $primaryKey = 'id';
    public $timestamps = false;
}
