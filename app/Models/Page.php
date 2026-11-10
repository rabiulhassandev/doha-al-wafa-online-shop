<?php

namespace App\Models;

use App\Models\Concerns\Translatable;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * A store page such as About, Delivery or Privacy, written in Markdown in both languages.
 */
#[Fillable(['slug', 'title_en', 'title_ar', 'body_en', 'body_ar', 'sort_order', 'is_published'])]
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory, Translatable;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'sort_order' => 0,
        'is_published' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    /**
     * Get the body in the current language as HTML. Raw HTML and unsafe links in the Markdown are removed.
     */
    public function bodyHtml(): HtmlString
    {
        return new HtmlString(Str::markdown((string) $this->translated('body'), ['html_input' => 'strip', 'allow_unsafe_links' => false]));
    }

    /**
     * Scope the query to pages shoppers can open.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * Order pages as the owner arranged them.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }
}
