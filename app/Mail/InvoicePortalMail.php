<?php

namespace App\Mail;

use App\Models\OperHead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoicePortalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public OperHead $invoice,
        public string $portalUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'فاتورة رقم #' . $this->invoice->pro_id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'invoices::invoices.mail.portal-link',
        );
    }
}
