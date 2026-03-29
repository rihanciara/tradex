<?php

namespace Modules\Exchange\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class PublishAssetsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exchange:publish-assets {--force : Overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish Exchange module assets to public directory';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Publishing Exchange module assets...');

        // Copy JavaScript file
        $source = __DIR__ . '/../Resources/assets/js/pos-exchange.js';
        $destination = public_path('js/pos-exchange.js');

        if (file_exists($source)) {
            if (!file_exists($destination) || $this->option('force')) {
                copy($source, $destination);
                $this->info('✓ Published: js/pos-exchange.js');
            } else {
                $this->warn('✗ Skipped: js/pos-exchange.js (already exists, use --force to overwrite)');
            }
        } else {
            $this->error('✗ Source file not found: ' . $source);
        }

        // Publish through Laravel's publish system
        $this->call('vendor:publish', [
            '--tag' => 'exchange-pos-js',
            '--force' => $this->option('force')
        ]);

        $this->info('Exchange assets published successfully!');
    }
}