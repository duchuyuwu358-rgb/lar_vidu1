<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
     * Các trường được phép gán dữ liệu hàng loạt (Mass Assignment)
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'total_price',
        'payment_method',
        'payment_status',
        'status',
        'momo_transaction_id',
        'transaction_id',     // Bổ sung để tương thích lưu mã giao dịch chung
        'ghn_order_code',     // Mã vận đơn GHN
        'address',
        'phone',
        'name',
        'email',
    ];

    /**
     * Danh sách định nghĩa trạng thái đơn hàng chuẩn cho hệ thống
     */
    public const STATUSES = [
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
    ];

    /**
     * Accessor: Tự động hiển thị Mã đơn dạng ORD-00023
     */
    public function getOrderCodeAttribute()
    {
        return 'ORD-' . str_pad($this->id, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Accessor: Tên nhãn hiển thị trạng thái
     */
    public function getStatusLabelAttribute()
    {
        return self::STATUSES[$this->status]['label'] ?? 'Không xác định';
    }

    /**
     * Accessor: Class màu sắc badge Bootstrap
     */
    public function getStatusBadgeAttribute()
    {
        return self::STATUSES[$this->status]['badge'] ?? 'bg-secondary';
    }

    /**
     * Accessor: Class biểu tượng FontAwesome
     */
    public function getStatusIconAttribute()
    {
        return self::STATUSES[$this->status]['icon'] ?? 'fas fa-info-circle';
    }

    /**
     * Quan hệ với bảng User (Khách hàng đặt)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Quan hệ với chi tiết lịch sử giao dịch thanh toán
     */
    public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction::class, 'order_id');
    }

    /**
     * Quan hệ với chi tiết đơn hàng OrderItem
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Tương thích mã nguồn gọi $order->items
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}