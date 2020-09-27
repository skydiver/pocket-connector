<?php

namespace Skydiver\PocketConnector\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ImportTags
{
    public function parseTags(array $items): Collection
    {
        return collect($items)
            ->filter(function ($item) {
                return !empty($item['tags']);
            })
            ->map(function ($item) {
                return collect($item['tags'])->keys();
            })
            ->flatten()
            ->unique()
            ->values();
    }

    public function insertTags(string $connection, string $table, string $userId, Collection $tags, callable $callback = null)
    {
        foreach ($tags as $tag) {
            DB::connection($connection)
                ->table($table)
                ->insert([
                    'tag'     => $tag,
                    'user_id' => $userId,
                ]);

            if ($callback) {
                $callback();
            }
        }
    }

    public function diff(string $connection, string $table, Collection $tags) :Collection
    {
        $tags = collect($tags);

        // match existing documents
        $exists = DB::connection($connection)
            ->table($table)
            ->select('tag')
            ->whereIn('tag', $tags)
            ->get()
            ->pluck('tag')
            ->toArray();

        // filter new items
        return $tags->filter(function ($tag) use ($exists) {
            return !in_array($tag, $exists);
        });
    }
}
