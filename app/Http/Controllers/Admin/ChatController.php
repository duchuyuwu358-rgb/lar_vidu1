<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    /**
     * ==========================================
     * 1. DÀNH CHO KHÁCH HÀNG (USER)
     * ==========================================
     */

    public function getUserMessages()
    {
        $userId = Auth::id();
        if (!$userId) return response()->json([]);

        $messages = ChatMessage::where(function ($q) use ($userId) {
            $q->where('sender_id', $userId)->orWhere('receiver_id', $userId);
        })->orderBy('created_at', 'asc')->get();

        return response()->json($messages);
    }

    public function sendUserMessage(Request $request)
    {
        $request->validate(['message' => 'required|string']);
        $userId = Auth::id();
        if (!$userId) return response()->json(['error' => 'Chưa đăng nhập'], 401);

        $admin = User::where('role', 'admin')->first();

        $chat = ChatMessage::create([
            'sender_id'   => $userId,
            'receiver_id' => $admin ? $admin->id : null,
            'content'     => $request->message,
            'is_read'     => false,
        ]);

        return response()->json(['status' => 'success', 'data' => $chat]);
    }

    public function checkUserUnread()
    {
        $userId = Auth::id();
        if (!$userId) return response()->json(['unread_count' => 0]);

        $count = ChatMessage::where('receiver_id', $userId)
            ->where('is_read', false)
            ->count();

        return response()->json(['unread_count' => $count]);
    }

    public function markUserRead()
    {
        $userId = Auth::id();
        if ($userId) {
            ChatMessage::where('receiver_id', $userId)
                ->where('is_read', false)
                ->update(['is_read' => true]);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * ==========================================
     * 2. DÀNH CHO ADMIN
     * ==========================================
     */

    // Admin lấy danh sách khách hàng (kèm số tin nhắn chưa đọc của từng khách)
    public function getAdminUsers()
    {
        $adminId = Auth::id();

        $senders = ChatMessage::where('sender_id', '!=', $adminId)->pluck('sender_id');
        $receivers = ChatMessage::where('receiver_id', '!=', $adminId)->whereNotNull('receiver_id')->pluck('receiver_id');
        $userIds = $senders->merge($receivers)->unique();

        $users = User::whereIn('id', $userIds)->select('id', 'name', 'email')->get()->map(function($user) use ($adminId) {
            $user->unread_count = ChatMessage::where('sender_id', $user->id)
                ->where(function($q) use ($adminId) {
                    $q->where('receiver_id', $adminId)->orWhereNull('receiver_id');
                })
                ->where('is_read', false)
                ->count();
            return $user;
        });

        return response()->json($users);
    }

    // Admin xem tin nhắn riêng với 1 User & tự động đánh dấu ĐÃ ĐỌC
    public function getAdminMessages($userId)
    {
        $adminId = Auth::id();

        // Đánh dấu tất cả tin nhắn từ User này gửi cho Admin thành ĐÃ ĐỌC
        ChatMessage::where('sender_id', $userId)
            ->where(function($q) use ($adminId) {
                $q->where('receiver_id', $adminId)->orWhereNull('receiver_id');
            })
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = ChatMessage::where(function ($q) use ($userId, $adminId) {
            $q->where('sender_id', $userId)
              ->where(function ($sub) use ($adminId) {
                  $sub->where('receiver_id', $adminId)->orWhereNull('receiver_id');
              });
        })->orWhere(function ($q) use ($userId, $adminId) {
            $q->where('sender_id', $adminId)
              ->where('receiver_id', $userId);
        })->orderBy('created_at', 'asc')->get();

        return response()->json($messages);
    }

    // Admin phản hồi tin nhắn
    public function sendAdminMessage(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'message' => 'required|string',
        ]);

        $chat = ChatMessage::create([
            'sender_id'   => Auth::id(),
            'receiver_id' => $request->user_id,
            'content'     => $request->message,
            'is_read'     => false,
        ]);

        return response()->json(['status' => 'success', 'data' => $chat]);
    }

    // Đếm tổng số tin nhắn chưa đọc của tất cả khách hàng gửi đến Admin
    public function checkAdminUnread()
    {
        $adminId = Auth::id();
        $count = ChatMessage::where('sender_id', '!=', $adminId)
            ->where('is_read', false)
            ->count();

        return response()->json(['unread_count' => $count]);
    }
}