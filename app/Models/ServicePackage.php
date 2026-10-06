<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicePackage extends Model
{
    use HasFactory;

    protected $table = 'service_packages';

    // Khai báo các cột được phép gán dữ liệu hàng loạt (Sửa triệt để lỗi MassAssignmentException)
    protected $fillable = [
        'name',
        'price',
        'description',
        'image',
        'is_active',
    ];

    // Ép kiểu dữ liệu chuẩn xác
    protected $casts = [
        'price' => 'decimal:0',
        'is_active' => 'boolean',
    ];

    // Accessor: Tự động định dạng giá tiền chuẩn VND khi gọi ($service->formatted_price)
    public function getFormattedPriceAttribute()
    {
        return number_format($this->price, 0, ',', '.') . 'đ';
    }
}