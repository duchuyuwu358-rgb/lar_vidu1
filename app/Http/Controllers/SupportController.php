<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SupportRequest;
use App\Mail\SupportReplyMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

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
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240', // Tối đa 10MB
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

        // Lưu thông tin vào CSDL
        SupportRequest::create([
            'name'            => $validated['name'],
            'email'           => $validated['email'],
            'phone'           => $validated['phone'] ?? null,
            'subject'         => $validated['subject'],
            'message'         => $validated['message'],
            'attachment_path' => $attachmentPath,
            'status'          => 'pending',
        ]);

        return back()->with('success', 'Yêu cầu hỗ trợ đã được gửi thành công! Ban quản trị sẽ kiểm tra và phản hồi sớm nhất.');
    }

    /**
     * 3. Phía Admin & Nhân viên: Thống kê số lượng + Danh sách hòm thư
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

        // Thống kê số lượng thư
        $totalCount   = SupportRequest::count();
        $pendingCount = SupportRequest::where('status', 'pending')->count();
        $repliedCount = SupportRequest::where('status', 'replied')->count();

        $supportRequests = $query->paginate(10)->withQueryString();

        return view('admin.support.index', compact('supportRequests', 'search', 'status', 'totalCount', 'pendingCount', 'repliedCount'));
    }

    /**
     * 4. Phía Admin & Nhân viên: Phản hồi thư trực tiếp + Đính kèm tệp/hình ảnh gửi Mail cho khách
     */
    public function adminReply(Request $request, $id)
    {
        $supportRequest = SupportRequest::findOrFail($id);

        $request->validate([
            'reply_message' => 'nullable|string',
            'status'        => 'required|in:pending,replied',
            'attachment'    => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        if ($request->filled('reply_message')) {
            $tempPath = null;
            if ($request->hasFile('attachment')) {
                $relPath  = $request->file('attachment')->store('temp_reply_attachments', 'public');
                $tempPath = storage_path('app/public/' . $relPath);
            }

            try {
                $subjectText = '[PHẢN HỒI HỖ TRỢ] ' . $supportRequest->subject;
                $contentBody = "Kính gửi {$supportRequest->name},\n\n" . $request->reply_message . "\n\nTrân trọng,\nBan quản trị XFAN Store";

                Mail::to($supportRequest->email)->send(new SupportReplyMail($subjectText, $contentBody, $tempPath));

                $supportRequest->status = 'replied';

                if ($tempPath && file_exists($tempPath)) {
                    @unlink($tempPath);
                }
            } catch (\Throwable $e) {
                if ($tempPath && file_exists($tempPath)) {
                    @unlink($tempPath);
                }
                Log::error('Lỗi gửi mail phản hồi: ' . $e->getMessage());
                return back()->with('error', 'Lỗi khi gửi email phản hồi: ' . $e->getMessage());
            }
        } else {
            $supportRequest->status = $request->status;
        }

        $supportRequest->save();

        return back()->with('success', 'Đã cập nhật trạng thái và gửi phản hồi email thành công!');
    }

    /**
     * 5. Phía Admin & Nhân viên: Xóa thư hỗ trợ
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