<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $orderCode;
    public $orderSequence;

    public function __construct(Order $order)
    {
        $this->order = $order;

        // 1. Định dạng Mã đơn chuẩn ORD-00025
        $this->orderCode = $order->order_number ?? $order->code ?? ('ORD-' . str_pad($order->id, 5, '0', STR_PAD_LEFT));

        // 2. Tính số thứ tự đơn hàng của khách
        $query = Order::query();
        if ($order->user_id) {
            $query->where('user_id', $order->user_id);
        } elseif ($order->email) {
            $query->where('email', $order->email);
        } else {
            $query->where('phone', $order->phone);
        }

        $this->orderSequence = $query->where('id', '<=', $order->id)->count();
    }

    public function build()
    {
        return $this->subject("Cập nhật đơn hàng {$this->orderCode} - XFAN Store")
                    ->view('emails.order_status_updated');
    }
}