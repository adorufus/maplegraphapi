<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\TokenStorage;

class InstagramGraphApiController extends Controller
{
    public function uploadIgAuthToken(Request $request) {

        $body = $request->collect();

        
        if($body->has('auth_token')) {
            if(TokenStorage::exists()){
                TokenStorage::whereId(1)->update([
                    'token' => $body['auth_token']
                ]);
            } else {
                TokenStorage::create([
                    'token' => $body['auth_token']
                ]);
            }

            return ['message' => 'Token Uploaded'];
        } else {
            return response()->json(['message' => 'Token Auth is required'], 400);
        }
    }

    public function getIgMedia(Request $request) {

        $tokenStorage = TokenStorage::get();

        $response = Http::get('https://google.com');

        $data = [
            'status' => 'success',
            'message' => 'here is your media lists',
            'data' => '[media list]',
        ];

        return response()->json($data, 200);
    }
}
