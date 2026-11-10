{{-- Schema.org data for search engines (see App\Support\StructuredData). "<" is escaped so no text can end the script. --}}
@props(['data'])

<script type="application/ld+json">{!! json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR) !!}</script>
