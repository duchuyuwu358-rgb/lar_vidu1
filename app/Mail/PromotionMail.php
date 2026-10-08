<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class PromotionMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $subjectText;
    public string $contentBody;
    public ?string $attachmentPath;
    public ?string $couponCode;

    public function __construct(string $subjectText, string $contentBody, ?string $attachmentPath = null, ?string $couponCode = null)
    {
        $this->subjectText    = $subjectText;
        $this->contentBody    = $contentBody;
        $this->attachmentPath = $attachmentPath;
        $this->couponCode     = $couponCode;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectText,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.promotion',
            with: [
                'mailTitle'      => $this->subjectText,
                'mailContent'    => $this->contentBody,
                'couponCode'     => $this->couponCode,
                'attachmentPath' => $this->attachmentPath,
            ],
        );
    }

    public function attachments(): array
    {
        // Nếu là tài liệu (PDF, DOCX...), đính kèm dạng file tải về ở chân thư
        if ($this->attachmentPath && file_exists($this->attachmentPath)) {
            $ext = strtolower(pathinfo($this->attachmentPath, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                return [
                    Attachment::fromPath($this->attachmentPath)
                ];
            }
        }

        return [];
    }
}