<?php

namespace Modules\AdvancedReports\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use App\System;
use Module;

class ModuleStatusController extends Controller
{
    /**
     * Get module version information in proper format
     */
    public function getVersionInfo()
    {
        try {
            $version = System::getProperty('advancedreports_version');
            $availableVersion = config('advancedreports.module_version', '1.1.4');

            return response()->json([
                'success' => true,
                'version' => [
                    'installed_version' => $version ?: 'Not installed',
                    'available_version' => $availableVersion,
                    'is_update_available' => version_compare($availableVersion, $version, '>')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve version information',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get module status
     */
    public function getStatus()
    {
        try {
            $isInstalled = !empty(System::getProperty('advancedreports_version'));
            $module = Module::find('AdvancedReports');
            $isEnabled = $module ? $module->isEnabled() : false;

            return response()->json([
                'success' => true,
                'status' => [
                    'installed' => $isInstalled,
                    'enabled' => $isEnabled,
                    'name' => 'Advanced Reports',
                    'description' => 'Comprehensive reporting module with 40+ professional reports'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve module status',
                'error' => $e->getMessage()
            ]);
        }
    }
}