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

        foreach ($data as $item) {
            $caption = $item['title'];

            if (str_contains($caption, '#Rewind') || str_contains($caption, '#REWIND') || str_contains($caption, '#rewind')) {
                $rewindInsightData = array_merge($rewindInsightData, [$item]);

                echo "rewind \n";
            }

            if (str_contains($caption, '#BreakingBadNews')) {
                $bbnInsightData = array_merge($bbnInsightData, [$item]);
                echo "bbn \n";
            }

            if (str_contains($caption, '#JikaKukuhMenjadi')) {
                $jkmInsightData = array_merge($jkmInsightData, [$item]);

                echo "jkm \n";
            }

            if (str_contains($caption, '#Dixi') || str_contains($caption, '#DIXI')) {
                $dixiInsightData = array_merge($dixiInsightData, [$item]);

                echo "dixi \n";
            }

            if (str_contains($caption, '#Wander') || str_contains($caption, '#wander')) {
                $wanderInsightData = array_merge($wanderInsightData, [$item]);

                echo "wander \n";
            }

            if (str_contains($caption, '#ASMR')) {
                $asmrInsightData = array_merge($asmrInsightData, [$item]);

                echo "asmr \n";
            }

            if (str_contains($caption, '#TrickRoom') || str_contains($caption, '#Trickroom') || str_contains($caption, '#trickroom')) {
                $trickroomInsightData = array_merge($trickroomInsightData, [$item]);

                echo "trickroom \n";
            }

            if (str_contains($caption, '#CAN')) {
                $canInsightData = array_merge($canInsightData, [$item]);

                echo "can \n";
            }

            if (str_contains($caption, '#Unscene') || str_contains($caption, '#UNSCENE')) {
                $unsceneInsightData = array_merge($unsceneInsightData, [$item]);
                echo "unscene \n";
            }

            if (str_contains($caption, '#Playroom') || str_contains($caption, '#PlayRoom')) {
                $playroomInsightData = array_merge($playroomInsightData, [$item]);

                echo "playroom \n";
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

        echo "kontol " . json_encode($rewindInsightData) . "\n";


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


        if ($type != 'data') {
            foreach ($insightData as $insight) {
                $total = [
                    'view' => $viewSum += $insight['view_count'],
                    'comments' => $commentsSum += $insight['comment_count'],
                    'likes' => $likes += $insight['like_count'],
                    'shares' => $shares += $insight['share_count'],
                ];
            }
        } else {
            $total = [
                'view' => $insightData['view_count'],
                'comments' => $insightData['comment_count'],
                'likes' => $insightData['like_count'],
                'shares' => $insightData['share_count'],
            ];
        }

        $total['media_count'] = count($insightData);

        print_r($total);

        try {
            $collectionRef = $this->firestore->collection('tiktok_graph');
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
