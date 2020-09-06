<?php

namespace Skydiver\PocketConnector\Services;

use Illuminate\Support\Facades\Http;

class Import
{
    const FULL_LIMIT = 10000;

    public function fetchItems(string $key, string $token, ?int $count = self::FULL_LIMIT, ?int $since = null): array
    {
        $params = [
            'consumer_key' => $key,
            'access_token' => $token,
            'state'        => 'all',
            'detailType'   => 'complete',
            'sort'         => 'newest',
        ];

        if ($since) {
            $params['since'] = $since;
        } else {
            $params['count'] = $count;
        }

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36',
        ])->get('https://getpocket.com/v3/get', $params);

        return $response->json()['list'];
    }
}
