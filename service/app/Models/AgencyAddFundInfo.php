<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AgencyAddFundInfo extends Model
{
    use HasFactory;

    /**
     * 关联的数据表
     *
     * @var string
     */
    protected $table = 'agency_add_fund_info';

    /**
     * 可批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'content',
        'images',
        'agency_id'
    ];

    /**
     * 类型转换
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * 获取处理后的图像数组
     *
     * @return array
     */
    public function getImagesArrayAttribute()
    {
        return !empty($this->images) ? json_decode($this->images, true) : [];
    }

    /**
     * 获取与该记录关联的钱包支付
     */
    public function walletPayment()
    {
        return $this->belongsTo(WalletPayment::class, 'agency_id', 'agency_id');
    }

    /**
     * 获取特定agency_id的最新版本记录
     *
     * @param string $agencyId
     * @return self|null
     */
    public static function getLatestVersion($agencyId)
    {
        return self::where('agency_id', $agencyId)
            ->latest()
            ->first();
    }

    /**
     * 获取特定agency_id的所有版本历史
     *
     * @param string $agencyId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getVersionHistory($agencyId)
    {
        return self::where('agency_id', $agencyId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}