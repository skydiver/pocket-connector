<?php

namespace Skydiver\PocketConnector\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Skydiver\PocketConnector\Services\Import;

class ImportItems
{
    public function fetch(string $key, string $token, ?int $limit, ?int $since, string $userId): array
    {
        $items = App::make(Import::class)
            ->fetchItems($key, $token, $limit, $since);

        return $this->addExtraInfo($userId, $items);
    }

    /**
     * Add extra attributes
     *
     * @param array $items
     * @return array
     */
    public function addExtraInfo(string $userId, array $items): array
    {
        return collect($items)->map(function ($item) use ($userId) {
            $tags = !empty($item['tags']) ? collect($item['tags'])->keys()->toArray() : null;
            $given_domain = !empty($item['given_url']) ? parse_url($item['given_url'])['host'] : null;
            $resolved_domain = !empty($item['resolved_url']) ? parse_url($item['resolved_url'])['host'] : null;

            $item['extra'] = [
                'tags'            => $tags,
                'given_domain'    => $given_domain,
                'resolved_domain' => $resolved_domain,
            ];

            return array_merge(['user_id' => $userId], $item);
        })->toArray();
    }

    /**
     * Mass create items
     *
     * @param array $items
     * @return void
     */
    public function insert(string $connection, string $table, array $items, callable $callback = null)
    {
        foreach ($items as $item) {
            DB::connection($connection)
                ->table($table)
                ->insert([$item]);

            if ($callback) {
                $callback();
            }
        }
    }

    /**
     * Insert only new records
     *
     * @param array $items
     * @return void
     */
    public function diff(string $connection, string $table, array $items)
    {
        $items = collect($items);

        // get ids to insert
        $ids = $items
            ->pluck('item_id')
            ->toArray();

        // match existing documents
        $exists = DB::connection($connection)
            ->table($table)
            ->select('item_id')
            ->whereIn('item_id', $ids)
            ->get()
            ->pluck('item_id')
            ->toArray();

        // filter new items
        return $items->filter(function ($item) use ($exists) {
            return !in_array($item['item_id'], $exists);
        })->toArray();
    }
}
