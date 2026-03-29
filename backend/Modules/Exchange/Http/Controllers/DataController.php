<?php

namespace Modules\Exchange\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Utils\ModuleUtil;
use Menu;

class DataController extends Controller
{
    /**
     * Defines module as a superadmin package.
     * @return Array
     */
    public function superadmin_package()
    {
        return [
            [
                'name' => 'Exchange',
                'label' => __('Exchange::Exchange.Exchange_module'),
                'default' => false
            ]
        ];
    }

    /**
     * Defines user permissions for the module.
     * @return array
     */
    public function user_permissions()
    {
        return [
            // General Access
            [
                'value' => 'exchange.access',
                'label' => __('exchange::lang.access_exchange_module'),
                'default' => false
            ],
            [
                'value' => 'exchange.view',
                'label' => __('exchange::lang.view_exchanges'),
                'default' => false
            ],
            
            // Exchange Operations
            [
                'value' => 'exchange.create',
                'label' => __('exchange::lang.create_exchange'),
                'default' => false
            ],
            [
                'value' => 'exchange.cancel',
                'label' => __('exchange::lang.cancel_exchange'),
                'default' => false
            ],
            [
                'value' => 'exchange.delete',
                'label' => __('exchange::lang.delete_exchange'),
                'default' => false
            ],
            
            // Reporting & Export
            [
                'value' => 'exchange.export',
                'label' => __('exchange::lang.export_exchange_data'),
                'default' => false
            ],
            [
                'value' => 'exchange.print',
                'label' => __('exchange::lang.print_exchange_receipt'),
                'default' => false
            ],
        ];
    }



    public function modifyAdminMenu()
    {
        $business_id = session()->get('user.business_id');
        $module_util = new ModuleUtil();
        
        // Fixed: Changed module name to match superadmin_package definition
        $is_exchange_enabled = (bool)$module_util->hasThePermissionInSubscription($business_id, 'Exchange', 'superadmin_package');

        // Fixed: Simplified permission check - removed 'superadmin' check that was blocking regular users
        if ($is_exchange_enabled && auth()->user()->can('exchange.access')) {
            $menuparent = Menu::instance('admin-sidebar-menu');

            $menuparent->url(
                action([\Modules\Exchange\Http\Controllers\ExchangeController::class, 'index']),
                __('exchange::lang.exchange'),
                [
                    'icon' => 'fa fas fa-exchange-alt',
                    'active' => request()->segment(1) == 'exchange',
                    // 'style' => 'background-color:rgb(255, 117, 200) !important;'
                ]
            )->order(31);
        }
    }
}
