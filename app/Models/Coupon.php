<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'min_order_amount',
        'quantity',
        'used_count',
        'hood_id',
        'category_id',
        'start_date',
        'expires_at',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'start_date'       => 'datetime',
        'expires_at'       => 'datetime',
        'value'            => 'float',
        'min_order_amount' => 'float',
        'quantity'         => 'integer',
        'used_count'       => 'integer',
    ];

    /**
     * Liên kết tới Sản phẩm (Hood)
     */
    public function hood(): BelongsTo
    {
        return $this->belongsTo(Hood::class, 'hood_id');
    }

    /**
     * Liên kết tới Danh mục (Category)
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Kiểm tra mã giảm giá có hợp lệ theo Số lượng, Thời gian, Sản phẩm & Danh mục không
     */
    public function isValid(float $totalAmount, ?int $categoryId = null, ?int $hoodId = null): bool
    {
        // 1. Kiểm tra trạng thái kích hoạt
        if (!$this->is_active) {
            return false;
        }

        // 2. Kiểm tra số lượng lượt dùng còn lại
        if ($this->quantity !== null && $this->quantity > 0 && $this->used_count >= $this->quantity) {
            return false;
        }

        // 3. Kiểm tra ngày bắt đầu áp dụng
        if ($this->start_date && now()->lt($this->start_date)) {
            return false;
        }

        // 4. Kiểm tra ngày hết hạn
        if ($this->expires_at && now()->gt($this->expires_at)) {
            return false;
        }

        // 5. Kiểm tra giá trị đơn hàng tối thiểu
        if ($this->min_order_amount && $totalAmount < $this->min_order_amount) {
            return false;
        }

        // 6. Kiểm tra sản phẩm áp dụng cụ thể
        if ($this->hood_id && $hoodId && (int)$this->hood_id !== (int)$hoodId) {
            return false;
        }

        // 7. Kiểm tra danh mục áp dụng
        if ($this->category_id && $categoryId && (int)$this->category_id !== (int)$categoryId) {
            return false;
        }

        return true;
    }

    /**
     * Tính số tiền giảm giá
     */
    public function calculateDiscount(float $totalAmount): float
    {
        if ($this->type === 'percent') {
            $discount = ($totalAmount * $this->value) / 100;
        } else {
            $discount = $this->value;
        }

        return min($discount, $totalAmount);
    }
}