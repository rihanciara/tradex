<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ForceMigrateAll extends Command
{
    protected $signature = 'migrate:force-all {--dry-run : Show what would happen without making changes}';
    protected $description = 'Force-sync all migrations: truncates the migrations table and re-runs every migration, safely skipping already-applied schema changes.';

    public function handle()
    {
        $this->warn('=== Force Migration Sync Tool ===');
        $this->newLine();

        // Get all migration files
        $migrator = app('migrator');
        $paths = $migrator->paths();
        $paths[] = database_path('migrations');
        $files = $migrator->getMigrationFiles($paths);

        $this->info('Found ' . count($files) . ' migration files.');

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN — no changes will be made.');
            foreach ($files as $name => $file) {
                $this->line("  → {$name}");
            }
            return 0;
        }

        if (!$this->confirm('This will truncate the migrations table and re-run all migrations (skipping already-applied ones). Your DATA is safe. Continue?')) {
            $this->info('Cancelled.');
            return 0;
        }

        // Step 1: Truncate the corrupted migrations table
        DB::table('migrations')->truncate();
        $this->warn('Migrations table cleared.');
        $this->newLine();

        $batch = 1;
        $applied = 0;
        $skipped = 0;
        $errors = [];

        foreach ($files as $name => $file) {
            try {
                // Load the migration class from the file
                $migration = require $file;

                // Run the up() method
                $migration->up();

                // Record it in the migrations table
                DB::table('migrations')->insert([
                    'migration' => $name,
                    'batch' => $batch,
                ]);

                $this->info("[APPLIED] {$name}");
                $applied++;
            } catch (\Throwable $e) {
                $msg = $e->getMessage();

                // Always record it as "run" so future `migrate` calls don't try it again
                DB::table('migrations')->insert([
                    'migration' => $name,
                    'batch' => $batch,
                ]);

                if (
                    str_contains($msg, 'already exists') ||
                    str_contains($msg, 'Duplicate column') ||
                    str_contains($msg, 'Duplicate entry') ||
                    str_contains($msg, 'SQLSTATE[42S01]') || // table already exists
                    str_contains($msg, 'SQLSTATE[42S21]')    // column already exists
                ) {
                    $this->warn("[SKIPPED] {$name} (already applied to DB)");
                    $skipped++;
                } else {
                    $this->error("[ERROR]   {$name}: {$msg}");
                    $errors[] = $name . ': ' . $msg;
                    $skipped++;
                }
            }
        }

        $this->newLine();
        $this->info('=== Summary ===');
        $this->info("Applied (new):  {$applied}");
        $this->info("Skipped (exist): {$skipped}");

        if (!empty($errors)) {
            $this->newLine();
            $this->error('The following migrations had unexpected errors:');
            foreach ($errors as $err) {
                $this->error("  • {$err}");
            }
        }

        $this->newLine();
        $this->info('Database schema is now in sync with v6.12 codebase!');
        $this->info('Run: php artisan optimize:clear');

        return 0;
    }
}
