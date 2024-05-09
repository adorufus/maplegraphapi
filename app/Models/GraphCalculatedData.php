<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GraphCalculatedData extends Model
{
    use HasFactory;

    protected $fillable = [
        'reach',
        'total_interactions',
        'comments',
        'ig_reels_avg_watch_time',
        'ig_reels_video_view_total_time',
        'likes',
        'plays',
        'saved',
        'shares',
    ];

    protected $cast = [
        'reach' => 'integer',
        'total_interactions' => 'integer',
        'comments' => 'integer',
        'ig_reels_avg_watch_time' => 'integer',
        'ig_reels_video_view_total_time' => 'integer',
        'likes' => 'integer',
        'plays' => 'integer',
        'saved' => 'integer',
        'shares' => 'integer',
    ];
}
