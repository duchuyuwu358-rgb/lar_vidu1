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
     * Tự động lọc đường dẫn ảnh trong DB: ép mọi đường dẫn cũ (dính uploads/) về hoods/
     */
    public function getImageAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // Lấy tên file gốc (loại bỏ tiền tố uploads/ hay hoods/ cũ)
        $filename = basename($value);

        return 'hoods/' . $filename;
    }

    /**
     * Trả về đường dẫn URL đầy đủ tới thư mục hoods
     */
    public function getImageUrlAttribute()
    {
        $rawImage = $this->getRawOriginal('image');
        if (!$rawImage) {
            return asset('images/no-image.png');
        }

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