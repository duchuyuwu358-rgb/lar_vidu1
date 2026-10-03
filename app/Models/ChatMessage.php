<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;

    // Khớp chính xác với Schema::create('chat_messages') trong migration
    protected $table = 'chat_messages';

    // Khớp các cột trong migration
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'content',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    // Mối quan hệ tới bảng users
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}