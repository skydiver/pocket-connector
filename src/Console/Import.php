<?php

namespace Skydiver\PocketConnector\Console;

use App;
use DB;

use App\Services\Import as ImportService;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class Import extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pocket:import
                            {--days=7 : Import last n days}
                            {--full : Make a full sync}
                            {--wipe : Wipe collection before insert data}
                            {--limit= : How many items to import (from new to old)}
                           ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import all your items to mongoDB';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->key = env('POCKET_CONSUMER_KEY');
        $this->token = env('POCKET_ACCESS_TOKEN');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->days = $this->option('days');
        $this->full = $this->option('full') ?? false;
        $this->limit = $this->option('limit') ?? ImportService::FULL_LIMIT;
        $this->wipe = $this->option('wipe');

        // Diff import
        $this->since = Carbon::now()->subDays($this->days)->getTimestamp();

        // Wipe database on full sync
        if ($this->full || $this->wipe) {
            $this->_wipe();
            $this->_rebuildIndexes();
        }

        $this->_import();

        $this->info("Process finished");
    }

    /**
     * Fetch Pocket items
     *
     * @return array
     */
    private function _fetch() : array
    {
        // prepare parameters
        $since = $this->full ? null : $this->since;

        if ($this->full) {
            $this->info('Fetch ' . $this->limit . ' items from Pocket.');
        } else {
            $this->info('Fetch Pocket items from last ' . $this->days . ' days.');
        }

        // Get items from Pocket
        $items = App::make(ImportService::class)
            ->fetchItems($this->key, $this->token, $this->limit, $since);

        return $items;
    }

    /**
     * Import items from Pocket
     *
     * @return void
     */
    private function _import()
    {
        $items = $this->_fetch();

        $items = $this->_addExtraInfo($items);

        if ($this->full) {
            $this->_insertAll($items);
        } else {
            $this->_insertDiff($items);
        }
    }

    /**
     * Mass create items
     *
     * @param array $items
     * @return void
     */
    private function _insertAll(array $items)
    {
        $this->info(sprintf('Adding %d items', count($items)));

        $this->info("\n");
        $itemsBar = $this->output->createProgressBar(count($items));

        foreach ($items as $item) {
            DB::table('items')->insert([$item]);
            $itemsBar->advance();
        }

        $itemsBar->finish();
        $this->info("\n");
    }

    /**
     * Insert only new records
     *
     * @param array $items
     * @return void
     */
    private function _insertDiff(array $items)
    {
        $this->info('Checking new items');

        $items = collect($items);

        // get ids to insert
        $ids = $items
            ->pluck('item_id')
            ->toArray();

        // match existing documents
        $exists = DB::table('items')
            ->select('item_id')
            ->whereIn('item_id', $ids)
            ->get()
            ->pluck('item_id')
            ->toArray();

        // filter new items
        $new = $items->filter(function ($item) use ($exists) {
            return !in_array($item['item_id'], $exists);
        })->toArray();

        if (empty($new)) {
            $this->info('There are no new items');
            return;
        }

        // insert diff
        $this->_insertAll($new);
    }

    /**
     * Add extra info for easier queries
     *
     * @param array $items
     * @return array
     */
    private function _addExtraInfo(array $items) :array
    {
        return collect($items)->map(function ($item) {
            $tags = !empty($item['tags']) ? collect($item['tags'])->keys()->toArray() : null;
            $given_domain = !empty($item['given_url']) ? parse_url($item['given_url'])['host'] : null;
            $resolved_domain = !empty($item['resolved_url']) ? parse_url($item['resolved_url'])['host'] : null;
            $item['extra'] = [
                'tags' => $tags,
                'given_domain' => $given_domain,
                'resolved_domain' => $resolved_domain,
            ];
            return $item;
        })->toArray();
    }

    /**
     * Wipe entire collection
     *
     * @return void
     */
    private function _wipe()
    {
        DB::table('items')->truncate();
        $this->warn('Wiped collection');
    }

    /**
     * Create collection indexes
     *
     * @return void
     */
    private function _rebuildIndexes()
    {
        Schema::table('items', function ($collection) {
            $collection->unique('item_id', 'item_id');
            $collection->index('given_title', 'given_title');
            $collection->index('resolved_title', 'resolved_title');
            $collection->index('given_url', 'given_url');
            $collection->index('resolved_url', 'resolved_url');
        });
        $this->warn('Indexes created');
    }
}