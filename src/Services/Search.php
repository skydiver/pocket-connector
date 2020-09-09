<?php

namespace Skydiver\PocketConnector\Services;

class Search
{
    public function search($model, array $searchParams)
    {
        // search, tags, domain
        extract($searchParams);

        $items = $model::take(20);

        if ($search) {
            $items
                ->where('resolved_title', 'like', "%$search%")
                ->orWhere('given_title', 'like', "%$search%");
        }

        if ($tags) {
            $tagsArray = explode(',', $tags);

            $items->where(function ($query) use ($tagsArray) {
                // $query->whereIn('extra.tags', $tags);        // tag OR tag
                foreach ($tagsArray as $tag) {
                    $query->where('extra.tags', $tag);
                }
            });
        }

        if ($domain) {
            $items
                ->where('extra.resolved_domain', 'like', "%$domain%")
                ->orWhere('extra.given_domain', 'like', "%$domain%");
        }

        return $items->get();
    }
}
