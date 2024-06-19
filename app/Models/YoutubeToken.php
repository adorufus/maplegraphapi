<?php

namespace App\Models;

use Google_Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * TokenStorage
 *
 * @mixin Builder
 */
class YoutubeToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'access_token',
        'refresh_token',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function isExpired()
    {
        return $this->expires_at->lt(Carbon::now());
    }
//
    public function refreshToken(Google_Client $client)
    {
        $client->refreshToken($this->refresh_token);
        $newAccessToken = $client->getAccessToken();

        $this->access_token = $newAccessToken['access_token'];
        $this->expires_at = Carbon::now()->addSeconds($newAccessToken['expires_in']);
        $this->save();
    }
}
