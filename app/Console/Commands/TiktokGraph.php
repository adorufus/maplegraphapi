<?php

namespace App\Console\Commands;

use App\Models\TiktokToken;
use Google\Cloud\Firestore\FirestoreClient;
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

    protected FirestoreClient $firestore;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get metrics from tiktok api v2';

    public function __construct()
    {
        parent::__construct();
        $this->firestore = new FirestoreClient(
            [
                'projectId' => 'mapleapp-7c7ab'
            ]
        );
    }

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

            if ($cursor != '') {
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

        $this->calculate($data);

    }

    function calculate($data)
    {
        $combinedData = [
            'views' => 0,
            'likes' => 0,
            'comments' => 0,
            'share' => 0,
        ];

        foreach ($data as $item) {
            $views = isset($item['view_count']) ? $item['view_count'] : 0;
            $likes = isset($item['like_count']) ? $item['like_count'] : 0;
            $comments = isset($item['comment_count']) ? $item['comment_count'] : 0;
            $share = isset($item['share_count']) ? $item['share_count'] : 0;

            $combinedData['views'] += $views;
            $combinedData['likes'] += $likes;
            $combinedData['comments'] += $comments;
            $combinedData['share'] += $share;

            echo json_encode($combinedData);
        }
        

        $this->firestore->collection('tiktok_graph')->document('metric_data')->set(
            $combinedData
        );
    }
}
