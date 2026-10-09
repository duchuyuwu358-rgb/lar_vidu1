<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\Hood;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

class ChatController extends Controller
{
    private function getAdminSenderId()
    {
        $admin = User::where('role', 'admin')->first();
        return $admin ? $admin->id : 2;
    }

    private function getIsAdminMode($userId)
    {
        if (Schema::hasTable('chat_sessions')) {
            $session = DB::table('chat_sessions')->where('user_id', $userId)->first();
            if ($session) {
                return (bool) $session->is_admin_chat;
            }
        }
        return (bool) session("is_admin_chat_{$userId}", false);
    }

    private function setIsAdminMode($userId, bool $status)
    {
        if (Schema::hasTable('chat_sessions')) {
            DB::table('chat_sessions')->updateOrInsert(
                ['user_id' => $userId],
                ['is_admin_chat' => $status ? 1 : 0, 'updated_at' => now()]
            );
        }
        session(["is_admin_chat_{$userId}" => $status]);
    }

    public function index()
    {
        if (View::exists('admin.chat')) {
            return view('admin.chat');
        } elseif (View::exists('admin.messages')) {
            return view('admin.messages');
        }
        return view('admin.portal');
    }

    public function getUserMessages()
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['error' => 'Chưa đăng nhập'], 401);
        }

        $isAdminMode = $this->getIsAdminMode($userId);

        $messages = ChatMessage::where(function ($q) use ($userId) {
            $q->where('sender_id', $userId)
              ->orWhere('receiver_id', $userId);
        })
        ->where('content', 'not like', '%🤖%')
        ->where('content', 'not like', '%XFAN Bot%')
        ->where('content', '!=', 'exit')
        ->where('content', '!=', 'connect_staff')
        ->orderBy('created_at', 'asc')
        ->get();

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
        ]);

        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['error' => 'Chưa đăng nhập'], 401);
        }

        $text      = trim($request->message);
        $textLower = mb_strtolower($text);
        $adminId   = $this->getAdminSenderId();

        // 1. Khách hàng bấm thoát khỏi chế độ Nhân viên tư vấn
        if (in_array($textLower, ['exit', '/exit', 'thoát', 'thoat', 'quit'])) {
            $this->setIsAdminMode($userId, false);

            return response()->json([
                'status' => 'success',
                'mode'   => 'bot',
                'reply'  => '🤖 Bạn đã thoát khỏi chế độ Nhân viên tư vấn. XFAN Bot sẵn sàng hỗ trợ bạn!'
            ]);
        }

        // 2. Kích hoạt chuyển sang Nhân viên tư vấn (Chống lưu thông báo trùng lặp)
        if ($textLower === 'connect_staff' || $textLower === 'connect_admin') {
            $this->setIsAdminMode($userId, true);

            $lastMsg = ChatMessage::where('sender_id', $userId)->latest()->first();
            $sysMsg = null;
            
            if (!$lastMsg || !str_contains($lastMsg->content ?? '', '🎧')) {
                $sysMsg = ChatMessage::create([
                    'sender_id'   => $userId,
                    'receiver_id' => $adminId,
                    'content'     => '🎧 Khách hàng đã yêu cầu kết nối trực tiếp với Nhân viên tư vấn.',
                    'is_read'     => false,
                ]);
            }

            return response()->json([
                'status' => 'success',
                'mode'   => 'admin',
                'reply'  => '✅ Đã kết nối với Nhân viên tư vấn! Vui lòng nhập nội dung cần hỗ trợ.',
                'data'   => $sysMsg
            ]);
        }

        // 3. Đang ở chế độ Chat trực tiếp với Nhân viên -> Lưu CSDL gửi Nhân viên
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

        // 4. Đang hỏi XFAN Bot -> Trả lời qua JSON, KHÔNG LƯU CSDL ĐỂ TRÁNH LÀM RÁC CSDL NHÂN VIÊN
        $reply = $this->generateSmartBotReply($text);

        return response()->json([
            'status' => 'success',
            'mode'   => 'bot',
            'reply'  => $reply,
            'data'   => [
                'sender_id'  => $userId,
                'content'    => $text,
                'created_at' => now()->toIso8601String()
            ]
        ]);
    }

    private function generateSmartBotReply(string $text): string
    {
        $textLower = mb_strtolower($text);

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
     * Nhân viên lấy danh sách Khách hàng
     */
    public function getAdminUsers()
    {
        $staffAndAdminIds = User::whereIn('role', ['admin', 'staff'])
            ->orWhere('email', 'like', '%admin%')
            ->pluck('id')
            ->toArray();

        $activeHumanUserIds = [];
        if (Schema::hasTable('chat_sessions')) {
            $activeHumanUserIds = DB::table('chat_sessions')
                ->where('is_admin_chat', 1)
                ->pluck('user_id')
                ->toArray();
        }

        $senders   = ChatMessage::whereNotIn('sender_id', $staffAndAdminIds)->pluck('sender_id')->toArray();
        $receivers = ChatMessage::whereNotIn('receiver_id', $staffAndAdminIds)->pluck('receiver_id')->toArray();

        $validUserIds = array_unique(array_merge($activeHumanUserIds, $senders, $receivers));

        $users = User::whereIn('id', $validUserIds)
            ->select('id', 'name', 'email', 'role')
            ->get()
            ->map(function ($user) {
                $user->unread_count = ChatMessage::where('sender_id', $user->id)
                    ->where('is_read', false)
                    ->where('content', 'not like', '%🤖%')
                    ->where('content', 'not like', '%XFAN Bot%')
                    ->where('content', '!=', 'exit')
                    ->where('content', '!=', 'connect_staff')
                    ->count();

                $latestMsg = ChatMessage::where(function ($q) use ($user) {
                    $q->where('sender_id', $user->id)
                      ->orWhere('receiver_id', $user->id);
                })
                ->where('content', 'not like', '%🤖%')
                ->where('content', 'not like', '%XFAN Bot%')
                ->where('content', '!=', 'exit')
                ->where('content', '!=', 'connect_staff')
                ->latest()
                ->first();

                $user->latest_message = $latestMsg ? ($latestMsg->content ?? '') : '';
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
     * Nhân viên lấy danh sách tin nhắn trực tiếp với 1 Khách hàng
     */
    public function getAdminMessages($userId)
    {
        ChatMessage::where('sender_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        // Lọc hoàn toàn tin nhắn tự động của Bot khỏi màn hình Nhân viên
        $messages = ChatMessage::where(function ($q) use ($userId) {
            $q->where('sender_id', $userId)
              ->orWhere('receiver_id', $userId);
        })
        ->where('content', 'not like', '%🤖%')
        ->where('content', 'not like', '%XFAN Bot%')
        ->where('content', '!=', 'exit')
        ->where('content', '!=', 'connect_staff')
        ->orderBy('created_at', 'asc')
        ->get();

        return response()->json($messages);
    }

    public function sendAdminMessage(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'message' => ['required', 'string', 'max:2000'],
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
        ->where('content', '!=', 'exit')
        ->where('content', '!=', 'connect_staff')
        ->count();

        return response()->json(['unread_count' => $count]);
    }
}