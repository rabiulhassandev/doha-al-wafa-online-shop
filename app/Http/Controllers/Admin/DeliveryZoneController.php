<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeliveryZoneRequest;
use App\Models\DeliveryZone;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class DeliveryZoneController extends Controller
{
    /**
     * List the delivery areas.
     */
    public function index(): View
    {
        return view('admin.delivery-zones.index', ['deliveryZones' => DeliveryZone::query()->ordered()->get()]);
    }

    /**
     * Show the form for adding a delivery area.
     */
    public function create(): View
    {
        return view('admin.delivery-zones.create', ['deliveryZone' => new DeliveryZone]);
    }

    /**
     * Add a delivery area.
     */
    public function store(DeliveryZoneRequest $request): RedirectResponse
    {
        $deliveryZone = DeliveryZone::query()->create($request->zoneAttributes());

        return to_route('admin.delivery-zones.index')->with('status', "“{$deliveryZone->name_en}” was added.");
    }

    /**
     * Show the form for editing a delivery area.
     */
    public function edit(DeliveryZone $deliveryZone): View
    {
        return view('admin.delivery-zones.edit', ['deliveryZone' => $deliveryZone]);
    }

    /**
     * Update a delivery area.
     */
    public function update(DeliveryZoneRequest $request, DeliveryZone $deliveryZone): RedirectResponse
    {
        $deliveryZone->update($request->zoneAttributes());

        return to_route('admin.delivery-zones.index')->with('status', "“{$deliveryZone->name_en}” was updated.");
    }

    /**
     * Delete a delivery area.
     */
    public function destroy(DeliveryZone $deliveryZone): RedirectResponse
    {
        $deliveryZone->delete();

        return to_route('admin.delivery-zones.index')->with('status', "“{$deliveryZone->name_en}” was deleted.");
    }
}
