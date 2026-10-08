<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Các thuộc tính có thể gán hàng loạt (Mass Assignment).
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status', // Bổ sung status để cho phép Mass Assignment khi tạo tài khoản
        'email_verified_at',
    ];

    /**
     * Kiểm tra người dùng có phải Admin hay không.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Kiểm tra người dùng có phải Nhân viên (Staff) hay không.
     */
    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    /**
     * Kiểm tra người dùng có phải Khách hàng hay không.
     */
    public function isCustomer(): bool
    {
        return in_array($this->role, ['customer', 'user']);
    }

    /**
     * Kiểm tra tài khoản có đang bị khóa hay không.
     */
    public function isBlocked(): bool
    {
        return isset($this->status) && (int) $this->status === 0;
    }

    /**
     * Mối quan hệ với Đơn hàng (Orders).
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    /**
     * Mối quan hệ với Tin nhắn Chat (ChatMessage).
     */
    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'sender_id');
    }

    /**
     * Các thuộc tính cần ẩn khi chuyển đổi thành Array/JSON.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Chuyển đổi kiểu dữ liệu (Type casting).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'status'            => 'integer',
        ];
    }
}