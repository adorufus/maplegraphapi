<?php

namespace App\Console\Commands;

use Google\Cloud\Core\Timestamp;
use Google\Cloud\Firestore\FirestoreClient;
use Google\Service\YouTube;
use Illuminate\Console\Command;

class YoutubeMetricsCron extends Command
{

    protected \Google_Client $client;

    public function __construct()
    {
        parent::__construct();
//        session_start();
        $this->client = new \Google_Client();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'youtube:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    function getAllPlaylistId($client): array
    {
        $yt = new YouTube($client);

        $queryParams = [
            'channelId' => 'UCQrHEwJkM32uz0x384RB2MQ'
        ];

        $nextPageToken = '';

        $response = $yt->playlists->listPlaylists('id,snippet', [
            'mine' => true,
            'maxResults' => 50,
            'pageToken' => $nextPageToken,
        ]);

        $playlistIds = [];

        foreach ($response->getItems() as $item) {
            $playlistIds[] = [
                'id' => $item->getId(),
                'title' => $item->getSnippet()->getTitle()
            ];
        }

        // You can uncomment this line if you want to print the IDs
//        print_r($playlistIds);

        return $playlistIds;
    }

    /**
     * Execute the console command.
     *
     *
     */
    public function handle()
    {
        $firestore = new FirestoreClient([
            'projectId' => 'mapleapp-7c7ab'
        ]);

        $todayForChecker = date('Y-m-d');

        $trackingDocRef = $firestore->collection('yt_meta')->document('last_storage_date');
        $trackingDoc = $trackingDocRef->snapshot();

        $lastStorageDate = null;
        if($trackingDoc->exists()) {
            $lastStorageDate = $trackingDoc->get('date');
        }

        if($lastStorageDate !== $todayForChecker) {
            $ytMetricCollection = $firestore->collection('yt_metrics');
            $today = new Timestamp(new \DateTime());

            $docRef = $ytMetricCollection->document('test');

            $metricDataCol = $docRef->collection('metric_data')->document($today);
            $metricDataCol->set(
                ["test" => 'test'],
                [
                    'merge' => true
                ]
            );


        }
    }
}
