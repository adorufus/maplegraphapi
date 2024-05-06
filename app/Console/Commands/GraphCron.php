<?php

namespace App\Console\Commands;

use App\Models\TokenStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

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

        $url = "https://graph.facebook.com/v18.0/17841451302689754/media?limit=50&access_token={$tokenStorage[0]['token']}&pretty=1&fields=id%2Ccaption%2Clike_count%2Ccomments_count%2Cusername%2Cmedia_product_type%2Cmedia_type%2Cowner%2Cpermalink%2Cmedia_url%2Cchildren%7Bmedia_url%7D";

        $response = Http::get($url);

        if($response->failed()) {
            var_dump($response->body());

            return response()->json(['error']);

        }

        $graphData = $response->json()['data'];

        $nextUrl = $response->json()['paging']['next'];

        // Check if there is a "next" pagination link
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

                    sleep(3);
                } else {
                    print_r($nextResponse->status());
                    info($nextUrl);
                    break;
                }
            }
        }
    }
}
