<?php

namespace Modules\AdvancedReports\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use DB;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $permissions = [
            // General Access
            [
                'name' => 'AdvancedReports.view',
                'display_name' => 'View Advanced Reports',
                'description' => 'Allow access to Advanced Reports module'
            ],
            [
                'name' => 'AdvancedReports.export',
                'display_name' => 'Export Advanced Reports',
                'description' => 'Allow exporting of Advanced Reports data'
            ],

            // Sales & Revenue Reports
            [
                'name' => 'AdvancedReports.sales_report',
                'display_name' => 'Sales Analytics',
                'description' => 'Access to sales analytics and reports'
            ],
            [
                'name' => 'AdvancedReports.sales_detail_report',
                'display_name' => 'Transaction Details Report',
                'description' => 'Access to detailed transaction reports'
            ],
            [
                'name' => 'AdvancedReports.customer_monthly_sales',
                'display_name' => 'Customer Performance Report',
                'description' => 'Access to customer monthly sales reports'
            ],
            [
                'name' => 'AdvancedReports.itemwise_sales_report',
                'display_name' => 'Product Sales Analysis',
                'description' => 'Access to itemwise sales reports'
            ],
            [
                'name' => 'AdvancedReports.daily_report',
                'display_name' => 'Daily Operations Report',
                'description' => 'Access to daily operations reports'
            ],
            [
                'name' => 'AdvancedReports.daily_summary_report',
                'display_name' => 'Daily Dashboard Report',
                'description' => 'Access to daily summary dashboard'
            ],

            // Product & Inventory Reports
            [
                'name' => 'AdvancedReports.stock_report',
                'display_name' => 'Stock Management Report',
                'description' => 'Access to stock management reports'
            ],
            [
                'name' => 'AdvancedReports.product_report',
                'display_name' => 'Product Performance Report',
                'description' => 'Access to product performance reports'
            ],
            [
                'name' => 'AdvancedReports.brand_monthly_sales',
                'display_name' => 'Brand Analytics Report',
                'description' => 'Access to brand monthly sales reports'
            ],
            [
                'name' => 'AdvancedReports.brand_wise_sales',
                'display_name' => 'Brand Comparison Report',
                'description' => 'Access to brand-wise sales reports'
            ],
            [
                'name' => 'AdvancedReports.supplier_monthly_sales',
                'display_name' => 'Supplier Performance Report',
                'description' => 'Access to supplier monthly sales reports'
            ],
            [
                'name' => 'AdvancedReports.supplier_wise_sales',
                'display_name' => 'Supplier Analysis Report',
                'description' => 'Access to supplier-wise sales reports'
            ],
            [
                'name' => 'AdvancedReports.supplier_stock_movement',
                'display_name' => 'Supplier Profitability Report',
                'description' => 'Access to supplier stock movement and profit reports'
            ],

            // Financial & Tax Reports
            [
                'name' => 'AdvancedReports.profit_loss_report',
                'display_name' => 'Profit & Loss Analysis',
                'description' => 'Access to profit and loss analysis reports'
            ],
            [
                'name' => 'AdvancedReports.cash_flow_report',
                'display_name' => 'Cash Flow Analysis',
                'description' => 'Access to cash flow analysis and forecasting reports'
            ],
            [
                'name' => 'AdvancedReports.purchase_analysis_report',
                'display_name' => 'Purchase Analysis',
                'description' => 'Access to purchase analysis and supplier optimization reports'
            ],
            [
                'name' => 'AdvancedReports.customer_lifetime_value',
                'display_name' => 'Customer Lifetime Value (CLV)',
                'description' => 'Access to customer lifetime value analysis, segmentation, and churn prediction'
            ],
            [
                'name' => 'AdvancedReports.gst_sales_report',
                'display_name' => 'GST Sales Compliance Report',
                'description' => 'Access to GST sales compliance reports'
            ],
            [
                'name' => 'AdvancedReports.gst_purchase_report',
                'display_name' => 'GST Purchase Compliance Report',
                'description' => 'Access to GST purchase compliance reports'
            ],
            [
                'name' => 'AdvancedReports.expense_monthly_report',
                'display_name' => 'Monthly Expenses Report',
                'description' => 'Access to monthly expense reports'
            ],
            [
                'name' => 'AdvancedReports.operations_summary_report',
                'display_name' => 'Business Operations Summary',
                'description' => 'Access to business operations summary reports'
            ],

            // Recognition & Staff Management
            [
                'name' => 'AdvancedReports.customer_recognition_system',
                'display_name' => 'Customer Loyalty Program',
                'description' => 'Access to customer recognition and loyalty system'
            ],
            [
                'name' => 'AdvancedReports.service_staff_recognition_system',
                'display_name' => 'Staff Performance Management',
                'description' => 'Access to service staff recognition system'
            ],
        ];

        foreach ($permissions as $permission) {
            $existing = DB::table('permissions')
                ->where('name', $permission['name'])
                ->first();

            if (!$existing) {
                DB::table('permissions')->insert([
                    'name' => $permission['name'],
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                echo "✅ Created permission: " . $permission['name'] . "\n";
            } else {
                echo "✅ Permission already exists: " . $permission['name'] . "\n";
            }
        }

        // Assign all permissions to Admin role if it exists
        $adminRole = DB::table('roles')->where('name', 'Admin')->first();
        
        if ($adminRole) {
            echo "\n🔑 Assigning permissions to Admin role...\n";
            
            foreach ($permissions as $permission) {
                $permissionRecord = DB::table('permissions')
                    ->where('name', $permission['name'])
                    ->first();
                
                if ($permissionRecord) {
                    $existing = DB::table('role_has_permissions')
                        ->where('permission_id', $permissionRecord->id)
                        ->where('role_id', $adminRole->id)
                        ->first();
                    
                    if (!$existing) {
                        DB::table('role_has_permissions')->insert([
                            'permission_id' => $permissionRecord->id,
                            'role_id' => $adminRole->id
                        ]);
                    }
                }
            }
            
            echo "✅ All permissions assigned to Admin role\n";
        }

        // Assign basic permissions to admin user (ID: 1) as fallback
        $adminUser = DB::table('users')->where('id', 1)->first();
        
        if ($adminUser) {
            echo "\n👤 Assigning basic permissions to admin user...\n";
            
            $basicPermissions = [
                'AdvancedReports.view',
                'AdvancedReports.cash_flow_report', 
                'AdvancedReports.purchase_analysis_report',
                'AdvancedReports.customer_lifetime_value'
            ];
            
            foreach ($basicPermissions as $permissionName) {
                $permissionRecord = DB::table('permissions')
                    ->where('name', $permissionName)
                    ->first();
                
                if ($permissionRecord) {
                    $existing = DB::table('model_has_permissions')
                        ->where('permission_id', $permissionRecord->id)
                        ->where('model_id', $adminUser->id)
                        ->where('model_type', 'App\\User')
                        ->first();
                    
                    if (!$existing) {
                        DB::table('model_has_permissions')->insert([
                            'permission_id' => $permissionRecord->id,
                            'model_type' => 'App\\User',
                            'model_id' => $adminUser->id
                        ]);
                    }
                }
            }
            
            echo "✅ Basic permissions assigned to admin user\n";
        }

        echo "\n🎉 Advanced Reports permissions seeding completed!\n";
    }
}