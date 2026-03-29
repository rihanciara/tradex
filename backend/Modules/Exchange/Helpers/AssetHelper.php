<?php

namespace Modules\Exchange\Helpers;

class AssetHelper
{
    /**
     * Get the URL for Exchange module JavaScript files
     *
     * @param string $file
     * @return string
     */
    public static function js($file)
    {
        $publicPath = public_path("js/modules/exchange/{$file}");
        $fallbackPath = public_path("js/{$file}");

        if (file_exists($publicPath)) {
            return asset("js/modules/exchange/{$file}");
        } elseif (file_exists($fallbackPath)) {
            return asset("js/{$file}");
        }

        // Return module asset path as fallback
        return module_asset('Exchange', "Resources/assets/js/{$file}");
    }

    /**
     * Get the URL for Exchange module CSS files
     *
     * @param string $file
     * @return string
     */
    public static function css($file)
    {
        $publicPath = public_path("css/modules/exchange/{$file}");
        $fallbackPath = public_path("css/{$file}");

        if (file_exists($publicPath)) {
            return asset("css/modules/exchange/{$file}");
        } elseif (file_exists($fallbackPath)) {
            return asset("css/{$file}");
        }

        // Return module asset path as fallback
        return module_asset('Exchange', "Resources/assets/css/{$file}");
    }

    /**
     * Check if assets are published
     *
     * @return bool
     */
    public static function areAssetsPublished()
    {
        return file_exists(public_path('js/pos-exchange.js'));
    }

    /**
     * Get asset version for cache busting
     *
     * @param string $file
     * @return string
     */
    public static function version($file)
    {
        $path = public_path("js/{$file}");

        if (file_exists($path)) {
            return filemtime($path);
        }

        return time();
    }
}