<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddAdvancedReportsPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // This migration ensures Advanced Reports permissions exist
        // The actual seeding is handled by PermissionSeeder
        
        $permissions = [
            'AdvancedReports.view',
            'AdvancedReports.cash_flow_report',
            'AdvancedReports.purchase_analysis_report'
        ];

        foreach ($permissions as $permission) {
            $existing = DB::table('permissions')
                ->where('name', $permission)
                ->first();

            if (!$existing) {
                DB::table('permissions')->insert([
                    'name' => $permission,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove the specific permissions added by this module
        $permissions = [
            'AdvancedReports.view',
            'AdvancedReports.cash_flow_report',
            'AdvancedReports.purchase_analysis_report'
        ];

        foreach ($permissions as $permission) {
            $permissionRecord = DB::table('permissions')
                ->where('name', $permission)
                ->first();
            
            if ($permissionRecord) {
                // Remove from role_has_permissions
                DB::table('role_has_permissions')
                    ->where('permission_id', $permissionRecord->id)
                    ->delete();
                
                // Remove from model_has_permissions
                DB::table('model_has_permissions')
                    ->where('permission_id', $permissionRecord->id)
                    ->delete();
                
                // Remove permission itself
                DB::table('permissions')
                    ->where('id', $permissionRecord->id)
                    ->delete();
            }
        }
    }
}