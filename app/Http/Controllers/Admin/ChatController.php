<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\Hood;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

class ChatController extends Controller
{
    /**
     * Lấy ID tài khoản Admin/Cửa hàng mặc định
     */
    private function getAdminSenderId()
    {
        $admin = User::where('role', 'admin')->first();
        return $admin ? $admin->id : 2;
    }

    /**
     * Lấy trạng thái chế độ Chat (Bot hay Nhân viên)
     */
    private function getIsAdminMode($userId)
    {
        if (Schema::hasTable('chat_sessions')) {
            $session = \App\Models\ChatSession::where('user_id', $userId)->first();
            if ($session) {
                return (bool) $session->is_admin_chat;
            }
        }
        return (bool) session("is_admin_chat_{$userId}", false);
    }

    /**
     * Cập nhật trạng thái chế độ Chat (Bot hay Nhân viên)
     */
    private function setIsAdminMode($userId, bool $status)
    {
        if (Schema::hasTable('chat_sessions')) {
            \App\Models\ChatSession::updateOrCreate(
                ['user_id' => $userId],
                ['is_admin_chat' => $status]
            );
        }
        session(["is_admin_chat_{$userId}" => $status]);
    }

    /**
     * Giao diện quản lý Chat Admin/Nhân viên
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

        $isAdminMode = $this->getIsAdminMode($userId);

        return response()->json([
            'status'   => 'success',
            'messages' => $messages,
            'is_admin' => $isAdminMode
        ]);
    }

    /**
     * Khách hàng gửi tin nhắn
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

        $text      = trim($request->message);
        $textLower = mb_strtolower($text);
        $adminId   = $this->getAdminSenderId();

        // 1. Thoát khỏi chế độ Nhân viên tư vấn
        if (in_array($textLower, ['exit', '/exit', 'thoát', 'thoat', 'quit'])) {
            $this->setIsAdminMode($userId, false);

            return response()->json([
                'status' => 'success',
                'mode'   => 'bot',
                'reply'  => null
            ]);
        }

        // 2. Kích hoạt chế độ Nhân viên tư vấn khi bấm nút
        if ($textLower === 'connect_staff' || $textLower === 'connect_admin') {
            $this->setIsAdminMode($userId, true);

            return response()->json([
                'status' => 'success',
                'mode'   => 'admin',
                'reply'  => null
            ]);
        }

        // 3. Đang ở chế độ Chat trực tiếp với Nhân viên -> Gửi tin cho Nhân viên, chờ Nhân viên reply
        if ($this->getIsAdminMode($userId)) {
            $userMsg = ChatMessage::create([
                'sender_id'   => $userId,
                'receiver_id' => $adminId,
                'content'     => $text,
                'is_read'     => false,
            ]);

            return response()->json([
                'status' => 'success',
                'mode'   => 'admin',
                'reply'  => null,
                'data'   => $userMsg
            ]);
        }

        // 4. Đang ở chế độ XFAN Bot -> Phản hồi tự động bằng Bot
        $userMsg = ChatMessage::create([
            'sender_id'   => $userId,
            'receiver_id' => $adminId,
            'content'     => $text,
            'is_read'     => false,
        ]);

        $reply = $this->generateSmartBotReply($text);

        ChatMessage::create([
            'sender_id'   => $adminId,
            'receiver_id' => $userId,
            'content'     => $reply,
            'is_read'     => false,
        ]);

        return response()->json([
            'status' => 'success',
            'mode'   => 'bot',
            'reply'  => $reply,
            'data'   => $userMsg
        ]);
    }

    /**
     * Sinh phản hồi tự động thông minh cho Bot
     */
    private function generateSmartBotReply(string $text): string
    {
        $textLower = mb_strtolower($text);

        // Tìm kiếm sản phẩm theo từ khóa (dành cho mọi cụm từ từ người dùng nhập)
        if (str_contains($textLower, 'máy hút mùi') || str_contains($textLower, 'giá') || str_contains($textLower, 'sản phẩm') || str_contains($textLower, 'under cabinet') || str_contains($textLower, 'cabinet') || str_contains($textLower, 'hood')) {
            $query = Hood::query();
            
            $keywords = explode(' ', $text);
            foreach ($keywords as $kw) {
                $kw = trim($kw);
                if (strlen($kw) > 1 && !in_array($kw, ['giá', 'báo', 'máy', 'hút', 'mùi', 'tìm'])) {
                    $query->orWhere('name', 'like', "%{$kw}%")
                          ->orWhere('model', 'like', "%{$kw}%");
                }
            }

            $hoods = $query->take(4)->get();

            if ($hoods->count() > 0) {
                $reply = "🤖 <b>XFAN Bot tìm thấy sản phẩm gợi ý phù hợp:</b><br>";
                foreach ($hoods as $hood) {
                    $priceStr = number_format($hood->price ?? 0, 0, ',', '.') . 'đ';
                    $reply .= "• <b>{$hood->name}</b> - Giá: <span class='text-danger fw-bold'>{$priceStr}</span><br>";
                }
                $reply .= "<br>👉 Bạn có thể truy cập mục <b>Cửa Hàng</b> trên thanh menu để xem chi tiết nhé!";
                return $reply;
            }
        }

        if (str_contains($textLower, 'chào') || str_contains($textLower, 'hi') || str_contains($textLower, 'hello')) {
            return "🤖 <b>XFAN Bot:</b> Xin chào! Tôi là trợ lý ảo XFAN. Bạn muốn tìm hiểu dòng máy hút mùi nào hay dịch vụ lắp đặt của chúng tôi?";
        }

        return "🤖 <b>XFAN Bot:</b> Cảm ơn bạn đã nhắn tin. Tôi có thể hỗ trợ bạn chọn máy hút mùi cao cấp hoặc đặt lịch bảo dưỡng. Để trao đổi trực tiếp với nhân viên hỗ trợ, bạn vui lòng bấm <b>'🎧 Kết nối trực tiếp với Nhân viên tư vấn'</b>.";
    }

    /**
     * Đếm số tin nhắn chưa đọc của người dùng
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
     * Đánh dấu tin nhắn là đã đọc
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
     * Nhân viên lấy danh sách Khách hàng
     */
    public function getAdminUsers()
    {
        $staffAndAdminIds = User::whereIn('role', ['admin', 'staff'])
            ->orWhere('email', 'like', '%admin%')
            ->pluck('id')
            ->toArray();

        $senders   = ChatMessage::whereNotNull('sender_id')->whereNotIn('sender_id', $staffAndAdminIds)->pluck('sender_id');
        $receivers = ChatMessage::whereNotNull('receiver_id')->whereNotIn('receiver_id', $staffAndAdminIds)->pluck('receiver_id');
        $userIds   = $senders->merge($receivers)->unique();

        $users = User::whereIn('id', $userIds)
            ->select('id', 'name', 'email', 'role')
            ->get()
            ->map(function ($user) {
                $user->unread_count = ChatMessage::where('sender_id', $user->id)
                    ->where('is_read', false)
                    ->where('content', 'not like', '%🤖%')
                    ->where('content', 'not like', '%XFAN Bot%')
                    ->where('content', 'not like', '%🎧%')
                    ->where('content', 'not like', '%Hệ thống:%')
                    ->where('content', '!=', 'exit')
                    ->where('content', '!=', 'connect_staff')
                    ->count();

                $latestMsg = ChatMessage::where(function ($q) use ($user) {
                    $q->where('sender_id', $user->id)
                      ->orWhere('receiver_id', $user->id);
                })
                ->where('content', 'not like', '%🤖%')
                ->where('content', 'not like', '%XFAN Bot%')
                ->where('content', 'not like', '%🎧%')
                ->where('content', 'not like', '%Hệ thống:%')
                ->where('content', '!=', 'exit')
                ->where('content', '!=', 'connect_staff')
                ->latest()
                ->first();

                $user->latest_message = $latestMsg ? ($latestMsg->content ?? $latestMsg->message ?? '') : '';
                $user->last_activity  = $latestMsg ? $latestMsg->created_at : null;

                return $user;
            })
            ->sortByDesc(function ($user) {
                return $user->last_activity ? $user->last_activity->timestamp : 0;
            })
            ->values();

        return response()->json($users);
    }

    /**
     * Nhân viên xem tin nhắn với 1 Khách hàng
     */
    public function getAdminMessages($userId)
    {
        ChatMessage::where('sender_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = ChatMessage::where(function ($q) use ($userId) {
            $q->where('sender_id', $userId)
              ->orWhere('receiver_id', $userId);
        })
        ->where(function ($q) {
            $q->whereNull('content')
              ->orWhere(function ($sub) {
                  $sub->where('content', 'not like', '%🤖%')
                      ->where('content', 'not like', '%XFAN Bot%')
                      ->where('content', 'not like', '%🎧%')
                      ->where('content', 'not like', '%Hệ thống:%')
                      ->where('content', '!=', 'exit')
                      ->where('content', '!=', 'connect_staff');
              });
        })
        ->orderBy('created_at', 'asc')
        ->get();

        return response()->json($messages);
    }

    /**
     * Nhân viên trả lời tin nhắn cho 1 Khách hàng
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

        $this->setIsAdminMode($request->user_id, true);

        return response()->json(['status' => 'success', 'data' => $chat]);
    }

    /**
     * Kiểm tra tổng số tin nhắn chưa đọc đối với Nhân viên
     */
    public function checkAdminUnread()
    {
        $staffAndAdminIds = User::whereIn('role', ['admin', 'staff'])
            ->orWhere('email', 'like', '%admin%')
            ->pluck('id')
            ->toArray();

        $count = ChatMessage::whereIn('sender_id', function($q) use ($staffAndAdminIds) {
            $q->select('id')->from('users')->whereNotIn('id', $staffAndAdminIds);
        })
        ->where('is_read', false)
        ->where('content', 'not like', '%🤖%')
        ->where('content', 'not like', '%XFAN Bot%')
        ->where('content', 'not like', '%🎧%')
        ->where('content', 'not like', '%Hệ thống:%')
        ->where('content', '!=', 'exit')
        ->where('content', '!=', 'connect_staff')
        ->count();

        return response()->json(['unread_count' => $count]);
    }
}