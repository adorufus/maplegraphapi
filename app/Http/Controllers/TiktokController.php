<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TiktokController extends Controller
{
    function auth(Request $request) {

        $client_key = env('TIKTOK_CLIENT_KEY');
        $client_key_sandbox = env('TIKTOK_CLIENT_KEY_SANDBOX');

        $csrfState = substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyz', 10)), 0, 32);

        $cookie = cookie('csrfState', $csrfState, minutes: 60);

        $redirUri = 'https://api.webwebapa.cloud/api/v1/tiktok-callback';
        $encodedUri = urlencode($redirUri);

        $url = 'https://www.tiktok.com/v2/auth/authorize/';
        $url .= "?client_key=$client_key_sandbox";
        $url .= "&scope=user.info.basic";
        $url .= "&response_type=code";
        $url .= "&redirect_uri=$encodedUri";
        $url .= "&state=$csrfState";

        return redirect()->away($url)->cookie($cookie);
    }

    function callback(Request $request) {
        // Retrieve the 'code' parameter from the URL
        $code = $request->query('code');

        // Optionally, retrieve other parameters like 'scopes' and 'state'
        $scopes = $request->query('scopes');
        $state = $request->query('state');

        // Use the retrieved parameters as needed
        // For example, you can return them or process them further
        return response()->json([
            'code' => $code,
            'scopes' => $scopes,
            'state' => $state,
        ]);
    }
}
