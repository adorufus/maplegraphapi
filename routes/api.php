<?php

use App\Http\Controllers\YoutubeMetricController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InstagramGraphApiController;
use App\Http\Controllers\GetMetricsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('v1')->group(function () {
    Route::post('/uploadIgAuthToken', [InstagramGraphApiController::class, 'uploadIgAuthToken']);
    Route::get('/getIgMedia', [InstagramGraphApiController::class, 'getIgMedia']);
    Route::get('/getMetrics', [GetMetricsController::class, 'getMetrics']);
    Route::get('/ytCallback', [YoutubeMetricController::class, 'getCallback']);
    Route::get('/createYtAuth', [YoutubeMetricController::class, 'createAuth']);
    Route::get('/getYtAnalytics', [YoutubeMetricController::class, 'getAnalyticsData']);
})->middleware(['web']);
