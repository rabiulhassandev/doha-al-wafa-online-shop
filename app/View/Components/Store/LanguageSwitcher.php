<?php

namespace App\View\Components\Store;

use App\Support\LocalizedUrl;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class LanguageSwitcher extends Component
{
    /**
     * The other languages the current page is available in.
     *
     * @var array<string, array{label: string, url: string}>
     */
    public array $alternatives;

    /**
     * Create a new component instance.
     */
    public function __construct(LocalizedUrl $localizedUrl)
    {
        $this->alternatives = collect(config('shop.locales'))
            ->except(app()->getLocale())
            ->map(fn (array $locale, string $code): array => [
                'label' => $locale['label'],
                'url' => $localizedUrl->current($code),
            ])
            ->all();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.store.language-switcher');
    }
}
