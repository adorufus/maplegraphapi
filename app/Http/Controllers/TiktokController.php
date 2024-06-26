<?php

namespace App\Http\Controllers;

use App\Models\tiktok;
use App\Models\TiktokToken;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TiktokController extends Controller
{

    protected string $client_key;
    protected string $client_key_sandbox;
    protected string $client_secret;
    protected string $client_secret_sandbox;

    public function __construct()
    {
        $this->client_key = env('TIKTOK_CLIENT_KEY');
        $this->client_key_sandbox = env('TIKTOK_CLIENT_KEY_SANDBOX');
        $this->client_secret = env('TIKTOK_CLIENT_SECRET');
        $this->client_secret_sandbox = env('TIKTOK_CLIENT_SECRET_SANDBOX');
    }

    function auth(Request $request)
    {


        $csrfState = substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyz', 10)), 0, 32);

        $cookie = cookie('csrfState', $csrfState, minutes: 60);

        $redirUri = 'https://api.webwebapa.cloud/api/v1/tiktok-callback';
        $encodedUri = urlencode($redirUri);

        $url = 'https://www.tiktok.com/v2/auth/authorize/';
        $url .= "?client_key=$this->client_key";
        $url .= "&scope=user.info.basic,video.list";
        $url .= "&response_type=code";
        $url .= "&redirect_uri=$encodedUri";
        $url .= "&state=$csrfState";

        return redirect()->away($url)->cookie($cookie);
    }

    function callback(Request $request)
    {
        // Retrieve the 'code' parameter from the URL
        $code = $request->query('code');

        // Optionally, retrieve other parameters like 'scopes' and 'state'
        $scopes = $request->query('scopes');
        $state = $request->query('state');

        $tiktokModel = new tiktok;

        // Use the retrieved parameters as needed
        // For example, you can return them or process them further

        $tiktokModel->updateOrCreate(
            ['id' => 1],
            ['code' => $code, 'scopes' => $scopes, 'state' => $state]
        );

        return redirect('https://api.webwebapa.cloud/api/v1/accept-user-access-token');
    }

    function acceptAccessToken(Request $request)
    {

        $tiktokTokenModel = new TiktokToken;
        $tiktokModel = new tiktok;

        $tiktokData = $tiktokModel->first()->toArray();
        $redirUri = 'https://api.webwebapa.cloud/api/v1/tiktok-callback';

        echo json_encode($tiktokData);

        $guzzleClient = new Client();

        $data = [
            'client_key' => $this->client_key,
            'client_secret' => $this->client_secret,
            'code' => $tiktokData['code'],
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirUri,
        ];

        try {
            // Send the POST request
            $response = $guzzleClient->post('https://open.tiktokapis.com/v2/oauth/token/', [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Cache-Control' => 'no-cache',
                ],
                'form_params' => $data,
            ]);

            // Get the response body
            $body = $response->getBody()->getContents();
            $responseData = json_decode($body, true);
            
            $tiktokTokenModel->updateOrCreate(['id' => 1], [
                'access_token' => $responseData['access_token'],
                'expires_in' => $responseData['expires_in'],
                'open_id' => $responseData['open_id'],
                'refresh_expires_in' => $responseData['refresh_expires_in'],
                'refresh_token' => $responseData['refresh_token'],
                'scope' => $responseData['scope'],
                'token_type' => $responseData['token_type'],
            ]);

            return response()->json($responseData);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Request failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
