<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * The inbox of messages sent with the store's contact form.
 */
class ContactMessageController extends Controller
{
    /**
     * List the messages, unread first, then newest.
     */
    public function index(): View
    {
        return view('admin.messages.index', [
            'contactMessages' => ContactMessage::query()
                ->orderByRaw('read_at is null desc')
                ->latest()
                ->orderByDesc('id')
                ->paginate(30),
        ]);
    }

    /**
     * Show a message and mark it as read.
     */
    public function show(ContactMessage $message): View
    {
        if ($message->read_at === null) {
            $message->update(['read_at' => now()]);
        }

        return view('admin.messages.show', ['contactMessage' => $message]);
    }

    /**
     * Delete a message.
     */
    public function destroy(ContactMessage $message): RedirectResponse
    {
        $message->delete();

        return to_route('admin.messages.index')->with('status', "The message from {$message->name} was deleted.");
    }
}
