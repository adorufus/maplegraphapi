<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * TokenStorage
 *
 * @mixin Builder
 */
class TiktokToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'access_token',
        'expires_in',
        'open_id',
        'refresh_expires_in',
        'refresh_token',
        'scope',
        'token_type'
    ];
    
}
