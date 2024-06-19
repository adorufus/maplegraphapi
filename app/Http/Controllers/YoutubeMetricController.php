<?php

namespace App\Http\Controllers;

use App\Models\YoutubeToken;
use Carbon\Carbon;
use Google\Cloud\Core\Exception\GoogleException;
use Google\Cloud\Core\Timestamp;
use Google\Cloud\Firestore\CollectionReference;
use Google\Cloud\Firestore\DocumentReference;
use Google\Cloud\Firestore\DocumentSnapshot;
use Google\Service\Exception;
use Google\Service\YouTube;
use Google\Service\YouTubeAnalytics\QueryResponse;
use Google\Type\Date;
use Google_Service_YouTubeAnalytics;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Google\Cloud\Firestore\FirestoreClient;

class YoutubeMetricController extends Controller
{
    protected \Google_Client $client;

    public function __construct()
    {
        $this->client = new \Google_Client();
    }

    function createAuth(Request $req): \Illuminate\Routing\Redirector|\Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse
    {


        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri('http://' . $_SERVER['HTTP_HOST'] . '/api/v1/ytCallback');
        $this->client->setAccessType('offline');
        $this->client->addScope([Google_Service_YouTubeAnalytics::YT_ANALYTICS_READONLY, \Google_Service_YouTube::YOUTUBE_READONLY]);
        $this->client->setPrompt('consent');

        return redirect($this->client->createAuthUrl());
    }

    function getCallback(Request $req)
    {
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri('http://' . $_SERVER['HTTP_HOST'] . '/api/v1/ytCallback');
        $this->client->addScope(Google_Service_YouTubeAnalytics::YT_ANALYTICS_READONLY);

        if ($req->get('code')) {
            $this->client->authenticate($req->get('code'));

            $accessToken = $this->client->getAccessToken();
            $refreshToken = $this->client->getRefreshToken();
            $expiresAt = Carbon::now()->addSeconds($accessToken['expires_in']);

            YoutubeToken::updateOrCreate(
                ['id' => 1],
                [
                    'access_token' => $accessToken['access_token'],
                    'refresh_token' => $refreshToken,
                    'expires_at' => $expiresAt
                ]
            )->save();


//            session_start();
//
//            $_SESSION['access_token'] = json_encode($this->client->getAccessToken());

            if(App::environment('local')) {
                return redirect(env('YT_AUTH_REDIRECT_AFTER_LOGIN_LOCAL'));
            } else {
                return redirect(env('YT_AUTH_REDIRECT_AFTER_LOGIN'));
            }
        }
    }

//    function getAnalyticsData(Request $req)
//    {
//
//        session_start();
//        $accessToken = $_SESSION['access_token'];
//
//        $this->client->setAccessToken($accessToken);
//
//        if ($this->client->isAccessTokenExpired()) {
//            return redirect('http://' . $_SERVER['HTTP_HOST'] . '/api/v1/createYtAuth');
//        }
//
//        $playlists = $this->getAllPlaylistId($this->client);
//
//        $ytAnal = new Google_Service_YouTubeAnalytics($this->client);
//
//        $reports = [];
//
//        $today = new \DateTime();
//
//        foreach ($playlists as $playlist) {
//
//
//            $queryParams = [
//                'ids' => 'channel==MINE',
//                'startDate' => '2020-01-01',
//                'endDate' => $today->format('Y-m-d'),
//                'metrics' => implode(',', ['views,estimatedMinutesWatched,playlistStarts,playlistViews,viewsPerPlaylistStart']),
//                'filters' => 'playlist==' . $playlist['id'],
//            ];
//
//            try {
//                $response = $ytAnal->reports->query($queryParams);
//
//
//                $column_headers = $response->getColumnHeaders();
//                $data_rows = $response->getRows();
//
////                echo json_encode($data_rows[0]) . PHP_EOL;
//
//                $data = [];
//
//                for($i = 0; $i < count($column_headers); $i++) {
//
//                   $data[$column_headers[$i]->getName()] = $data_rows[0][$i];
//                }
//
//                $reports[] = [
//                    'title' => $playlist['title'],
//                    'data' => $data
//                ];
//
//            } catch (Exception $e) {
//                echo $e->getMessage();
//                echo $e->getCode();
//            }
//        }
//
//        return response()->json($reports);
//
//
//    }

    /**
     * @throws Exception
     * @throws GoogleException
     */
    function ytFooBar() {

//        set_time_limit(3600);
//
//        session_start();
//        $accessToken = $_SESSION['access_token'];
//        $today = new \DateTime();
//
//        $this->client->setAccessToken($accessToken);
//
//        if ($this->client->isAccessTokenExpired()) {
//            $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
//            $_SESSION['access_token'] = $this->client->getAccessToken();
//        }
//
//        $playlists = $this->getAllPlaylistId($this->client);
//
//        $service = new \Google_Service_YouTube($this->client);
//
//
//        $items = [];
//        $pageToken = '';
//        $statistic = [];
//        $firestore = new FirestoreClient([
//            'projectId' => 'mapleapp-7c7ab'
//        ]);
//
//        $todayForChecking = date('Y-m-d');
//        $lastStorageDate = null;
//        $trackingDocRef = $firestore->collection('yt_meta')->document('last_storage_date');
//
//        $trackingDoc = $trackingDocRef->snapshot();
//
//        if($trackingDoc->exists()) {
//            $lastStorageTimestamp = $trackingDoc->get('date');
//
//            if($lastStorageTimestamp instanceof Timestamp) {
//                $lastStorageDateTime = $lastStorageTimestamp->get()->format('Y-m-d');
//                $lastStorageDate = $lastStorageDateTime;
//            }
//        }
//
//        if($lastStorageDate !== $todayForChecking) {
//            foreach($playlists as $playlist) {
//                do {
//                    $response = $service->playlistItems->listPlaylistItems('snippet', [
//                        'playlistId' => $playlist['id'],
//                        'maxResults' => 100,
//                        'pageToken' => $pageToken
//                    ]);
//
//                    $videoIds = [];
//
//                    foreach ($response->getItems() as $item) {
//                        $videoIds[] = $item->getSnippet()->getResourceId()->getVideoId();
//                    }
//
//                    $items[] = [
//                        'playlist_name' => $playlist['title'],
//                        'videos_id' => $videoIds
//                    ];
//
//                    $pageToken = $response->getNextPageToken();
//
//                } while ($pageToken);
//            }
//
//            $combinedData = ['views' => 0,
//                'likes' => 0,
//                'dislikes' => 0,
//                'comments' => 0,
//                'favorite' => 0,];
//
//            foreach($items as $item) {
//                $chunks = array_chunk($item['videos_id'], 50);
//
//                foreach($chunks as $chunk) {
//                    $response = $service->videos->listVideos('statistics', [
//                        'id' => implode(',', $chunk)
//                    ]);
//
//                    if (!isset($statistic[$item['playlist_name']])) {
//                        $statistic[$item['playlist_name']] = [
//                            'views' => 0,
//                            'likes' => 0,
//                            'dislikes' => 0,
//                            'comments' => 0,
//                            'favorite' => 0,
//                        ];
//                    }
//
//                    foreach ($response->getItems() as $videoStats) {
//                        $stats = $videoStats->getStatistics();
//                        $views = isset($stats['viewCount']) ? $stats['viewCount'] : 0;
//                        $likes = isset($stats['likeCount']) ? $stats['likeCount'] : 0;
//                        $dislikes = isset($stats['dislikeCount']) ? $stats['dislikeCount'] : 0;
//                        $comments = isset($stats['commentCount']) ? $stats['commentCount'] : 0;
//                        $favorite = isset($stats['favoriteCount']) ? $stats['favoriteCount'] : 0;
//
//                        $statistic[$item['playlist_name']]['views'] += $views;
//                        $statistic[$item['playlist_name']]['likes'] += $likes;
//                        $statistic[$item['playlist_name']]['dislikes'] += $dislikes;
//                        $statistic[$item['playlist_name']]['comments'] += $comments;
//                        $statistic[$item['playlist_name']]['favorite'] += $favorite;
//
//                        $combinedData['views'] += $views;
//                        $combinedData['likes'] += $likes;
//                        $combinedData['dislikes'] += $dislikes;
//                        $combinedData['comments'] += $comments;
//                        $combinedData['favorite'] += $favorite;
//                    }
//                }
//            }
//
//
//
//            $ytMetricCollection = $firestore->collection('yt_metrics');
//            $todayTimestamp = new Timestamp(new \DateTime());
//
//            $combinedData['updated_at'] = $todayTimestamp;
//
//            $combinedRef = $ytMetricCollection->document('data');
//
//            $combinedRef->set(['updated_at' => $todayTimestamp], ['merge' => true]);
//
//            $combinedRef->collection('metric_data')->document($todayTimestamp)->set(
//                $combinedData
//            );
//
//            foreach ($statistic as $key => $value) {
//
//                $formattedKey = str_replace(' ', '_', $key);
//                $docRef = $ytMetricCollection->document(strtolower($formattedKey));
//
//                $docRef->set(['updated_at' => $todayTimestamp]);
//
//                $value['updated_at'] = $todayTimestamp;
//
//                $metricDataCol = $docRef->collection('metric_data')->document($todayTimestamp);
//                $metricDataCol->set(
//                    $value,
//                    [
//                        'merge' => true
//                    ]
//                );
//            }
//
//            $trackingDocRef->set([
//                'date' => $today
//            ]);
//
//            return response()->json($statistic);
//        } else {
//            return response()->json([
//                'message' => 'Data already stored for today :)'
//            ]);
//        }

//        $ytAnal = new Google_Service_YouTubeAnalytics($this->client);
//
//        $queryParams = [
//            'ids' => 'contentOwner==UCIDaRh0v88PwigLSYZSEX3g',
//            'startDate' => '2020-01-01',
//            'endDate' => $today->format('Y-m-d'),
//            "dimensions" => "day",
//            'sort' => 'day',
//            'metrics' => implode(',', ['views,estimatedMinutesWatched,averageViewDuration,averageTimeInPlaylist,playlistAverageViewDuration,playlistEstimatedMinutesWatched,playlistSaves,playlistStarts,playlistViews,viewsPerPlaylistStart']),
//            'filters' => 'playlist==PLaESrtegONJ8u7dZA4QKBxstX49hJzYoK'
//        ];
//
//        try {
//            $response = $ytAnal->reports->query($queryParams);
//            return response()->json($response);
//        } catch (\Google_Exception $ex) {
//            echo $ex->getMessage();
//        }


    }

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
}
