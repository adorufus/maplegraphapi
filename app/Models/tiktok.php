<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * TokenStorage
 *
 * @mixin Builder
 */
class tiktok extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'scopes',
        'state',
    ];
}
