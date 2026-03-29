<?php

namespace Modules\AdvancedReports\Utils;

use App\System;

class VersionHelper
{
    /**
     * Get properly formatted version information for AdvancedReports module
     * Prevents "Cannot access offset of type string on string" errors
     *
     * @return array
     */
    public static function getVersionInfo()
    {
        $installedVersion = System::getProperty('advancedreports_version');
        $availableVersion = config('advancedreports.module_version', '1.1.4');

        return [
            'installed_version' => $installedVersion ?: 'Not Installed',
            'available_version' => $availableVersion,
            'is_update_available' => $installedVersion ? version_compare($availableVersion, $installedVersion, '>') : false
        ];
    }

    /**
     * Get formatted version string for display
     *
     * @return string
     */
    public static function getVersionString()
    {
        $version = self::getVersionInfo();
        return $version['installed_version'];
    }

    /**
     * Check if module is properly installed
     *
     * @return bool
     */
    public static function isInstalled()
    {
        $version = System::getProperty('advancedreports_version');
        return !empty($version);
    }

    /**
     * Safe version access that won't cause array offset errors
     * Use this instead of direct array access in templates
     *
     * @param mixed $versionData
     * @return string
     */
    public static function safeVersionAccess($versionData)
    {
        // Handle different types of version data
        if (is_array($versionData) && isset($versionData['installed_version'])) {
            return $versionData['installed_version'];
        } elseif (is_string($versionData)) {
            return $versionData;
        } else {
            return self::getVersionString();
        }
    }
}