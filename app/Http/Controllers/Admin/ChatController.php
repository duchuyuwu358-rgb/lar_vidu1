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
     * Lấy ID tài khoản Admin mặc định cho Bot / Admin gửi tin nhắn
     */
    private function getAdminSenderId()
    {
        $admin = User::where('role', 'admin')->first();
        return $admin ? $admin->id : 2; // Mặc định Admin ID = 2 từ CSDL
    }

    /**
     * Lấy trạng thái chế độ Chat (Bot hay Admin/Nhân viên)
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
     * Cập nhật trạng thái chế độ Chat (Bot hay Admin/Nhân viên)
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
     * Hiển thị giao diện quản lý Chat
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
     * Khách hàng gửi tin nhắn (Xử lý ngắt Bot tuyệt đối khi đã ở chế độ Nhân viên)
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

        // 1. Lệnh EXIT: Thoát khỏi chế độ Chat Nhân viên -> Quay lại Bot
        if (in_array($textLower, ['exit', '/exit', 'thoát', 'thoat', 'quit'])) {
            $this->setIsAdminMode($userId, false);

            $userMsg = ChatMessage::create([
                'sender_id'   => $userId,
                'receiver_id' => $adminId,
                'content'     => $text,
                'is_read'     => false,
            ]);

            $reply = "🤖 **XFAN Bot:** Đã thoát cuộc trò chuyện với Nhân viên! XFAN Bot sẵn sàng hỗ trợ bạn tư vấn sản phẩm, dịch vụ. Bạn cần giúp gì tiếp theo?";

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

        // 2. Lệnh CONNECT: Kích hoạt chế độ Chat Nhân viên
        $isConnectCommand = in_array($textLower, ['connect_staff', 'connect_admin', 'kết nối']) ||
            str_contains($textLower, 'tư vấn viên') ||
            str_contains($textLower, 'gặp admin') ||
            str_contains($textLower, 'kết nối admin') ||
            str_contains($textLower, 'kết nối nhân viên');

        if ($isConnectCommand) {
            $this->setIsAdminMode($userId, true);

            $userMsg = null;
            if ($textLower !== 'connect_staff') {
                $userMsg = ChatMessage::create([
                    'sender_id'   => $userId,
                    'receiver_id' => $adminId,
                    'content'     => $text,
                    'is_read'     => false,
                ]);
            }

            $reply = "🎧 **Hệ thống:** Đã kết nối với Nhân viên tư vấn! Vui lòng gửi câu hỏi bên dưới (Bấm 'Thoát Nhân viên' hoặc gõ **'exit'** để quay lại XFAN Bot).";

            ChatMessage::create([
                'sender_id'   => $adminId,
                'receiver_id' => $userId,
                'content'     => $reply,
                'is_read'     => false,
            ]);

            return response()->json([
                'status' => 'success',
                'mode'   => 'admin',
                'reply'  => $reply,
                'data'   => $userMsg
            ]);
        }

        // 3. Đang trong chế độ CHAT NHÂN VIÊN -> LƯU CSDL & KHÔNG ĐƯỢC CHẠY BOT CỦA HỆ THỐNG!
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
                'reply'  => null, // Trả về null để Bot KHÔNG phản hồi
                'data'   => $userMsg
            ]);
        }

        // 4. Đang ở chế độ XFAN Bot tự động
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
     * Câu trả lời tự động cho XFAN Bot
     */
    private function generateSmartBotReply(string $text): string
    {
        $textLower = mb_strtolower($text);

        if (str_contains($textLower, 'máy hút mùi') || str_contains($textLower, 'giá') || str_contains($textLower, 'sản phẩm')) {
            $hoods = Hood::where('name', 'like', "%{$text}%")->orWhere('model', 'like', "%{$text}%")->take(3)->get();
            if ($hoods->count() > 0) {
                $reply = "🤖 **XFAN Bot tìm thấy sản phẩm gợi ý:**\n";
                foreach ($hoods as $hood) {
                    $reply .= "• **{$hood->name}** - Giá: " . number_format($hood->price) . "đ\n";
                }
                return $reply;
            }
        }

        if (str_contains($textLower, 'chào') || str_contains($textLower, 'hi') || str_contains($textLower, 'hello')) {
            return "🤖 **XFAN Bot:** Xin chào! Tôi là trợ lý ảo XFAN. Bạn muốn tìm hiểu dòng máy hút mùi nào hay dịch vụ lắp đặt của chúng tôi?";
        }

        return "🤖 **XFAN Bot:** Cảm ơn bạn đã nhắn tin: '{$text}'. Tôi có thể hỗ trợ bạn chọn máy hút mùi cao cấp hoặc đặt lịch vệ sinh. Để trò chuyện trực tiếp với nhân viên hỗ trợ, bạn vui lòng chọn **'Kết nối trực tiếp với Nhân viên tư vấn'**.";
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
     * Đánh dấu tất cả tin nhắn gửi tới người dùng là đã đọc
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
     * Admin/Nhân viên lấy danh sách khách hàng
     */
    public function getAdminUsers()
    {
        $adminId = Auth::id();

        $senders   = ChatMessage::whereNotNull('sender_id')->where('sender_id', '!=', $adminId)->pluck('sender_id');
        $receivers = ChatMessage::whereNotNull('receiver_id')->where('receiver_id', '!=', $adminId)->pluck('receiver_id');
        $userIds   = $senders->merge($receivers)->unique();

        $users = User::whereIn('id', $userIds)
            ->select('id', 'name', 'email', 'role')
            ->get()
            ->map(function ($user) use ($adminId) {
                $user->unread_count = ChatMessage::where('sender_id', $user->id)
                    ->where(function ($q) use ($adminId) {
                        $q->where('receiver_id', $adminId)->orWhereNull('receiver_id');
                    })
                    ->where('is_read', false)
                    ->count();

                $latestMsg = ChatMessage::where(function ($q) use ($user) {
                    $q->where('sender_id', $user->id)
                      ->orWhere('receiver_id', $user->id);
                })->latest()->first();

                $user->latest_message = $latestMsg ? $latestMsg->content : '';
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
     * Admin/Nhân viên lấy nội dung tin nhắn với 1 User
     */
    public function getAdminMessages($userId)
    {
        $adminId = Auth::id();

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
     * Admin/Nhân viên gửi tin nhắn cho 1 User
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
     * Đếm tổng số tin nhắn chưa đọc đối với Nhân viên
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