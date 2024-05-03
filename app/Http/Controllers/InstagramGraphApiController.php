<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\JsonResponse;
use App\Models\TokenStorage;

class InstagramGraphApiController extends Controller
{
    public function uploadIgAuthToken(Request $request): array|JsonResponse
    {

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

    public function getIgMedia(Request $request): JsonResponse
    {

        $tokenStorage = TokenStorage::get();

        $url = "https://graph.facebook.com/v18.0/17841451302689754/media?limit=25&access_token={$tokenStorage[0]['token']}&pretty=1&fields=id%2Ccaption%2Clike_count%2Ccomments_count%2Cusername%2Cmedia_product_type%2Cmedia_type%2Cowner%2Cpermalink%2Cmedia_url%2Cchildren%7Bmedia_url%7D";

        $response = Http::get($url);

        $data = [
            'status' => 'success',
            'message' => 'here is your media lists',
            'data' => $response->json()['data'],
        ];

        return response()->json($data, 200);
    }
}
