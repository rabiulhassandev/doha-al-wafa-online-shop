<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use App\Support\Digits;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * People who joined the store's community from the footer, to receive news and offers.
 */
class SubscriberController extends Controller
{
    /**
     * List subscribers, newest first.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        return view('admin.subscribers.index', [
            'subscribers' => $this->search(Subscriber::query(), $search)->latest()->orderByDesc('id')->paginate(50)->withQueryString(),
            'search' => $search,
            'total' => Subscriber::query()->count(),
        ]);
    }

    /**
     * Download every subscriber as a CSV file.
     */
    public function export(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'w');
            // A byte order mark tells Excel the file is UTF-8.
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['WhatsApp number', 'Email', 'Language', 'Joined'], escape: '');

            foreach (Subscriber::query()->oldest()->orderBy('id')->lazy(500) as $subscriber) {
                fputcsv($output, [$subscriber->phone, $subscriber->email, $subscriber->locale, $subscriber->created_at->format('Y-m-d')], escape: '');
            }

            fclose($output);
        }, 'subscribers-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Remove someone who asked to stop receiving news and offers.
     */
    public function destroy(Subscriber $subscriber): RedirectResponse
    {
        $subscriber->delete();

        return to_route('admin.subscribers.index')->with('status', ($subscriber->phone ?? $subscriber->email).' was removed.');
    }

    /**
     * @param  Builder<Subscriber>  $query
     * @return Builder<Subscriber>
     */
    private function search(Builder $query, string $search): Builder
    {
        $digits = preg_replace('/\D+/', '', Digits::toWestern($search));

        return $query->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
            ->where('email', 'like', "%{$search}%")
            ->when($digits !== '', fn (Builder $query) => $query->orWhere('phone', 'like', "%{$digits}%"))));
    }
}
