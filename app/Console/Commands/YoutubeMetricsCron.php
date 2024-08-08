<?php

namespace App\Console\Commands;

use App\Models\YoutubeToken;
use Carbon\Carbon;
use Google\Cloud\Core\Exception\GoogleException;
use Google\Cloud\Core\Timestamp;
use Google\Cloud\Firestore\FirestoreClient;
use Google\Service\Exception;
use Google\Service\YouTube;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use DateTimeZone;

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

    /**
     * @throws Exception
     */
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

    function convertToGmt($dateTime)
    {
        $dateTime->setTimezone(new DateTimeZone('Asia/Jakarta'));
        return $dateTime;
    }

    /**
     * Execute the console command.
     *
     *
     */
    public function handle()
    {
        set_time_limit(3600);
        $today = new \DateTime();

        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setAccessType('offline');
        $this->client->addScope(\Google_Service_YouTube::YOUTUBE_READONLY);
        $this->client->setPrompt('consent');

        $service = new \Google_Service_YouTube($this->client);

        try {
            $token = YoutubeToken::firstOrFail();

            if ($token->isExpired()) {
                $token->refreshToken($this->client);
            } else {
                $this->client->setAccessToken($token->access_token);
            }

            $playlists = $this->getAllPlaylistId($this->client);

            $items = [];
            $pageToken = '';
            $statistic = [];
            $firestore = new FirestoreClient([
                'projectId' => 'mapleapp-7c7ab'
            ]);

            $todayForChecking = date('Y-m-d');
            $lastStorageDate = null;
            $trackingDocRef = $firestore->collection('yt_meta')->document('last_storage_date');

            $trackingDoc = $trackingDocRef->snapshot();

            if ($trackingDoc->exists()) {
                $lastStorageTimestamp = $trackingDoc->get('date');

                if ($lastStorageTimestamp instanceof Timestamp) {
                    $lastStorageDateTime = $lastStorageTimestamp->get()->format('Y-m-d');
                    $lastStorageDate = $lastStorageDateTime;
                }
            }
            foreach ($playlists as $playlist) {
                do {
                    $response = $service->playlistItems->listPlaylistItems('snippet', [
                        'playlistId' => $playlist['id'],
                        'maxResults' => 100,
                        'pageToken' => $pageToken
                    ]);

                    $videoIds = [];

                    foreach ($response->getItems() as $item) {
                        $videoIds[] = $item->getSnippet()->getResourceId()->getVideoId();
                    }

                    $items[] = [
                        'playlist_name' => $playlist['title'],
                        'videos_id' => $videoIds
                    ];

                    $pageToken = $response->getNextPageToken();

                } while ($pageToken);
            }

            $combinedData = [
                'views' => 0,
                'likes' => 0,
                'dislikes' => 0,
                'comments' => 0,
                'favorite' => 0,
            ];

            foreach ($items as $item) {
                $chunks = array_chunk($item['videos_id'], 50);

                foreach ($chunks as $chunk) {
                    $response = $service->videos->listVideos('statistics', [
                        'id' => implode(',', $chunk)
                    ]);
                    
                    print_r($statistic, true);

                    if (!isset($statistic[$item['playlist_name']])) {
                        $statistic[$item['playlist_name']] = [
                            'views' => 0,
                            'likes' => 0,
                            'dislikes' => 0,
                            'comments' => 0,
                            'favorite' => 0,
                        ];
                    }

                    foreach ($response->getItems() as $videoStats) {
                        $stats = $videoStats->getStatistics();
                        $views = isset($stats['viewCount']) ? $stats['viewCount'] : 0;
                        $likes = isset($stats['likeCount']) ? $stats['likeCount'] : 0;
                        $dislikes = isset($stats['dislikeCount']) ? $stats['dislikeCount'] : 0;
                        $comments = isset($stats['commentCount']) ? $stats['commentCount'] : 0;
                        $favorite = isset($stats['favoriteCount']) ? $stats['favoriteCount'] : 0;

                        $statistic[$item['playlist_name']]['views'] += $views;
                        $statistic[$item['playlist_name']]['likes'] += $likes;
                        $statistic[$item['playlist_name']]['dislikes'] += $dislikes;
                        $statistic[$item['playlist_name']]['comments'] += $comments;
                        $statistic[$item['playlist_name']]['favorite'] += $favorite;

                        $combinedData['views'] += $views;
                        $combinedData['likes'] += $likes;
                        $combinedData['dislikes'] += $dislikes;
                        $combinedData['comments'] += $comments;
                        $combinedData['favorite'] += $favorite;
                    }
                }
            }


            $ytMetricCollection = $firestore->collection('yt_metrics');
            $todayTimestamp = new Timestamp(new \DateTime());

            $combinedData['updated_at'] = $todayTimestamp;

            $combinedRef = $ytMetricCollection->document('data');

            $combinedRef->set(['updated_at' => $todayTimestamp], ['merge' => true]);

            $combinedRef->collection('metric_data')->document($todayTimestamp)->set(
                $combinedData
            );

            $isEndOfMonth = Carbon::now()->isLastOfMonth();

            if ($isEndOfMonth) {
                $monthlyRef = $combinedRef->collection('monthly_metric_data')->document(new Timestamp($this->convertToGmt(new \DateTime('now'))));

                $monthlyRef->set( 
                    $combinedData,
                    [
                        'merge' => true
                    ]
                );
            }

            foreach ($statistic as $key => $value) {

                $formattedKey = str_replace(' ', '_', $key);
                $docRef = $ytMetricCollection->document(strtolower($formattedKey));

                $docRef->set(['updated_at' => $todayTimestamp]);

                $value['updated_at'] = $todayTimestamp;

                if ($isEndOfMonth) {
                    $monthlyRef = $docRef->collection('monthly_metric_data')->document(new Timestamp($this->convertToGmt(new \DateTime('now'))));

                    $monthlyRef->set(
                        $value,
                        [
                            'merge' => true
                        ]
                    );
                }

                $metricDataCol = $docRef->collection('metric_data')->document($todayTimestamp);
                $metricDataCol->set(
                    $value,
                    [
                        'merge' => true
                    ]
                );
            }

            $trackingDocRef->set([
                'date' => $today
            ]);

            echo json_encode($statistic);
        } catch (ModelNotFoundException $e) {
            $this->error('No tokens found, please reauth the youtube account');

            return 1;
        } catch (GoogleException | Exception $e) {
            $this->error("Exception caught:{$e->getMessage()}");

            return 1;
        }


    }
}
