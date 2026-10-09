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
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'email_verified_at',
        'google_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'status'            => 'integer',
        ];
    }

    /**
     * Kiểm tra tài khoản đã xác minh hay chưa (Tạo bởi Admin / Đăng ký Google / Click Mail xác minh / Quyền Admin, Staff)
     */
    public function isVerified(): bool
    {
        return !empty($this->email_verified_at) 
            || !empty($this->google_id) 
            || in_array($this->role, ['admin', 'staff']);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isCustomer(): bool
    {
        return in_array($this->role, ['customer', 'user']);
    }

    public function isBlocked(): bool
    {
        return isset($this->status) && (int) $this->status === 0;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'sender_id');
    }
}