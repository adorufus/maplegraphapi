<?php

namespace App\Http\Controllers;

use App\Models\GraphCalculatedData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GetMetricsController extends Controller
{
    protected GraphCalculatedData $graphCalculatedData;


    public function __construct()
    {
//        parent::__construct();
        $this->graphCalculatedData = new GraphCalculatedData();
    }

    public function getMetrics(Request $request): JsonResponse {
        DB::beginTransaction();

        try {

            $data = $this->graphCalculatedData->get();

            DB::commit();

            return response()->json(
                [
                    'status' => 'success',
                    'data' => $data
                ]
            );


        } catch (\Exception $e) {
            vardump($e->getMessage());

            DB::rollBack();

            return response()->json([
                'status' => 'failed',
                'reason' => 'failed to get calculated metrics',
                'message' => $e->getMessage()
            ], $e->getCode());
        }
    }
}
