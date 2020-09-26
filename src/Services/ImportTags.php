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
            $data = [
                'tag' => $tag
            ];

            if ($userId) {
                $data['user_id'] = $userId;
            }

            DB::connection($connection)->table($table)->insert($data);

            if ($callback) {
                $callback();
            }
        }
    }
}
