<?php

namespace Skydiver\PocketConnector\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Skydiver\PocketConnector\Services\Import as ImportService;

class Tags extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'pocket:tags';

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
        $key = env('POCKET_CONSUMER_KEY');
        $token = env('POCKET_ACCESS_TOKEN');

        // Stop if no config found
        if (empty($key) || empty($token)) {
            $this->error('Error: missing "Consumer Key" and "Access Token"');
            die();
        }

        // Check if "pocket-tags" table exists
        if (!Schema::hasTable('pocket-tags')) {
            $this->error('Error: missing "pocket-tags" table');
            exit();
        }

        // Get items from Pocket
        $items = App::make(ImportService::class)
            ->fetchItems($key, $token, 20);

        // Make a tags collection
        $tags = collect($items)
            ->filter(function ($item) {
                return !empty($item['tags']);
            })
            ->map(function ($item) {
                return collect($item['tags'])->keys();
            })
            ->flatten()
            ->unique()
            ->values();

        $total = $tags->count();

        // Wipe existing tags
        DB::table('pocket-tags')->truncate();
        $this->warn('Wiped tags');

        $this->info(sprintf('Adding %d tags', $total));

        $this->info("\n");
        $tagsBar = $this->output->createProgressBar($total);

        // Start inserting tags
        foreach ($tags as $tag) {
            DB::table('pocket-tags')->insert([
                'tag' => $tag
            ]);
            $tagsBar->advance();
        }

        $tagsBar->finish();
        $this->info("\n");
    }
}
