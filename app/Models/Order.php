<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_price',
        'payment_method',
        'payment_status',
        'status',
        'momo_transaction_id',
        'transaction_id',
        'ghn_order_code',
        'address',
        'phone',
        'name',
        'email',
    ];

    /**
     * Bảng ánh xạ trạng thái đơn hàng đầy đủ
     */
    public const STATUSES = [
        'pending' => [
            'label' => 'Chờ xử lý',
            'badge' => 'bg-warning text-dark',
            'icon'  => 'fas fa-clock',
        ],
        'pending_payment' => [
            'label' => 'Chờ thanh toán',
            'badge' => 'bg-warning text-dark',
            'icon'  => 'fas fa-clock',
        ],
        'paid' => [
            'label' => 'Đã thanh toán',
            'badge' => 'bg-success',
            'icon'  => 'fas fa-check-circle',
        ],
        'processing' => [
            'label' => 'Đang xử lý (Chờ lấy)',
            'badge' => 'bg-info text-white',
            'icon'  => 'fas fa-box-open',
        ],
        'shipping' => [
            'label' => 'Đang giao hàng',
            'badge' => 'bg-primary',
            'icon'  => 'fas fa-shipping-fast',
        ],
        'completed' => [
            'label' => 'Hoàn thành',
            'badge' => 'bg-success',
            'icon'  => 'fas fa-check-double',
        ],
        'cancelled' => [
            'label' => 'Đã hủy / Thất bại',
            'badge' => 'bg-danger',
            'icon'  => 'fas fa-times-circle',
        ],
        'failed' => [
            'label' => 'Đã hủy / Thất bại',
            'badge' => 'bg-danger',
            'icon'  => 'fas fa-times-circle',
        ],
    ];

    public function getOrderCodeAttribute()
    {
        return 'ORD-' . str_pad($this->id, 5, '0', STR_PAD_LEFT);
    }

    public function getStatusLabelAttribute()
    {
        $status = (string) $this->status;
        if (isset(self::STATUSES[$status])) {
            return self::STATUSES[$status]['label'];
        }

        switch ($status) {
            case '0': return 'Chờ xử lý';
            case '1': return 'Đang xử lý';
            case '2': return 'Hoàn thành';
            case '3': return 'Đã hủy / Thất bại';
            default: return 'Chờ xác nhận (' . $status . ')';
        }
    }

    public function getStatusBadgeAttribute()
    {
        $status = (string) $this->status;
        if (isset(self::STATUSES[$status])) {
            return self::STATUSES[$status]['badge'];
        }

        switch ($status) {
            case '0': return 'bg-warning text-dark';
            case '1': return 'bg-info text-white';
            case '2': return 'bg-success';
            case '3': return 'bg-danger';
            default: return 'bg-secondary';
        }
    }

    public function getStatusIconAttribute()
    {
        $status = (string) $this->status;
        if (isset(self::STATUSES[$status])) {
            return self::STATUSES[$status]['icon'];
        }

        return 'fas fa-info-circle';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction::class, 'order_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}