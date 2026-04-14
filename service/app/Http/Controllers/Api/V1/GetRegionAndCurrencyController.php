<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GetRegionAndCurrencyController extends Controller
{
    /**
     * 获取地区和币种信息
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // 获取所有地区信息
            $regions = Region::select('id', 'region_name', 'region_code')
                ->orderBy('region_name')
                ->get();

            // 获取所有币种信息
            $currencies = Currency::select('id', 'country', 'currency_code', 'currency_symbol', 'exchange_rate')
                ->orderBy('country')
                ->get();

            return response()->json([
                'success' => true,
                'message' => '获取地区和币种信息成功',
                'data' => [
                    'regions' => $regions,
                    'currencies' => $currencies
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '获取数据失败',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}