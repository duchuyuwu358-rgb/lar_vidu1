<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Tự động thêm attribute display_name khi convert sang JSON/Array
    protected $appends = ['display_name'];

    /**
     * Quan hệ ngược về Đơn hàng
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Quan hệ tới Hút mùi / Máy (Hood)
     */
    public function hood()
    {
        return $this->belongsTo(Hood::class, 'hood_id')->withDefault(function ($hood, $orderItem) {
            $hood->name  = $orderItem->product_name ?? $orderItem->name ?? 'Sản phẩm';
            $hood->price = $orderItem->price ?? 0;
        });
    }

    /**
     * Quan hệ tới Dịch vụ (Tự động nhận diện Service / ServicePackage trong DB)
     */
    public function service()
    {
        $serviceClass = class_exists(\App\Models\Service::class)
            ? \App\Models\Service::class
            : (class_exists(\App\Models\ServicePackage::class) ? \App\Models\ServicePackage::class : Hood::class);

        return $this->belongsTo($serviceClass, 'service_id')->withDefault(function ($service, $orderItem) use ($serviceClass) {
            // Tự động tìm tên gói dịch vụ mới nhất từ CSDL
            $realService = class_exists($serviceClass) ? $serviceClass::first() : null;
            $serviceName = $realService->name 
                ?? $realService->title 
                ?? $orderItem->service_name 
                ?? $orderItem->name 
                ?? 'Vệ sinh';

            $service->name  = $serviceName;
            $service->title = $serviceName;
            $service->price = $orderItem->price ?? 250000;
        });
    }

    /**
     * Quan hệ tới Sản phẩm (Tự động nhận diện Product / Hood)
     */
    public function product()
    {
        $productClass = class_exists(\App\Models\Product::class)
            ? \App\Models\Product::class
            : Hood::class;

        return $this->belongsTo($productClass, 'product_id')->withDefault(function ($product, $orderItem) {
            $product->name  = $orderItem->product_name ?? $orderItem->name ?? 'Sản phẩm';
            $product->price = $orderItem->price ?? 0;
        });
    }

    /**
     * Accessor: Tên hiển thị ưu tiên theo Dịch vụ -> Hút mùi -> Sản phẩm
     * Gọi trong View: $item->display_name
     */
    public function getDisplayNameAttribute()
    {
        return $this->service?->name 
            ?? $this->service?->title 
            ?? $this->hood?->name 
            ?? $this->product?->name 
            ?? $this->service_name 
            ?? $this->product_name 
            ?? $this->name 
            ?? 'Vệ sinh';
    }
}