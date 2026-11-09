<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the customer their order is placed, in the language they ordered in. Send it with ->locale($order->locale).
 */
class OrderPlaced extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Your order :number is placed', ['number' => $this->order->number]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $this->order->loadMissing('items');

        return new Content(
            view: 'mail.orders.placed',
            with: ['confirmationUrl' => $this->order->confirmationUrl()],
        );
    }
}
