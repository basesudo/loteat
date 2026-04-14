<?php

namespace Database\Seeders;

use App\Models\BusinessSetting;
use Illuminate\Database\Seeder;

class EzyPaySeeder extends Seeder
{
    public function run()
    {
        // 检查是否已存在ezypay配置
        $existingEzypay = BusinessSetting::where('key', 'ezypay')->first();
        
        if (!$existingEzypay) {
            // 创建ezypay支付网关配置
            BusinessSetting::create([
                'key' => 'ezypay',
                'value' => json_encode([
                    'gateway' => 'ezypay',
                    'mode' => 'test',
                    'status' => '1',
                    'partner_code' => '',
                    'credential_code' => ''
                ]),
                'type' => 'payment_config',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // 创建ezypay附加数据配置
            BusinessSetting::create([
                'key' => 'ezypay_additional_data',
                'value' => json_encode([
                    'gateway_title' => 'EzyPay',
                    'gateway_image' => '',
                    'storage' => 'public'
                ]),
                'type' => 'payment_config',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}