<?php

namespace App\Http\Controllers;

use Google\Cloud\Core\Timestamp;
use Google\Service\Exception;
use Google\Service\YouTube;
use Google\Service\YouTubeAnalytics\QueryResponse;
use Google\Type\Date;
use Google_Service_YouTubeAnalytics;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Google\Cloud\Firestore\FirestoreClient;

class YoutubeMetricController extends Controller
{
    protected \Google_Client $client;
    protected FirestoreClient $firestore;

    public function __construct()
    {
        $this->client = new \Google_Client();
//        $this->firestore = new FirestoreClient();
    }

    function createAuth(Request $req)
    {


        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri('http://' . $_SERVER['HTTP_HOST'] . '/api/v1/ytCallback');
        $this->client->addScope([Google_Service_YouTubeAnalytics::YT_ANALYTICS_READONLY, \Google_Service_YouTube::YOUTUBE_READONLY]);

        return redirect($this->client->createAuthUrl());
    }

    function getCallback(Request $req): void
    {

        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri('http://' . $_SERVER['HTTP_HOST'] . '/api/v1/ytCallback');
        $this->client->addScope(Google_Service_YouTubeAnalytics::YT_ANALYTICS_READONLY);

        if ($req->get('code')) {
            $this->client->authenticate($req->get('code'));

            session_start();

            $_SESSION['access_token'] = json_encode($this->client->getAccessToken());

            echo 'Auth Success' . PHP_EOL;
        }
    }

    function getAnalyticsData(Request $req)
    {

        session_start();
        $accessToken = $_SESSION['access_token'];

        $this->client->setAccessToken($accessToken);

        if ($this->client->isAccessTokenExpired()) {
            return redirect('http://' . $_SERVER['HTTP_HOST'] . '/api/v1/createYtAuth');
        }

        $playlists = $this->getAllPlaylistId($this->client);

        $ytAnal = new Google_Service_YouTubeAnalytics($this->client);

        $reports = [];

        $today = new \DateTime();

        foreach ($playlists as $playlist) {


            $queryParams = [
                'ids' => 'channel==MINE',
                'startDate' => '2020-01-01',
                'endDate' => $today->format('Y-m-d'),
                'metrics' => implode(',', ['views,estimatedMinutesWatched,playlistStarts,playlistViews,viewsPerPlaylistStart']),
                'filters' => 'playlist==' . $playlist['id'],
            ];

            try {
                $response = $ytAnal->reports->query($queryParams);


                $column_headers = $response->getColumnHeaders();
                $data_rows = $response->getRows();

//                echo json_encode($data_rows[0]) . PHP_EOL;

                $data = [];

                for($i = 0; $i < count($column_headers); $i++) {

                   $data[$column_headers[$i]->getName()] = $data_rows[0][$i];
                }

                $reports[] = [
                    'title' => $playlist['title'],
                    'data' => $data
                ];

            } catch (Exception $e) {
                echo $e->getMessage();
                echo $e->getCode();
            }
        }

        return response()->json($reports);


    }

    function ytFooBar() {

        session_start();
        $accessToken = $_SESSION['access_token'];
        $today = new \DateTime();

        $this->client->setAccessToken($accessToken);

        if ($this->client->isAccessTokenExpired()) {
            return redirect('http://' . $_SERVER['HTTP_HOST'] . '/api/v1/createYtAuth');
        }

        $playlists = $this->getAllPlaylistId($this->client);

        $service = new \Google_Service_YouTube($this->client);


        $items = [];
        $pageToken = '';
        $statistic = [];

        foreach($playlists as $playlist) {
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



        foreach($items as $item) {
            $chunks = array_chunk($item['videos_id'], 50);

            foreach($chunks as $chunk) {
                $response = $service->videos->listVideos('statistics', [
                   'id' => implode(',', $chunk)
                ]);


                foreach ($response->getItems() as $stats) {
                    $statistic[$item['playlist_name']] = $stats['statistics'];
                }
            }
        }

        $firestore = new FirestoreClient();

        $ytMetricCollection = $firestore->collection('yt_metrics');
        $today = new Timestamp(new \DateTime());

        foreach ($statistic as $key => $value) {
            $docRef = $ytMetricCollection->document($key);

            $metricDataCol = $docRef->collection('metric_data')->document($today);
            $metricDataCol->set(
                $value,
                [
                    'merge' => true
                ]
            );
        }

        return response()->json($statistic);

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
