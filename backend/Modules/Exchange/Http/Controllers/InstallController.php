<?php

namespace Modules\Exchange\Http\Controllers;

use App\System;

use Carbon\Exceptions\Exception;
use Composer\Semver\Comparator;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Log;

class InstallController extends Controller
{
    public function __construct()
    {
        $this->module_name = 'Exchange';
        $this->appVersion = config('exchange.module_version');
        $this->module_display_name = 'Exchange';
    }

    /**
     * Install
     *
     * @return Response
     */
    public function index()
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '512M');

        $this->installSettings();
        //System::addProperty($this->module_name.'_version', $this->appVersion);

        //Check if installed or not.
        $is_installed = System::getProperty($this->module_name . '_version');
        if (empty($is_installed)) {
            try {
                DB::statement('SET default_storage_engine=INNODB;');

                // Debug: Check before migration
                \Log::info('About to run migration');

                Artisan::call('module:migrate', ['module' => 'Exchange', '--force' => true]);

                // Debug: Check after migration
                \Log::info('Migration completed');

                // Artisan::call('module:publish', ['module' => 'Exchange']);
                System::addProperty($this->module_name . '_version', $this->appVersion);
                // DB::commit();
            } catch (\Exception $e) {
                \Log::error('Installation error: ' . $e->getMessage());
                throw $e;
            }
        }

        $output = [
            'success' => 1,
            'msg' => 'Exchange module installed succesfully',
        ];

        return redirect()
            ->action([\App\Http\Controllers\Install\ModulesController::class, 'index'])
            ->with('status', $output);
    }

    /**
     * Initialize all install functions
     */
    private function installSettings()
    {
        config(['app.debug' => true]);
        Artisan::call('config:clear');
    }

    // Updating
    public function update()
    {
        //Check if Exchange_version is same as appVersion then 404
        //If appVersion > Exchange_version - run update script.
        //Else there is some problem.
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            ini_set('max_execution_time', 0);
            ini_set('memory_limit', '512M');

            $Exchange_version = System::getProperty($this->module_name . '_version');

            if (Comparator::greaterThan($this->appVersion, $Exchange_version)) {
                ini_set('max_execution_time', 0);
                ini_set('memory_limit', '512M');
                $this->installSettings();

                DB::statement('SET default_storage_engine=INNODB;');
                Artisan::call('module:migrate', ['module' => 'Exchange', '--force' => true]);
                // Artisan::call('module:publish', ['module' => 'Exchange']);

                System::setProperty($this->module_name . '_version', $this->appVersion);
            } else {
                abort(404);
            }

            DB::commit();

            $output = [
                'success' => 1,
                'msg' => 'Exchange module updated Succesfully to version ' . $this->appVersion . ' !!',
            ];

            return redirect()
                ->action([\App\Http\Controllers\Install\ModulesController::class, 'index'])
                ->with('status', $output);
        } catch (Exception $e) {
            DB::rollBack();
            exit($e->getMessage());
        }
    }

    /**
     * Uninstall
     *
     * @return Response
     */
    public function uninstall()
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            System::removeProperty($this->module_name . '_version');

            $output = [
                'success' => true,
                'msg' => __('exchange::lang.success'),
            ];
        } catch (\Exception $e) {
            $output = [
                'success' => false,
                'msg' => $e->getMessage(),
            ];
        }

        return redirect()->back()->with(['status' => $output]);
    }
}
