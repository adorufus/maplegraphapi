<?php

namespace App\Console\Commands;

use App\Models\TiktokToken;
use Carbon\Carbon;
use DateTimeZone;
use Exception;
use Google\Cloud\Core\Timestamp;
use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use Log;

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

        $refreshTokenUrl = 'https://open.tiktokapis.com/v2/oauth/token/';

        $refreshTokenResponse = $httpClient->postAsync($refreshTokenUrl, [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Cache-Control' => 'no-cache'
            ],
            'form_params' => [
                'client_key' => env('TIKTOK_CLIENT_KEY'),
                'client_secret' => env('TIKTOK_CLIENT_SECRET'),
                'grant_type' => 'refresh_token',
                'refresh_token' => $firstIndexTokenModel['refresh_token'],
            ]
        ]);

        try {
            $refreshTokenResponse->then(function ($res) use ($tiktokTokenModel, $httpClient, &$cursor, &$count, &$data, &$hasMore, $firstIndexTokenModel) {
                echo "memek";
                $body = $res->getBody();
                $responseData = json_decode($body, true);
                print_r($responseData);

                $tiktokTokenModel->updateOrCreate(['id' => 1], [
                    'access_token' => $responseData['access_token'],
                    'expires_in' => $responseData['expires_in'],
                    'open_id' => $responseData['open_id'],
                    'refresh_expires_in' => $responseData['refresh_expires_in'],
                    'refresh_token' => $responseData['refresh_token'],
                    'scope' => $responseData['scope'],
                    'token_type' => $responseData['token_type'],
                ]);

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
        
                    // Merge videos data into $data array
                    $data = array_merge($data, $body['data']['videos']);
        
                    $count += 1;
        
                    echo 'array pushed ' . $count . "\n";
                    $hasMore = $body['data']['has_more'];
                    $cursor = $body['data']['cursor'];
        
                    sleep(1);
                } while ($hasMore);
        
        
                $this->calculate($data);
    
            }, function ($ex) {
                print_r($ex->getMessage(), true);
                if ($ex->hasResponse()) {
                    echo $ex->getResponse()->getBody()->getContents();
                }
            })->wait();
        } catch (\Exception $e) {
            Log::error('Request failed: ' . $e->getMessage());
        }

    }

    function calculate($data)
    {
        $combinedData = [
            'view_count' => 0,
            'like_count' => 0,
            'comment_count' => 0,
            'share_count' => 0,
        ];

        $rewindInsightData = [];
        $bbnInsightData = [];
        $jkmInsightData = [];
        $dixiInsightData = [];
        $wanderInsightData = [];
        $asmrInsightData = [];
        $canInsightData = [];
        $trickroomInsightData = [];
        $unsceneInsightData = [];
        $playroomInsightData = [];
        $btcInsightData = [];

        foreach ($data as $item) {
            $caption = $item['title'];

            if (str_contains($caption, '#Rewind') || str_contains($caption, '#REWIND') || str_contains($caption, '#rewind')) {
                $rewindInsightData = array_merge($rewindInsightData, [$item]);

                echo "rewind \n" . count($rewindInsightData);
            }

            if (str_contains($caption, '#BreakingBadNews')) {
                $bbnInsightData = array_merge($bbnInsightData, [$item]);
                echo "bbn \n" . count($bbnInsightData);
            }

            if (str_contains($caption, '#JikaKukuhMenjadi')) {
                $jkmInsightData = array_merge($jkmInsightData, [$item]);

                echo "jkm \n" . count($jkmInsightData);
            }

            if (str_contains($caption, '#Dixi') || str_contains($caption, '#DIXI')) {
                $dixiInsightData = array_merge($dixiInsightData, [$item]);

                echo "dixi \n" . count($dixiInsightData);
            }

            if (str_contains($caption, '#Wander') || str_contains($caption, '#wander')) {
                $wanderInsightData = array_merge($wanderInsightData, [$item]);

                echo "wander \n" . count($wanderInsightData);
            }

            if (str_contains($caption, '#ASMR')) {
                $asmrInsightData = array_merge($asmrInsightData, [$item]);

                echo "asmr \n" . count($asmrInsightData);
            }

            if (str_contains($caption, '#TrickRoom') || str_contains($caption, '#Trickroom') || str_contains($caption, '#trickroom')) {
                $trickroomInsightData = array_merge($trickroomInsightData, [$item]);

                echo "trickroom \n" . count($trickroomInsightData);
            }

            if (str_contains($caption, '#CAN') || str_contains($caption, 'CAN!')) {
                $canInsightData = array_merge($canInsightData, [$item]);

                echo "can \n" . count($canInsightData);
            }

            if (str_contains($caption, '#Unscene') || str_contains($caption, '#UNSCENE') || str_contains($caption, '#unscene')) {
                $unsceneInsightData = array_merge($unsceneInsightData, [$item]);
                echo "unscene \n" . count($unsceneInsightData);
            }

            if (str_contains($caption, '#Playroom') || str_contains($caption, '#PlayRoom')) {
                $playroomInsightData = array_merge($playroomInsightData, [$item]);

                echo "playroom \n" . count($playroomInsightData);
            }

            if (str_contains($caption, '#BehindTheCurtain')) {
                $btcInsightData = array_merge($btcInsightData, [$item]);

                echo "btc \n" . count($btcInsightData);
            }

            // echo json_encode($item) . "\n";
            $views = isset($item['view_count']) ? $item['view_count'] : 0;
            $likes = isset($item['like_count']) ? $item['like_count'] : 0;
            $comments = isset($item['comment_count']) ? $item['comment_count'] : 0;
            $share = isset($item['share_count']) ? $item['share_count'] : 0;

            $combinedData['view_count'] += $views;
            $combinedData['like_count'] += $likes;
            $combinedData['comment_count'] += $comments;
            $combinedData['share_count'] += $share;

            // echo json_encode($combinedData);
        }

        $combinedData["media_count"] = count($rewindInsightData) + count($bbnInsightData) + count($jkmInsightData) + count($dixiInsightData) + count($wanderInsightData) + count($asmrInsightData) + count($trickroomInsightData) + count($canInsightData) + count($unsceneInsightData) + count($playroomInsightData) + count($btcInsightData);


        $compiledSegmentsInsights['rewind'] = $rewindInsightData;
        $compiledSegmentsInsights['bbn'] = $bbnInsightData;
        $compiledSegmentsInsights['jkm'] = $jkmInsightData;
        $compiledSegmentsInsights['dixi'] = $dixiInsightData;
        $compiledSegmentsInsights['wander'] = $wanderInsightData;
        $compiledSegmentsInsights['asmr'] = $asmrInsightData;
        $compiledSegmentsInsights['trickroom'] = $trickroomInsightData;
        $compiledSegmentsInsights['can'] = $canInsightData;
        $compiledSegmentsInsights['unscene'] = $unsceneInsightData;
        $compiledSegmentsInsights['playroom'] = $playroomInsightData;
        $compiledSegmentsInsights['btc'] = $btcInsightData;

        $this->calcAndSendToFirebase($combinedData, 'data');
        $this->calcAndSendToFirebase($compiledSegmentsInsights['rewind'], 'rewind');
        $this->calcAndSendToFirebase($compiledSegmentsInsights['bbn'], 'bbn');
        $this->calcAndSendToFirebase($compiledSegmentsInsights['jkm'], 'jika_kukuh_menjadi');
        $this->calcAndSendToFirebase($compiledSegmentsInsights['can'], 'can');
        $this->calcAndSendToFirebase($compiledSegmentsInsights['asmr'], 'asmr');
        $this->calcAndSendToFirebase($compiledSegmentsInsights['dixi'], 'dixi');
        $this->calcAndSendToFirebase($compiledSegmentsInsights['wander'], 'wander');
        $this->calcAndSendToFirebase($compiledSegmentsInsights['unscene'], 'unscene');
        $this->calcAndSendToFirebase($compiledSegmentsInsights['playroom'], 'playroom');
        $this->calcAndSendToFirebase($compiledSegmentsInsights['trickroom'], 'trickroom');
        $this->calcAndSendToFirebase($compiledSegmentsInsights['btc'], 'btc');
    }

    function convertToGmt($dateTime)
    {
        $dateTime->setTimezone(new DateTimeZone('Asia/Jakarta'));
        return $dateTime;
    }

    function calcAndSendToFirebase($insightData, $type)
    {
        echo 'memek ' . json_encode($insightData) . "\n";


        $viewSum = 0;
        $commentsSum = 0;
        $likes = 0;
        $shares = 0;

        $total = [];

        print_r(count($insightData));


        if ($type != 'data') {
            foreach ($insightData as $insight) {
                $total = [
                    'view' => $viewSum += $insight['view_count'],
                    'comments' => $commentsSum += $insight['comment_count'],
                    'likes' => $likes += $insight['like_count'],
                    'shares' => $shares += $insight['share_count'],
                ];
            }

            $total['media_count'] = count($insightData);
        } else {
            // echo "insight data" . $insightData;
            $total = [
                'view' => $insightData['view_count'],
                'comments' => $insightData['comment_count'],
                'likes' => $insightData['like_count'],
                'shares' => $insightData['share_count'],
                'media_count' => $insightData['media_count']
            ];
        }

        print_r($total);

        try {
            $collectionRef = $this->firestore->collection('tiktok_graph');
            $collectionRef->document($type)->set(["data" => true]);
            $docRef = $collectionRef->document($type)->collection('metric_data')->document(new Timestamp($this->convertToGmt(new \DateTime("now"))));
            $total['updated_at'] = new Timestamp($this->convertToGmt(new \DateTime("now")));

            $isEndOfMonth = Carbon::now()->isLastOfMonth();

            if ($isEndOfMonth) {
                $monthlyRef = $collectionRef->document($type)->collection('monthly_metric_data')->document(new Timestamp($this->convertToGmt(new \DateTime('now'))));

                $monthlyRef->set(
                    $total,
                    [
                        'merge' => true
                    ]
                );
            }

            $docRef->set(
                $total,
                [
                    'merge' => true
                ]
            );
        } catch (Exception $e) {
            print_r($e->getMessage());
        }
    }
}
