<?php

namespace Skydiver\PocketConnector\Services;

use Jenssegers\Mongodb\Eloquent\Builder;

class Search
{
    public function search($model, array $searchParams) :Builder
    {
        // search, tags, domain
        extract($searchParams);

        $query = $model::query();

        if ($search) {
            $query
                ->where('resolved_title', 'like', "%$search%")
                ->orWhere('given_title', 'like', "%$search%");
        }

        if ($tags) {
            $tagsArray = explode(',', $tags);

            $query->where(function ($query) use ($tagsArray) {
                // $query->whereIn('extra.tags', $tags);        // tag OR tag
                foreach ($tagsArray as $tag) {
                    $query->where('extra.tags', $tag);
                }
            });
        }

        if ($domain) {
            $query
                ->where('extra.resolved_domain', 'like', "%$domain%")
                ->orWhere('extra.given_domain', 'like', "%$domain%");
        }

        return $query;
    }
}
