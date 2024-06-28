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

        $hasMore = false;

        // echo json_encode($firstIndexTokenModel);

        $url = 'https://open.tiktokapis.com/v2/video/list/?fields=title,like_count,comment_count,share_count,view_count';

        $response = $httpClient->post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $firstIndexTokenModel['access_token'],
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'max_count' => 50
            ]
        ]);

        $body = $response->getBody()->getContents();

        echo $body;

    }
}
