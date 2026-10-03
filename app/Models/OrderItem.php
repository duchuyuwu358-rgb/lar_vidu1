<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Tự động lấy quan hệ Hood dựa trên cột hood_id hoặc product_id có trong DB
     */
    public function hood()
    {
        $foreignKey = isset($this->attributes['hood_id']) ? 'hood_id' : 'product_id';
        
        return $this->belongsTo(Hood::class, $foreignKey)->withDefault([
            'name'  => $this->attributes['product_name'] ?? $this->attributes['name'] ?? 'Sản phẩm mẫu',
            'price' => $this->attributes['price'] ?? 0,
        ]);
    }

    /**
     * Tự động lấy quan hệ Product (Alias cho hood)
     */
    public function product()
    {
        $foreignKey = isset($this->attributes['product_id']) ? 'product_id' : 'hood_id';

        return $this->belongsTo(Hood::class, $foreignKey)->withDefault([
            'name'  => $this->attributes['product_name'] ?? $this->attributes['name'] ?? 'Sản phẩm mẫu',
            'price' => $this->attributes['price'] ?? 0,
        ]);
    }
}