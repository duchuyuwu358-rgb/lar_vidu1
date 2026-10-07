<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ServicePackage extends Model
{
    use HasFactory;

    protected $table = 'service_packages';

    // Khai báo các cột được phép gán dữ liệu hàng loạt (Tránh lỗi MassAssignmentException)
    protected $fillable = [
        'name',
        'price',
        'description',
        'image',
        'is_active',
    ];

    // Ép kiểu dữ liệu chuẩn xác
    protected $casts = [
        'price' => 'integer',
        'is_active' => 'boolean',
    ];

    // Tự động đính kèm các thuộc tính ảo khi xuất dữ liệu dạng Array/JSON
    protected $appends = [
        'formatted_price',
        'image_url',
    ];

    /**
     * Accessor: Tự động định dạng giá tiền VND ($service->formatted_price)
     */
    public function getFormattedPriceAttribute()
    {
        return number_format($this->price, 0, ',', '.') . 'đ';
    }

    /**
     * Accessor: Tự động chuẩn hóa đường dẫn ảnh hiển thị ($service->image_url)
     */
    public function getImageUrlAttribute()
    {
        if (empty($this->image)) {
            // Ảnh mặc định khi dịch vụ chưa có hình ảnh
            return 'https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=600&q=80';
        }

        // Nếu là đường dẫn URL tuyệt đối từ bên ngoài
        if (Str::startsWith($this->image, ['http://', 'https://', 'data:image/'])) {
            return $this->image;
        }

        // Nếu là đường dẫn lưu trong bộ nhớ local/storage
        return asset('storage/' . ltrim($this->image, '/'));
    }

    /**
     * Scope: Lọc gói dịch vụ đang hoạt động (Hiển thị storefront)
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Tìm kiếm gói dịch vụ theo tên
     */
    public function scopeSearch($query, $keyword)
    {
        if ($keyword) {
            return $query->where('name', 'like', '%' . trim($keyword) . '%');
        }
        return $query;
    }
}