<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SupportRequest;
use App\Models\User;
use App\Mail\SupportReplyMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SupportController extends Controller
{
    /**
     * 1. Phía Khách hàng: Hiển thị form gửi thư hỗ trợ
     */
    public function showForm()
    {
        return view('user.support');
    }

    /**
     * 2. Phía Khách hàng: Lưu thư & Tệp đính kèm vào CSDL
     */
    public function sendSupport(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:100',
            'email'      => 'required|email|max:255',
            'phone'      => 'nullable|string|max:20',
            'subject'    => 'required|string|max:255',
            'message'    => 'required|string',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ], [
            'name.required'    => 'Vui lòng nhập họ và tên.',
            'email.required'   => 'Vui lòng nhập email liên hệ.',
            'subject.required' => 'Vui lòng nhập tiêu đề yêu cầu.',
            'message.required' => 'Vui lòng nhập nội dung cần hỗ trợ.',
            'attachment.mimes' => 'Tệp đính kèm phải có định dạng PDF, DOC, DOCX, JPG, JPEG hoặc PNG.',
            'attachment.max'   => 'Tệp đính kèm không vượt quá 10MB.',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('support_attachments', 'public');
        }

        $data = [
            'name'            => $validated['name'],
            'email'           => $validated['email'],
            'phone'           => $validated['phone'] ?? null,
            'subject'         => $validated['subject'],
            'message'         => $validated['message'],
            'attachment_path' => $attachmentPath,
            'status'          => 'pending',
            'user_id'         => auth()->id(),
        ];

        SupportRequest::create($data);

        return back()->with('success', 'Yêu cầu hỗ trợ đã được gửi thành công! Ban quản trị sẽ kiểm tra và phản hồi sớm nhất.');
    }

    /**
     * 3. Phía Khách hàng: Xem danh sách thư hỗ trợ & Phản hồi 2 chiều từ Admin
     */
    public function userHistory()
    {
        $user = auth()->user();

        $requests = SupportRequest::where(function ($q) use ($user) {
            if (Schema::hasColumn('support_requests', 'user_id')) {
                $q->where('user_id', $user->id)->orWhere('email', $user->email);
            } else {
                $q->where('email', $user->email);
            }
        })->orderBy('id', 'desc')->paginate(10);

        return view('user.history', compact('requests'));
    }

    /**
     * 4. Phía Admin & Nhân viên: Thống kê số lượng + Danh sách hòm thư
     */
    public function adminIndex(Request $request)
    {
        $search = trim($request->get('search', ''));
        $status = $request->get('status', 'all');

        $query = SupportRequest::latest();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $totalCount   = SupportRequest::count();
        $pendingCount = SupportRequest::where('status', 'pending')->count();
        $repliedCount = SupportRequest::where('status', 'replied')->count();

        $supportRequests = $query->paginate(10)->withQueryString();

        return view('admin.support.index', compact('supportRequests', 'search', 'status', 'totalCount', 'pendingCount', 'repliedCount'));
    }

    /**
     * 5. Phía Admin & Nhân viên: Phản hồi thư, lưu CSDL 2 chiều & Gửi Mail
     */
    public function adminReply(Request $request, $id)
    {
        $supportRequest = SupportRequest::findOrFail($id);

        $request->validate([
            'reply_message' => 'required_if:status,replied|nullable|string',
            'status'        => 'required|in:pending,replied',
            'attachment'    => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ], [
            'reply_message.required_if' => 'Vui lòng nhập nội dung câu trả lời trước khi chuyển sang trạng thái Đã phản hồi.',
        ]);

        if ($request->filled('reply_message')) {
            $tempPath = null;
            if ($request->hasFile('attachment')) {
                $relPath  = $request->file('attachment')->store('temp_reply_attachments', 'public');
                $tempPath = storage_path('app/public/' . $relPath);
            }

            if (Schema::hasColumn('support_requests', 'reply_content')) {
                $supportRequest->reply_content = $request->reply_message;
            }
            $supportRequest->status = 'replied';
            $supportRequest->save();

            try {
                Mail::to($supportRequest->email)->send(
                    new SupportReplyMail($supportRequest, $request->reply_message, $tempPath)
                );

                if ($tempPath && file_exists($tempPath)) {
                    @unlink($tempPath);
                }
            } catch (\Throwable $e) {
                if ($tempPath && file_exists($tempPath)) {
                    @unlink($tempPath);
                }
                Log::error('Lỗi gửi mail phản hồi: ' . $e->getMessage());

                return back()->with('success', 'Đã lưu phản hồi vào CSDL! (Không gửi được Email: ' . $e->getMessage() . ')');
            }
        } else {
            $supportRequest->status = $request->status;
            $supportRequest->save();
        }

        return back()->with('success', 'Đã lưu câu trả lời và gửi thông báo Gmail cho khách hàng thành công!');
    }

    /**
     * 6. Phía Admin: Gửi Thư Hỗ Trợ & Thông Báo Khách Hàng (Giao diện HTML đẹp)
     */
    public function sendPromotionMail(Request $request)
    {
        $request->validate([
            'recipient'  => 'required',
            'subject'    => 'required|string|max:255',
            'message'    => 'required|string',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $recipients = [];
        if ($request->recipient === 'all') {
            $recipients = User::whereNotNull('email')->pluck('email')->toArray();
        } else {
            $recipients = [$request->recipient];
        }

        $attachment = $request->file('attachment');
        $successCount = 0;

        foreach ($recipients as $email) {
            try {
                Mail::send('emails.support', [
                    'recipientName'  => $email,
                    'subjectTitle'   => $request->subject,
                    'contentMessage' => $request->message,
                ], function ($mail) use ($email, $request, $attachment) {
                    $mail->to($email)->subject($request->subject);

                    if ($attachment) {
                        $mail->attach($attachment->getRealPath(), [
                            'as'   => $attachment->getClientOriginalName(),
                            'mime' => $attachment->getClientMimeType(),
                        ]);
                    }
                });
                $successCount++;
            } catch (\Throwable $e) {
                Log::error("Lỗi gửi mail hỗ trợ đến {$email}: " . $e->getMessage());
            }
        }

        if ($successCount > 0) {
            return back()->with('success', "Đã gửi thư hỗ trợ kèm tập tin đính kèm thành công đến {$successCount} khách hàng!");
        }

        return back()->with('error', 'Không thể gửi email. Vui lòng kiểm tra log hệ thống.');
    }

    /**
     * 7. Phía Admin & Nhân viên: Xóa thư hỗ trợ
     */
    public function destroy($id)
    {
        $supportRequest = SupportRequest::findOrFail($id);
        if ($supportRequest->attachment_path) {
            @unlink(storage_path('app/public/' . $supportRequest->attachment_path));
        }
        $supportRequest->delete();

        return back()->with('success', 'Đã xóa thư hỗ trợ thành công!');
    }
}