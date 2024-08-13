<?php

namespace App\Console\Commands;

use App\Models\GraphCalculatedData;
use App\Models\TokenStorage;
use DateTimeZone;
use Exception;
use Google\Cloud\Core\Timestamp;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Promise\Utils;
use Google\Cloud\Firestore\FirestoreClient;
use Carbon\Carbon;

class GraphCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'graph:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Loop Graph Media Daily every minutes';
    protected FirestoreClient $firestore;

    protected $graph_calculated_model;

    public function __construct()
    {
        parent::__construct();
        $this->graph_calculated_model = new GraphCalculatedData;
        $this->firestore = new FirestoreClient([
            'projectId' => 'mapleapp-7c7ab'
        ]);
    }

    public function segmentedMetrics($data): array
    {
        $hastagRegex = '/#(Rewind|TrickRoom|Wander|BreakingBadNews|CAN!|DIXI|Unscene|PlayRoom|ASMR|JikaKukuhMenjadi|CAN)\b/i';
        $captions = [];
        $allHastags = [];


        foreach ($data as $d) {
            $captions[] = $d['caption'];
        }

        foreach ($captions as $caption) {
            preg_match_all($hastagRegex, $caption, $matches);

            if(!empty($matches[0])){
                $allHastags = array_merge($allHastags, $matches[0]);
            }
        }

        $allHastags = array_unique($allHastags);

        print_r($allHastags);

        return $allHastags;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        set_time_limit(3600);

        $output = new \Symfony\Component\Console\Output\ConsoleOutput();


        try {
            $client = new Client();
            $tokenStorage = TokenStorage::get();
            $url = "https://graph.facebook.com/v18.0/17841451302689754/media?limit=200&access_token={$tokenStorage[0]['token']}&pretty=1&fields=id%2Ccaption%2Clike_count%2Ccomments_count%2Cusername%2Cmedia_product_type%2Cmedia_type%2Cowner%2Cpermalink%2Cmedia_url%2Cchildren%7Bmedia_url%7D";

            $client->getAsync($url)->then(function ($response) use ($tokenStorage) {

                $decoded = json_decode($response->getBody(), true);

                $graphData = $decoded['data'];
                // // Check if there is a "next" pagination link
                if (isset($decoded['paging']['next'])) {
                    $nextUrl = $decoded['paging']['next'];
                    $noMoreUrl = false;
                    $count = 0;
                    while ($nextUrl) {

                        try {
                            $nextClient = new Client();
                            $nextClient->getAsync($nextUrl)->then(
                                function ($response) use (&$graphData, &$noMoreUrl, &$nextUrl, &$count) {
                                    $nextResponse = json_decode($response->getBody(), true);

                                    $nextData = $nextResponse['data'];

                                    $graphData = array_merge($graphData, $nextData);

                                    if (isset($nextResponse['paging']['next'])) {
                                        $nextUrl = $nextResponse['paging']['next'];
                                        $count = $count += 1;
                                        echo $count;
                                    } else {
                                        $noMoreUrl = true;
                                    }
                                },
                                function (RequestException $re) use (&$noMoreUrl) {
                                    $noMoreUrl = true;
                                }
                            )->wait();
                        } catch (GuzzleException $ge) {
                            echo $ge->getMessage();
                            break;
                        }

                        if($noMoreUrl) {
                            break;
                        }
                    }
                }

                $this->calculateGraphData($graphData, $tokenStorage[0]['token']);

            }, function (RequestException $re) {
                echo $re->getMessage();
            })->wait();

        } catch (GuzzleException $ge) {
            echo $ge->getMessage();
        }

//        $response = Utils::unwrap($promise);
//
//        var_dump($response);
//
//        if(array_key_exists('error', $response)){
//            echo implode($response);
//        }
//        else {
//
//        }
    }

    protected function calculateGraphData($data, $token)
    {
        $dataLength = count($data);

        $insightData = [];

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

        $baseUrl = 'https://graph.facebook.com/v18.0';

        $metric = 'impressions,reach,video_views,total_interactions,saved,comments,likes';

        print_r('calculating data... please wait...');

//        foreach ($mediaData as $data) {
//            print_r($data);
//            $captions[] = $data["caption"];
//        }

        for ($i = 0; $i < $dataLength; $i++) {
            if ($data[$i]['media_type'] == 'VIDEO') {

                $segments = $this->segmentedMetrics($data);

                var_dump($i);
                $metric = 'reach,total_interactions,comments,ig_reels_avg_watch_time,ig_reels_video_view_total_time,likes,plays,reach,saved,shares';
                $client = new Client();

                try {
                    $this->getInsightData($baseUrl, $data, $i, $metric, $token, $client, $insightData);
                } catch (GuzzleException $ge) {
                    echo $ge->getMessage();
                }

                try {
                    $caption = $data[$i]['caption'];

                    if($caption) {
                        if(str_contains($caption, '#Rewind') || str_contains($caption, '#REWIND')){
                            $this->getInsightData($baseUrl, $data, $i, $metric, $token, $client, $rewindInsightData);

                            echo "rewind";
                        }

                        if(str_contains($caption, '#BreakingBadNews')){
                            $this->getInsightData($baseUrl, $data, $i, $metric, $token, $client, $bbnInsightData);

                            echo "bbn";
                        }

                        if(str_contains($caption, '#JikaKukuhMenjadi')){
                            $this->getInsightData($baseUrl, $data, $i, $metric, $token, $client, $jkmInsightData);

                            echo "jkm";
                        }

                        if(str_contains($caption, '#Dixi') || str_contains($caption, '#DIXI')){
                            $this->getInsightData($baseUrl, $data, $i, $metric, $token, $client, $dixiInsightData);

                            echo "dixi";
                        }

                        if(str_contains($caption, '#Wander') || str_contains($caption, '#wander')){
                            $this->getInsightData($baseUrl, $data, $i, $metric, $token, $client, $wanderInsightData);

                            echo "wander";
                        }

                        if(str_contains($caption, '#ASMR')){
                            $this->getInsightData($baseUrl, $data, $i, $metric, $token, $client, $asmrInsightData);

                            echo "asmr";
                        }

                        if(str_contains($caption, '#TrickRoom') || str_contains($caption, '#Trickroom') || str_contains($caption, '#trickroom')){
                            $this->getInsightData($baseUrl, $data, $i, $metric, $token, $client, $trickroomInsightData);

                            echo "trickroom";
                        }

                        if(str_contains($caption, '#CAN')){
                            $this->getInsightData($baseUrl, $data, $i, $metric, $token, $client, $canInsightData);

                            echo "can";
                        }

                        if(str_contains($caption, '#Unscene') || str_contains($caption, '#UNSCENE')){
                            $this->getInsightData($baseUrl, $data, $i, $metric, $token, $client, $unsceneInsightData);
                            echo "unscene";
                        }

                        if(str_contains($caption, '#Playroom') || str_contains($caption, '#PlayRoom')){
                            $this->getInsightData($baseUrl, $data, $i, $metric, $token, $client, $playroomInsightData);

                            echo "playroom";
                        }

                        if(str_contains($caption, '#BehindTheCurtain')){
                            $this->getInsightData($baseUrl, $data, $i, $metric, $token, $client, $btcInsightData);

                            echo "btc";
                        }
                    }
                } catch (GuzzleException $ge) {
                    echo $ge->getMessage();
                }
            }
        }

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

        $this->calcAndSendToFirebase($insightData, 'data');
        sleep(2);
        $this->calcAndSendToFirebase($compiledSegmentsInsights['rewind'], 'rewind');
        sleep(2);
        $this->calcAndSendToFirebase($compiledSegmentsInsights['bbn'], 'bbn');
        $this->calcAndSendToFirebase($compiledSegmentsInsights['jkm'], 'jika_kukuh_menjadi');
        sleep(2);
        $this->calcAndSendToFirebase($compiledSegmentsInsights['can'], 'can');
        sleep(2);
        $this->calcAndSendToFirebase($compiledSegmentsInsights['asmr'], 'asmr');
        sleep(2);
        $this->calcAndSendToFirebase($compiledSegmentsInsights['dixi'], 'dixi');
        sleep(2);
        $this->calcAndSendToFirebase($compiledSegmentsInsights['wander'], 'wander');
        sleep(2);
        $this->calcAndSendToFirebase($compiledSegmentsInsights['unscene'], 'unscene');
        sleep(2);
        $this->calcAndSendToFirebase($compiledSegmentsInsights['playroom'], 'playroom');
        sleep(2);
        $this->calcAndSendToFirebase($compiledSegmentsInsights['trickroom'], 'trickroom');
        sleep(2);
        $this->calcAndSendToFirebase($compiledSegmentsInsights['btc'], 'btc');


        $collectionRef = $this->firestore->collection('metrics_logs');
        $collectionRef->newDocument()->set([
            'last_logged_at' => new Timestamp(new \DateTime())
        ]);

//        DB::beginTransaction();
//        try {
//
//            $this->graph_calculated_model->create($total);
//
//            DB::commit();
//        } catch (Exception $e) {
//            var_dump($e->getMessage());
//            DB::rollBack();
//        }
    }

    function calcAndSendToFirebase($insightData, $type) {
        $reachSum = 0;
        $total_interactions_sum = 0;
        $commentsSum = 0;
        $ig_reels_avg_watch_time_sum = 0;
        $ig_reels_video_view_total_time_sum = 0;
        $likesSum = 0;
        $playsSum = 0;
        $saved = 0;
        $shares = 0;

        $total = [];


        foreach ($insightData as $insight) {
            $total = [
                'reach' => $reachSum += $insight['data'][0]['values'][0]['value'],
                'total_interactions' => $total_interactions_sum += $insight['data'][1]['values'][0]['value'],
                'comments' => $commentsSum += $insight['data'][2]['values'][0]['value'],
                'ig_reels_avg_watch_time' => $ig_reels_avg_watch_time_sum += $insight['data'][3]['values'][0]['value'],
                'ig_reels_video_view_total_time' => $ig_reels_video_view_total_time_sum += $insight['data'][4]['values'][0]['value'],
                'likes' => $likesSum += $insight['data'][5]['values'][0]['value'],
                'plays' => $playsSum += $insight['data'][6]['values'][0]['value'],
                'saved' => $saved += $insight['data'][7]['values'][0]['value'],
                'shares' => $shares += $insight['data'][8]['values'][0]['value'],
            ];
        }

        $total['media_count'] = count($insightData);

        print_r($total);

        try {
            $collectionRef = $this->firestore->collection('metrics');
            $docRef = $collectionRef->document($type)->collection('metric_data')->document(new Timestamp($this->convertToGmt(new \DateTime("now"))));
            $total['updated_at'] = new Timestamp($this->convertToGmt(new \DateTime("now")));

            $isEndOfMonth = Carbon::now()->isLastOfMonth();

            if($isEndOfMonth) {
                $monthlyRef = $collectionRef->document($type)->collection('monthly_metric_data')->document(new Timestamp($this->convertToGmt(new \DateTime('now'))));

                $monthlyRef->set(
                    $total, [
                        'merge' => true
                    ]
                );
            }

            $docRef->set(
                $total, [
                    'merge' => true
                ]
            );
        } catch (Exception $e) {
            print_r($e->getMessage());
        }
    }

    function convertToGmt($dateTime) {
        $dateTime->setTimezone(new DateTimeZone('Asia/Jakarta'));
        return $dateTime;
    }

    function getInsightData ($baseUrl, $data, $i, $metric, $token, $client, &$insightDataVar) {
        $client->getAsync("$baseUrl/{$data[$i]['id']}/insights?metric={$metric}&access_token={$token}")->then(
            function ($response) use (&$insightDataVar, $data, $i) {

                $decoded = json_decode($response->getBody(), true);

                $insightDataVar[] = [
                    "media_id" => $data[$i]['id'],
                    "data" => $decoded['data']
                ];

            },
            function (RequestException $re) {
                echo $re->getMessage();
            }
        )->wait();;
    }
}
