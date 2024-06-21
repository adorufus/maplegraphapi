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
        try {
            // Refresh the token
            $client->refreshToken($this->refresh_token);
            $newAccessToken = $client->getAccessToken();

            // Debugging: Print the response
            print_r($newAccessToken);

            // Check if the response is valid
            if (isset($newAccessToken['access_token']) && isset($newAccessToken['expires_in'])) {
                $this->access_token = $newAccessToken['access_token'];
                $this->expires_at = Carbon::now()->addSeconds($newAccessToken['expires_in']);

                if (isset($newAccessToken['refresh_token'])) {
                    $this->refresh_token = $newAccessToken['refresh_token'];
                }

                $this->save();
            } else {
                // Handle the case where the access token is not returned
                throw new \Exception('Failed to refresh the access token.');
            }
        } catch (\Exception $e) {
            // Handle exceptions
            echo 'Error: ' . $e->getMessage();
        }
    }
}
