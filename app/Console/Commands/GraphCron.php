<?php

namespace App\Console\Commands;

use App\Models\GraphCalculatedData;
use App\Models\TokenStorage;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Services\FirebaseService;
use DB;

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

    protected $graph_calculated_model;

    public function __construct() {
        parent::__construct();
        $this->graph_calculated_model = new GraphCalculatedData;
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
        $tokenStorage = TokenStorage::get();

        $url = "https://graph.facebook.com/v18.0/17841451302689754/media?limit=200&access_token={$tokenStorage[0]['token']}&pretty=1&fields=id%2Ccaption%2Clike_count%2Ccomments_count%2Cusername%2Cmedia_product_type%2Cmedia_type%2Cowner%2Cpermalink%2Cmedia_url%2Cchildren%7Bmedia_url%7D";

        $response = Http::get($url);

        if ($response->failed()) {
            var_dump($response->body());

            return response()->json(['error']);

        }

        $graphData = $response->json()['data'];

        $nextUrl = $response->json()['paging']['next'];

        // // Check if there is a "next" pagination link
        if (isset($response->json()['paging']['next'])) {
            $nextUrl = $response->json()['paging']['next'];

            while ($nextUrl) {
                $nextResponse = Http::get($nextUrl);

                if ($nextResponse->successful()) {
                    $nextData = $nextResponse->json()['data'];

                    $graphData = array_merge($graphData, $nextData);

                    if (isset($nextResponse->json()['paging']['next'])) {
                        $nextUrl = $nextResponse->json()['paging']['next'];
                        $output->writeln($nextUrl);
                    } else {
                        $output->writeln('stopped, no more url');
                        break;
                    }
                    
                } else {
                    print_r($nextResponse->status());
                    info($nextUrl);
                    break;
                }
            }
        }

        $this->calculateGraphData($graphData, $tokenStorage[0]['token']);
    }

    protected function calculateGraphData($data, $token)
    {
        $dataLength = count($data);

        $insightData = [];

        $baseUrl = 'https://graph.facebook.com/v18.0';

        $metric = 'impressions,reach,video_views,total_interactions,saved,comments,likes';

        print_r('calculating data... please wait...');

        for ($i = 0; $i < $dataLength; $i++) {
            if ($data[$i]['media_type'] == 'VIDEO') {
                var_dump($i);
                $metric = 'reach,total_interactions,comments,ig_reels_avg_watch_time,ig_reels_video_view_total_time,likes,plays,reach,saved,shares';

                $response = Http::get("$baseUrl/{$data[$i]['id']}/insights?metric={$metric}&access_token={$token}");

                // print_r($response->json());

                if ($response->successful()) {
                    array_push($insightData, [
                        "media_id" => $data[$i]['id'],
                        "data" => $response->json()['data']
                    ]);

                } else if ($response->failed()) {
                    print_r('something went wrong');
                    print_r($response->status());
                }
            }
        }

        print_r($insightData);

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

        print_r($total);

        DB::beginTransaction();
        try {
        
            $this->graph_calculated_model->create($total);

            DB::commit();
        } catch (Exception $e) {
            var_dump($e->getMessage());
            DB::rollBack();
        }
    }
}
