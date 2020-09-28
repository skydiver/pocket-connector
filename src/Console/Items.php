<?php

namespace Skydiver\PocketConnector\Console;

use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Skydiver\PocketConnector\Services\Import;
use Skydiver\PocketConnector\Services\ImportTags;
use Skydiver\PocketConnector\Services\ImportItems;

class Items extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pocket:items
                            {--user= : Assign user_id to records}
                            {--days=7 : Import last n days}
                            {--full : Make a full sync}
                            {--limit= : How many items to import (from new to old). Valid on full import only}
                            {--wipe : Wipe collection before insert data}
                           ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import all your items to mongoDB';

    private $key;
    private $token;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $userId = $this->option('user', null);

        // Stop if no userId specified
        if (empty($userId)) {
            $this->error('Error: user id is required');
            die();
        }

        $this->key = env('POCKET_CONSUMER_KEY');
        $this->token = env('POCKET_ACCESS_TOKEN');

        // Stop if no config found
        if (empty($this->key) || empty($this->token)) {
            $this->error('Error: missing "Consumer Key" and "Access Token"');
            die();
        }

        $this->connection = config('pocket-connector.database_connection');
        $this->table_tags = config('pocket-connector.table_tags');
        $this->table_items = config('pocket-connector.table_items');

        // Check if "pocket-tags" table exists
        if (!Schema::connection($this->connection)->hasTable($this->table_items)) {
            $this->error('Error: missing "' . $this->table_items . '" table');
            exit();
        }

        // Check if "pocket-tags" table exists
        if (!Schema::connection($this->connection)->hasTable($this->table_tags)) {
            $this->error('Error: missing "' . $this->table_tags . '" table');
            exit();
        }

        $this->user_id = $this->option('user');
        $this->days = $this->option('days');
        $this->limit = $this->option('limit') ?? Import::FULL_LIMIT;
        $this->full = $this->option('full') ?? false;
        $this->wipe = $this->option('wipe');

        // Diff import
        $this->since = Carbon::now()->subDays($this->days)->getTimestamp();

        // Wipe database on full sync
        if ($this->full || $this->wipe) {
            $this->wipe();
            $this->rebuildIndexes();
        }

        // Launch items import process
        $this->import();

        $this->info("Process finished");
    }

    /**
     * Import items from Pocket
     *
     * @return void
     */
    private function import()
    {
        // prepare parameters
        $since = $this->full ? null : $this->since;

        if ($this->full) {
            $this->info('Fetch ' . $this->limit . ' items from Pocket.');
        } else {
            $this->info('Fetch Pocket items from last ' . $this->days . ' days.');
        }

        // Define service class
        $itemsService = App::make(ImportItems::class);
        $tagsService = App::make(ImportTags::class);

        // Fetch items from Pocket
        $items = $itemsService->fetch($this->key, $this->token, $this->limit, $since, $this->user_id);
        $tags = $tagsService->parseTags($items);

        // Tags progress bar
        $totalTags = $tags->count();
        $tagsBar = $this->output->createProgressBar($totalTags);

        // Create tags
        $this->info('Insert tags:');
        $tagsService->insertTags($this->connection, $this->table_tags, $this->user_id, $tags, function () use ($tagsBar) {
            $tagsBar->advance();
        });
        $tagsBar->finish();
        $this->info("\n");

        // Check if new items only
        if (!$this->full) {
            $items = $itemsService->diff($this->connection, $this->table_items, $items);
        }

        // Create progress bars
        $totalItems = count($items);
        $itemsBar = $this->output->createProgressBar($totalItems);

        // Insert items
        $this->info('Insert items:');
        $itemsService->insert($this->connection, $this->table_items, $items, function () use ($itemsBar) {
            $itemsBar->advance();
        });

        $itemsBar->finish();
        $this->info("\n");
    }

    /**
     * Wipe entire collection
     *
     * @return void
     */
    private function wipe()
    {
        DB::connection($this->connection)
            ->table($this->table_tags)
            ->truncate();

        $this->warn('Wiped tags');

        DB::connection($this->connection)
            ->table($this->table_items)
            ->truncate();

        $this->warn('Wiped items collection');
    }

    /**
     * Create collection indexes
     *
     * @return void
     */
    private function rebuildIndexes()
    {
        Schema::connection($this->connection)->table($this->table_items, function ($collection) {
            $collection->unique('item_id', 'item_id');
            $collection->index('given_title', 'given_title');
            $collection->index('resolved_title', 'resolved_title');
            $collection->index('given_url', 'given_url');
            $collection->index('resolved_url', 'resolved_url');
        });

        $this->warn('Indexes created');
    }
}
