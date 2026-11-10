{{--
    Hands an ad tracking event (see App\Services\Tracking) to resources/js/store/tracking.js,
    which sends it once the pixels may load. Nothing is sent from here.
--}}
@props(['event' => null])

@if ($event !== null)
    <script type="application/json" data-track-event>@json($event)</script>
@endif
