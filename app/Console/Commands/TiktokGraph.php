<?php

namespace App\Console\Commands;

use App\Models\TiktokToken;
use Illuminate\Console\Command;
use GuzzleHttp\Client;

class TiktokGraph extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tiktok:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get metrics from tiktok api v2';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $tiktokTokenModel = new TiktokToken;
        $httpClient = new Client();
        $firstIndexTokenModel = $tiktokTokenModel->first()->toArray();
        
        echo json_encode($firstIndexTokenModel);

        $url = 'https://open-api.tiktok.com/video/list/';

        $httpClient->postAsync($url, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'form_params' => [
                'access_token' => $firstIndexTokenModel['access_token'],
                'fields' => ['id', 'title', 'like_count', 'comment_count', 'share_count', 'view_count']
            ]
        ])->then(function ($response) {
            echo json_decode($response->getBody());
        });

    }
}
