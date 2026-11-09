<?php

namespace App\Actions;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Moves a record one place up or down among its siblings and renumbers their sort_order.
 */
class MoveInList
{
    /**
     * @param  Collection<int, Model>  $siblings  every record in the list, in display order, including $item
     * @param  'up'|'down'  $direction
     */
    public function handle(Collection $siblings, Model $item, string $direction): void
    {
        $ids = $siblings->modelKeys();
        $index = array_search($item->getKey(), $ids, true);
        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if ($index === false || ! array_key_exists($target, $ids)) {
            return;
        }

        [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];

        DB::transaction(function () use ($ids, $item): void {
            foreach ($ids as $position => $id) {
                $item->newQuery()->whereKey($id)->update(['sort_order' => ($position + 1) * 10]);
            }
        });
    }
}
