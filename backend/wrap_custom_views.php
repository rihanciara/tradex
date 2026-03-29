<?php
/**
 * JerryUpdates: Custom View Wrapper Utility
 * 
 * USE: Run this script after adding NEW files to the 'custom_views/' directory.
 * It will automatically wrap them in the Tweak Architecture Toggle logic,
 * ensuring they switch safely between your custom code and the V12 core.
 */

$dir = __DIR__ . '/custom_views';
if (!is_dir($dir)) {
    die("Error: 'custom_views/' directory not found.\n");
}

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
$count = 0;

foreach ($iterator as $file) {
    if (!$file->isFile() || strpos($file->getFilename(), '.blade.php') === false) {
        continue;
    }

    $content = file_get_contents($file->getPathname());

    // Skip if already wrapped
    if (strpos($content, 'jerry_traditional_mode') !== false) {
        continue;
    }

    // Compute relative path to rebuild the Blade dot-notation
    $absolutePath = str_replace('\\', '/', $file->getPathname());
    $baseDir = str_replace('\\', '/', $dir) . '/';
    $relativePath = str_replace($baseDir, '', $absolutePath); // e.g. sale_pos/create.blade.php
    
    $viewName = str_replace(['/', '.blade.php'], ['.', ''], $relativePath);

    // Build the non-breaking Upgrade-Safe Wrapper
    $wrapper = "@if(\\Modules\\JerryUpdates\\Utils\\JerrySettings::get('jerry_traditional_mode', '1') == '1')\n";
    $wrapper .= "<!-- JERRY CUSTOM OVERRIDE START -->\n";
    $wrapper .= $content;
    $wrapper .= "\n<!-- JERRY CUSTOM OVERRIDE END -->\n";
    $wrapper .= "@else\n";
    $wrapper .= "    @include('{$viewName}')\n";
    $wrapper .= "@endif\n";

    file_put_contents($file->getPathname(), $wrapper);
    echo "Wrapped & Protected: {$relativePath} -> [Toggle Logic Applied]\n";
    $count++;
}

echo "\nDone! Total new views wrapped: $count\n";
