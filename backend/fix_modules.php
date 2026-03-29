<?php
/**
 * One-time fix: Corrects the corrupted enabled_modules value in the business table.
 * Run from server: php fix_modules.php
 * DELETE THIS FILE AFTER RUNNING.
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$correct_value = json_encode(['purchases', 'add_sale', 'pos_sale', 'stock_transfers', 'stock_adjustment', 'expenses']);

// Loop and fix every business in the database
$businesses = DB::table('business')->get();
foreach ($businesses as $business) {
    echo "Business ID " . $business->id . " - BEFORE: " . var_export($business->enabled_modules, true) . "\n";
    DB::table('business')->where('id', $business->id)->update(['enabled_modules' => $correct_value]);
    echo "Business ID " . $business->id . " - AFTER: " . $correct_value . "\n\n";
}

// Clear sessions
array_map('unlink', glob(__DIR__ . '/storage/framework/sessions/*'));
echo "Sessions cleared.\n";

// Clear cache
Illuminate\Support\Facades\Artisan::call('optimize:clear');
echo "Cache cleared.\nDone! Delete this file and log in fresh.\n";
