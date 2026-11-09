<?php

namespace App\Mail;

use App\Models\ProductVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the store team, in English, which colors and sizes on sale are sold out or running low.
 */
class LowStockDigest extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, ProductVariant>  $variants  with their product, product color and size loaded
     */
    public function __construct(public Collection $variants, public int $threshold) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $soldOut = $this->variants->where('stock', 0)->count();

        return new Envelope(
            subject: "Stock check: {$soldOut} sold out, ".($this->variants->count() - $soldOut).' running low',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(view: 'mail.low-stock-digest');
    }
}
