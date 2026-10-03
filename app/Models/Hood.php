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