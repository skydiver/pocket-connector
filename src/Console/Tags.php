<?php

namespace Skydiver\PocketConnector\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Skydiver\PocketConnector\Services\Import as ImportService;
use Skydiver\PocketConnector\Services\ImportTags;

class Tags extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'pocket:tags
                            {--user= : Assign user_id to records}
                           ';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Import all tags into a collection';

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

        $key = env('POCKET_CONSUMER_KEY');
        $token = env('POCKET_ACCESS_TOKEN');

        $connection = config('pocket-connector.database_connection');
        $table = config('pocket-connector.table_tags');

        // Stop if no config found
        if (empty($key) || empty($token)) {
            $this->error('Error: missing "Consumer Key" and "Access Token"');
            die();
        }

        // Check if "pocket-tags" table exists
        if (!Schema::connection($connection)->hasTable($table)) {
            $this->error('Error: missing "' . $table . '" table');
            exit();
        }

        // Define service classes
        $importService = App::make(ImportService::class);
        $tagsService = App::make(ImportTags::class);

        // Get data from Pocket
        $items = $importService->fetchItems($key, $token);
        $tags = $tagsService->parseTags($items);

        $total = $tags->count();

        // Wipe existing tags
        DB::connection($connection)->table($table)->truncate();
        $this->warn('Wiped tags');

        $this->info(sprintf('Adding %d tags', $total));

        $this->info("\n");
        $tagsBar = $this->output->createProgressBar($total);

        // Start tags creation
        $tagsService->insertTags($connection, $table, $userId, $tags, function () use ($tagsBar) {
            $tagsBar->advance();
        });

        $tagsBar->finish();
        $this->info("\n");
    }
}
