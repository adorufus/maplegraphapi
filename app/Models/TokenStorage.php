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
class TokenStorage extends Model
{
    use HasFactory;

    protected $fillable = [
        'token'
    ];
}
