<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\SupportRequest;

class SupportReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public $supportRequest;
    public $replyMessage;
    public $attachmentPath;

    /**
     * Khởi tạo mail phản hồi
     */
    public function __construct(SupportRequest $supportRequest, string $replyMessage, ?string $attachmentPath = null)
    {
        $this->supportRequest = $supportRequest;
        $this->replyMessage   = $replyMessage;
        $this->attachmentPath = $attachmentPath;
    }

    /**
     * Dựng khung Email HTML gửi tới Gmail khách hàng
     */
    public function build()
    {
        $email = $this->subject('[XFAN STORE] Phản hồi yêu cầu hỗ trợ #' . $this->supportRequest->id)
                      ->view('emails.support_reply');

        if ($this->attachmentPath && file_exists($this->attachmentPath)) {
            $email->attach($this->attachmentPath);
        }

        return $email;
    }
}