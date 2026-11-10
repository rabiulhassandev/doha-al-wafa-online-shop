<?php

namespace App\Actions;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Adds or changes a saved delivery address. An account always has exactly one default address
 * once it has any: the first address becomes the default, and a new default replaces the old one.
 */
class SaveAddress
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $user, array $attributes, bool $makeDefault = false, ?Address $address = null): Address
    {
        return DB::transaction(function () use ($user, $attributes, $makeDefault, $address): Address {
            $address ??= new Address(['user_id' => $user->id]);
            $otherAddresses = fn (): Builder => Address::query()
                ->where('user_id', $user->id)
                ->when($address->exists, fn (Builder $query) => $query->whereKeyNot($address->id));
            $isDefault = $makeDefault || ($address->exists && $address->is_default) || ! $otherAddresses()->exists();

            if ($isDefault) {
                $otherAddresses()->update(['is_default' => false]);
            }

            $address->user_id = $user->id;
            $address->fill([...$attributes, 'is_default' => $isDefault])->save();

            return $address;
        });
    }

    /**
     * Delete an address. When it was the default, the oldest remaining address takes its place.
     */
    public function delete(User $user, Address $address): void
    {
        DB::transaction(function () use ($user, $address): void {
            $address->delete();

            if ($address->is_default) {
                Address::query()->where('user_id', $user->id)->oldest('id')->first()?->update(['is_default' => true]);
            }
        });
    }
}
