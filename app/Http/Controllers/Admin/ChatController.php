<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class ChatController extends Controller
{
    /**
     * ==========================================
     * 0. HIỂN THỊ GIAO DIỆN CHAT ADMIN
     * ==========================================
     */
    public function index()
    {
        if (View::exists('admin.chat')) {
            return view('admin.chat');
        } elseif (View::exists('admin.messages')) {
            return view('admin.messages');
        }
        return view('admin.portal');
    }

    /**
     * ==========================================
     * 1. DÀNH CHO KHÁCH HÀNG (USER)
     * ==========================================
     */

    /**
     * Lấy danh sách tin nhắn của khách hàng đang đăng nhập
     */
    public function getUserMessages()
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['error' => 'Chưa đăng nhập'], 401);
        }

        $messages = ChatMessage::where(function ($q) use ($userId) {
            $q->where('sender_id', $userId)
              ->orWhere('receiver_id', $userId);
        })->orderBy('created_at', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'messages' => $messages
        ]);
    }

    /**
     * Khách hàng gửi tin nhắn cho Admin
     */
    public function sendUserMessage(Request $request)
    {
        $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ], [
            'message.required' => 'Nội dung tin nhắn không được để trống.',
            'message.max'      => 'Tin nhắn không được vượt quá 2000 ký tự.',
        ]);

        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['error' => 'Chưa đăng nhập'], 401);
        }

        $admin = User::where('role', 'admin')->first();

        $chat = ChatMessage::create([
            'sender_id'   => $userId,
            'receiver_id' => $admin ? $admin->id : null,
            'content'     => $request->message,
            'is_read'     => false,
        ]);

        return response()->json(['status' => 'success', 'data' => $chat]);
    }

    /**
     * Kiểm tra số tin nhắn chưa đọc của khách hàng
     */
    public function checkUserUnread()
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['unread_count' => 0]);
        }

        $count = ChatMessage::where('receiver_id', $userId)
            ->where('is_read', false)
            ->count();

        return response()->json(['unread_count' => $count]);
    }

    /**
     * Đánh dấu tất cả tin nhắn gửi tới khách hàng là ĐÃ ĐỌC
     */
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

    /**
     * Admin lấy danh sách khách hàng đã nhắn tin (kèm tin nhắn mới nhất & số tin chưa đọc)
     */
    public function getAdminUsers()
    {
        $adminId = Auth::id();

        $senders   = ChatMessage::whereNotNull('sender_id')->where('sender_id', '!=', $adminId)->pluck('sender_id');
        $receivers = ChatMessage::whereNotNull('receiver_id')->where('receiver_id', '!=', $adminId)->pluck('receiver_id');
        $userIds   = $senders->merge($receivers)->unique();

        $users = User::whereIn('id', $userIds)
            ->select('id', 'name', 'email')
            ->get()
            ->map(function ($user) use ($adminId) {
                // Đếm số tin nhắn chưa đọc
                $user->unread_count = ChatMessage::where('sender_id', $user->id)
                    ->where(function ($q) use ($adminId) {
                        $q->where('receiver_id', $adminId)->orWhereNull('receiver_id');
                    })
                    ->where('is_read', false)
                    ->count();

                // Lấy nội dung và thời gian tin nhắn mới nhất
                $latestMsg = ChatMessage::where(function ($q) use ($user) {
                    $q->where('sender_id', $user->id)
                      ->orWhere('receiver_id', $user->id);
                })->latest()->first();

                $user->latest_message = $latestMsg ? $latestMsg->content : '';
                $user->last_activity  = $latestMsg ? $latestMsg->created_at : null;

                return $user;
            })
            // Sắp xếp khách hàng mới nhắn tin lên đầu
            ->sortByDesc(function ($user) {
                return $user->last_activity ? $user->last_activity->timestamp : 0;
            })
            ->values();

        return response()->json($users);
    }

    /**
     * Admin xem tin nhắn riêng với 1 User & tự động đánh dấu ĐÃ ĐỌC
     */
    public function getAdminMessages($userId)
    {
        $adminId = Auth::id();

        // Đánh dấu tất cả tin nhắn từ User này gửi cho Admin thành ĐÃ ĐỌC
        ChatMessage::where('sender_id', $userId)
            ->where(function ($q) use ($adminId) {
                $q->where('receiver_id', $adminId)->orWhereNull('receiver_id');
            })
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = ChatMessage::where(function ($outer) use ($userId, $adminId) {
            $outer->where(function ($q) use ($userId, $adminId) {
                $q->where('sender_id', $userId)
                  ->where(function ($sub) use ($adminId) {
                      $sub->where('receiver_id', $adminId)->orWhereNull('receiver_id');
                  });
            })->orWhere(function ($q) use ($userId, $adminId) {
                $q->where('sender_id', $adminId)
                  ->where('receiver_id', $userId);
            });
        })->orderBy('created_at', 'asc')->get();

        return response()->json($messages);
    }

    /**
     * Admin phản hồi tin nhắn cho 1 User
     */
    public function sendAdminMessage(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'message' => ['required', 'string', 'max:2000'],
        ], [
            'user_id.required' => 'Không xác định được người nhận.',
            'user_id.exists'   => 'Tài khoản người dùng không tồn tại.',
            'message.required' => 'Nội dung tin nhắn không được để trống.',
            'message.max'      => 'Tin nhắn không được vượt quá 2000 ký tự.',
        ]);

        $chat = ChatMessage::create([
            'sender_id'   => Auth::id(),
            'receiver_id' => $request->user_id,
            'content'     => $request->message,
            'is_read'     => false,
        ]);

        return response()->json(['status' => 'success', 'data' => $chat]);
    }

    /**
     * Đếm tổng số tin nhắn chưa đọc của tất cả khách hàng gửi đến Admin
     */
    public function checkAdminUnread()
    {
        $adminId = Auth::id();

        $count = ChatMessage::where(function ($q) use ($adminId) {
            $q->where('sender_id', '!=', $adminId)
              ->orWhereNull('sender_id');
        })
        ->where('is_read', false)
        ->count();

        return response()->json(['unread_count' => $count]);
    }
}