<?php

namespace App\Models;

use App\Support\QatarPhone;
use Database\Factories\ContactMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A message sent with the store's contact form.
 */
#[Fillable(['name', 'phone', 'email', 'message', 'locale', 'ip_address', 'read_at'])]
class ContactMessage extends Model
{
    /** @use HasFactory<ContactMessageFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'immutable_datetime',
        ];
    }

    /**
     * Get the sender's phone number written for people, e.g. "+974 5555 1234".
     */
    public function formattedPhone(): ?string
    {
        return $this->phone === null ? null : (QatarPhone::tryParse($this->phone)?->formatted() ?? $this->phone);
    }

    /**
     * Scope the query to messages nobody on the team opened yet.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function unread(Builder $query): void
    {
        $query->whereNull('read_at');
    }
}
