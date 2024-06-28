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
        $data = [];
        $count = 0;
        $cursor = '';

        $bodyData = [
            'max_count' => 20
        ];

        // echo json_encode($firstIndexTokenModel);

        do {
            $url = 'https://open.tiktokapis.com/v2/video/list/?fields=title,like_count,comment_count,share_count,view_count';

            if($cursor != '') {
                $bodyData['cursor'] = $cursor;
            }

            

            $response = $httpClient->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $firstIndexTokenModel['access_token'],
                    'Content-Type' => 'application/json'
                ],
                'json' => $bodyData
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            array_push($data, $body['data']['videos']);

            $count += 1;

            echo 'array pushed ' . $count . "\n";
            $hasMore = $body['data']['has_more'];
            $cursor = $body['data']['cursor'];

            sleep(1);

        } while ($hasMore);

        echo 'data: ' . json_encode($data);

    }
}
