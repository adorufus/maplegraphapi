<?php

namespace App\Http\Controllers;

use Google\Service\Exception;
use Google\Service\YouTube;
use Google\Service\YouTubeAnalytics\QueryResponse;
use Google_Service_YouTubeAnalytics;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class YoutubeMetricController extends Controller
{

    function createAuth(Request $req)
    {

        $client = new \Google_Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri('http://' . $_SERVER['HTTP_HOST'] . '/api/v1/ytCallback');
        $client->addScope([Google_Service_YouTubeAnalytics::YT_ANALYTICS_READONLY, \Google_Service_YouTube::YOUTUBE_READONLY]);

        return redirect($client->createAuthUrl());
    }

    function getCallback(Request $req): void
    {
        $client = new \Google_Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri('http://' . $_SERVER['HTTP_HOST'] . '/api/v1/ytCallback');
        $client->addScope(Google_Service_YouTubeAnalytics::YT_ANALYTICS_READONLY);

        if ($req->get('code')) {
            $client->authenticate($req->get('code'));

            session_start();

            $_SESSION['access_token'] = json_encode($client->getAccessToken());

            echo 'Auth Success' . PHP_EOL;
        }
    }

    function getAnalyticsData(Request $req)
    {
        $client = new \Google_Client();

        session_start();
        $accessToken = $_SESSION['access_token'];

        $client->setAccessToken($accessToken);

        if ($client->isAccessTokenExpired()) {
            return redirect('http://' . $_SERVER['HTTP_HOST'] . '/api/v1/createYtAuth');
        }

        $playlists = $this->getAllPlaylistId($client);

        $ytAnal = new Google_Service_YouTubeAnalytics($client);

        $reports = [];

        foreach ($playlists as $playlist) {

            $queryParams = [
                'ids' => 'channel==MINE',
                'startDate' => '2022-01-01',
                'endDate' => '2024-01-31',
                'metrics' => implode(',', ['views,estimatedMinutesWatched,playlistStarts,playlistViews,viewsPerPlaylistStart']),
                'filters' => 'playlist==' . $playlist['id'],
            ];

            try {
                $response = $ytAnal->reports->query($queryParams);


                $column_headers = $response->getColumnHeaders();

                foreach ($column_headers as $header) {
                    echo $header->name;
                }
//                $reports[] = [
//                    'title' => $playlist['title'],
//                    'data' => $response
//                ];
            } catch (Exception $e) {
                echo $e->getMessage();
                echo $e->getCode();
            }
        }



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
