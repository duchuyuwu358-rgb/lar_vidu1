<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hood extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'model',
        'description',
        'category_id',
        'price',
        'power',
        'dimensions',
        'color',
        'manufacturer',
        'material',
        'warranty_months',
        'type',
        'is_active',
        'stock_quantity',
        'image',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Tạo đường dẫn URL đầy đủ trỏ thẳng vào storage/hoods/
     */
    public function getImageUrlAttribute(): string
    {
        $rawImage = $this->attributes['image'] ?? null;

        if (!$rawImage) {
            return 'https://via.placeholder.com/400x300?text=No+Image';
        }

        // Lấy chính xác tên file (loại bỏ mọi đường dẫn cũ như uploads/ hay hoods/)
        $filename = basename($rawImage);

        return asset('storage/hoods/' . $filename);
    }

    /**
     * Scope lọc theo trạng thái sản phẩm
     */
    public function scopeByStatus($query, $status)
    {
        if ($status === 'selling') {
            return $query->where('is_active', true)->where('stock_quantity', '>', 0);
        } elseif ($status === 'importing') {
            return $query->where('is_active', true)->where('stock_quantity', '<=', 0);
        } elseif ($status === 'sold_out') {
            return $query->where('is_active', false);
        }
        return $query;
    }
}