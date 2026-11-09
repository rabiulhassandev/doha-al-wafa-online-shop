<?php

namespace App\Mail;

use App\Models\Order;
use App\Support\Money;
use App\Support\QatarPhone;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the store team about a new order, in English, with everything needed to confirm and deliver it.
 */
class NewOrderAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New order {$this->order->number} · ".Money::format($this->order->grand_total).' · '.$this->order->delivery_zone_name_en,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $this->order->loadMissing('items');

        return new Content(
            view: 'mail.orders.new-order-alert',
            with: ['customerWhatsAppUrl' => 'https://wa.me/'.QatarPhone::tryParse($this->order->customer_phone)?->whatsAppNumber()],
        );
    }
}
