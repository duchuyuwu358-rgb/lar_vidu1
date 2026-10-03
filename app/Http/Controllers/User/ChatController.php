<?php

namespace App\Http\Controllers\User; // <-- Đã sửa đúng namespace

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function getMessages()
    {
        $userId = Auth::id();

        // Đánh dấu tin nhắn do Admin gửi cho User này là đã xem
        ChatMessage::where('receiver_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = ChatMessage::where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    public function send(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $admin = User::where('role', 'admin')->first();

        $message = ChatMessage::create([
            'sender_id'   => Auth::id(),
            'receiver_id' => $admin ? $admin->id : null,
            'content'     => $request->message,
            'is_read'     => false,
        ]);

        return response()->json(['status' => 'success', 'data' => $message]);
    }
}