<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TiktokController extends Controller
{
    function auth(Request $request) {

        $client_key = env('TIKTOK_CLIENT_KEY');

        $csrfState = substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyz', 10)), 0, 32);

        $redirUri = 'https://mapleapp-7c7ab.web.app/tiktok-callback';
        $encodedUri = urlencode($redirUri);

        $url = 'https://www.tiktok.com/v2/auth/authorize/';
        $url .= "?client_key=$client_key";
        $url .= "&scope=user.info.basic";
        $url .= "&response_type=code";
        $url .= "&redirect_uri=$encodedUri";
        $url .= "&state=$csrfState";

        return redirect()->away($url);
    }

    function callback(Request $request) {
        echo $request->path();
        echo $request->route('code');
    }
}
