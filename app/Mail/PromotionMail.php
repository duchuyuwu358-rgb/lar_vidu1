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

    public function __construct(string $subjectText, string $contentBody, ?string $attachmentPath = null)
    {
        $this->subjectText    = $subjectText;
        $this->contentBody    = $contentBody;
        $this->attachmentPath = $attachmentPath;
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
            htmlString: nl2br(e($this->contentBody)),
        );
    }

    public function attachments(): array
    {
        if ($this->attachmentPath && file_exists($this->attachmentPath)) {
            return [
                Attachment::fromPath($this->attachmentPath)
            ];
        }

        return [];
    }
}