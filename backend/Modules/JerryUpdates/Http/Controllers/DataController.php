<?php

namespace Modules\JerryUpdates\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Utils\ModuleUtil;
use Menu;

class DataController extends Controller
{
    public function modifyAdminMenu()
    {
        $module_util = new ModuleUtil();

        if ($module_util->isModuleInstalled('JerryUpdates')) {
            $business_id = session()->get('business.id');
            
            if (auth()->user()->hasRole('Admin#' . $business_id) || auth()->user()->id == 1) {
                Menu::modify('admin-sidebar-menu', function ($menu) {
                    $menu->url(
                        action([\Modules\JerryUpdates\Http\Controllers\JerryUpdatesController::class, 'index']),
                        'Just Tweaks',
                        [
                            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="tw-size-5 tw-shrink-0" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
  <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
  <path d="M4 7h16" />
  <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
  <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
  <path d="M10 12l4 4m0 -4l-4 4" />
</svg>',
                            'active' => request()->segment(1) == 'jerryupdates'
                        ]
                    )->order(100);
                });
            }
        }
    }

    public function get_additional_script()
    {
        $additional_html = '';

        $dark_mode_setting = \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_dark_mode') ?? '0';

        // Inverted Theme (filter: invert hack)
        if ($dark_mode_setting === 'inverted') {
            $additional_html .= '
<style>
    body:not(.login-page) {
        filter: invert(100%) hue-rotate(180deg) brightness(90%) contrast(90%) !important;
        background: #fff !important; 
    }
    body:not(.login-page) img, body:not(.login-page) video, body:not(.login-page) iframe, body:not(.login-page) canvas, body:not(.login-page) svg {
        filter: invert(100%) hue-rotate(180deg) !important;
    }
    body:not(.login-page) main > div.tw-bg-gradient-to-r, body:not(.login-page) aside.side-bar > a {
        filter: invert(100%) hue-rotate(180deg) !important;
    }
    body:not(.login-page) main > div.tw-bg-gradient-to-r svg, body:not(.login-page) aside.side-bar > a svg {
        filter: none !important;
    }
    body:not(.login-page) .wrapper, body:not(.login-page) .content-wrapper, body:not(.login-page) main {
        background-color: #f4f6f9 !important;
    }
</style>';
        }

        // Normal Dark Theme (Apple iOS Dark Mode overrides)
        if ($dark_mode_setting === 'normal' || $dark_mode_setting === '1') {
            $additional_html .= '
<style>
    /* ===== AGGRESSIVE APPLE IOS RESET ===== */
    html { background-color: #000000 !important; }
    body:not(.login-page) { background-color: #000000 !important; color: #ffffff !important; }
    
    body:not(.login-page) .wrapper, 
    body:not(.login-page) .content-wrapper, 
    body:not(.login-page) main {
        background-color: #000000 !important;
        color: #ffffff !important;
    }

    /* ===== Nuke Tailwind Light Colors Globally ===== */
    body:not(.login-page) .tw-bg-white,
    body:not(.login-page) .bg-white,
    body:not(.login-page) .tw-bg-gray-50,
    body:not(.login-page) .tw-bg-gray-100,
    body:not(.login-page) .tw-bg-gray-200 {
        background-color: #1c1c1e !important;
        color: #ffffff !important;
        border-color: #3a3a3c !important;
    }
    
    body:not(.login-page) .hover\:tw-bg-gray-50:hover,
    body:not(.login-page) .hover\:tw-bg-gray-100:hover,
    body:not(.login-page) .hover\:tw-bg-gray-200:hover {
        background-color: #3a3a3c !important;
        color: #0a84ff !important;
    }

    body:not(.login-page) .tw-text-gray-500,
    body:not(.login-page) .tw-text-gray-600,
    body:not(.login-page) .tw-text-gray-700,
    body:not(.login-page) .tw-text-gray-800,
    body:not(.login-page) .tw-text-gray-900,
    body:not(.login-page) .text-muted,
    body:not(.login-page) .tw-text-black {
        color: #98989d !important;
    }

    body:not(.login-page) .hover\:tw-text-gray-800:hover,
    body:not(.login-page) .hover\:tw-text-gray-900:hover,
    body:not(.login-page) .hover\:tw-text-black:hover {
        color: #ffffff !important;
    }

    /* ===== HEADER & DROPDOWNS ===== */
    body:not(.login-page) .main-header .navbar, 
    body:not(.login-page) header, 
    body:not(.login-page) nav,
    body:not(.login-page) main > div[class*="tw-bg-gradient-to-r"] {
        background: linear-gradient(to right, #1c1c1e, #000000) !important;
        border-bottom: 1px solid #3a3a3c !important;
    }

    body:not(.login-page) .dropdown-menu,
    body:not(.login-page) .tw-dw-dropdown-content,
    body:not(.login-page) .tw-dw-menu {
        background-color: #1c1c1e !important;
        border: 1px solid #38383a !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.5) !important;
    }

    body:not(.login-page) .dropdown-menu ul,
    body:not(.login-page) .dropdown-menu li,
    body:not(.login-page) .tw-dw-menu div,
    body:not(.login-page) .dropdown-menu div {
        background-color: transparent !important;
    }

    body:not(.login-page) .dropdown-menu a,
    body:not(.login-page) .tw-dw-menu a {
        color: #98989d !important;
        background-color: transparent !important;
    }

    body:not(.login-page) .dropdown-menu a:hover,
    body:not(.login-page) .dropdown-menu a:focus,
    body:not(.login-page) .tw-dw-menu a:hover {
        background-color: #3a3a3c !important;
        color: #0a84ff !important;
    }

    /* ===== SIDEBAR ===== */
    body:not(.login-page) aside.side-bar, 
    body:not(.login-page) .main-sidebar, 
    body:not(.login-page) .sidebar {
        background-color: #000000 !important;
        border-right: 1px solid #3a3a3c !important;
    }
    
    body:not(.login-page) .sidebar a {
        color: #98989d !important;
        background-color: transparent !important;
    }

    body:not(.login-page) .sidebar a:hover,
    body:not(.login-page) .sidebar-menu > li.active > a,
    body:not(.login-page) .sidebar-menu > li.menu-open > a,
    body:not(.login-page) .sidebar-menu > li > a:hover {
        background-color: #1c1c1e !important;
        color: #ffffff !important;
    }

    body:not(.login-page) .sidebar .treeview-menu {
        background-color: #000000 !important;
    }
    
    body:not(.login-page) .sidebar .treeview-menu > li > a {
        color: #98989d !important;
        background-color: transparent !important;
    }

    body:not(.login-page) .sidebar .treeview-menu > li.active > a,
    body:not(.login-page) .sidebar .treeview-menu > li > a:hover {
        background-color: #1c1c1e !important;
        color: #ffffff !important;
    }

    /* ===== CARDS, PANELS, WIDGETS ===== */
    body:not(.login-page) .box, 
    body:not(.login-page) .box-solid, 
    body:not(.login-page) .card, 
    body:not(.login-page) .panel,
    body:not(.login-page) .well {
        background-color: #1c1c1e !important;
        border: 1px solid #3a3a3c !important;
    }
    
    body:not(.login-page) .box-header, 
    body:not(.login-page) .panel-heading {
        background-color: #1c1c1e !important;
        border-bottom: 1px solid #3a3a3c !important;
        color: #ffffff !important;
    }

    body:not(.login-page) .box-body, 
    body:not(.login-page) .panel-body {
        background-color: #1c1c1e !important;
    }

    body:not(.login-page) .box-footer, 
    body:not(.login-page) .panel-footer {
        background-color: #000000 !important;
        border-top: 1px solid #3a3a3c !important;
    }

    /* ===== TABLES ===== */
    body:not(.login-page) .table {
        background-color: #1c1c1e !important;
        color: #ffffff !important;
    }
    
    body:not(.login-page) .table > thead > tr > th,
    body:not(.login-page) .table > tbody > tr > td,
    body:not(.login-page) .table > tfoot > tr > td,
    body:not(.login-page) .table-bordered,
    body:not(.login-page) .table-bordered > thead > tr > th,
    body:not(.login-page) .table-bordered > tbody > tr > td {
        border-color: #3a3a3c !important;
    }

    body:not(.login-page) .table > thead > tr > th {
        background-color: #000000 !important;
        color: #98989d !important;
    }

    body:not(.login-page) .table-striped > tbody > tr:nth-of-type(odd) {
        background-color: #1c1c1e !important;
    }

    body:not(.login-page) .table-hover > tbody > tr:hover,
    body:not(.login-page) .table-striped > tbody > tr:hover {
        background-color: #2c2c2e !important;
    }

    /* ===== FORMS ===== */
    body:not(.login-page) .form-control,
    body:not(.login-page) select,
    body:not(.login-page) textarea,
    body:not(.login-page) input {
        background-color: #000000 !important;
        border-color: #38383a !important;
        color: #ffffff !important;
    }

    body:not(.login-page) .form-control:focus {
        border-color: #0a84ff !important;
        box-shadow: 0 0 0 1px #0a84ff !important;
    }

    body:not(.login-page) .input-group-addon {
        background-color: #1c1c1e !important;
        border-color: #38383a !important;
        color: #98989d !important;
    }

    /* ===== SELECT2 DROPDOWNS ===== */
    body:not(.login-page) .select2-container--default .select2-selection--single,
    body:not(.login-page) .select2-container--default .select2-selection--multiple {
        background-color: #000000 !important;
        border-color: #38383a !important;
    }
    body:not(.login-page) .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #ffffff !important;
    }
    body:not(.login-page) .select2-dropdown {
        background-color: #1c1c1e !important;
        border-color: #38383a !important;
    }
    body:not(.login-page) .select2-container--default .select2-results__option {
        background-color: #1c1c1e !important;
        color: #ffffff !important;
    }
    body:not(.login-page) .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #3a3a3c !important;
        color: #0a84ff !important;
    }

    /* ===== MODALS ===== */
    body:not(.login-page) .modal-content {
        background-color: #1c1c1e !important;
        border-color: #38383a !important;
    }
    body:not(.login-page) .modal-header {
        border-bottom-color: #38383a !important;
    }
    body:not(.login-page) .modal-footer {
        border-top-color: #38383a !important;
    }

    /* ===== DATATABLES INFO ===== */
    body:not(.login-page) .dataTables_wrapper .dataTables_info,
    body:not(.login-page) .dataTables_wrapper .dataTables_length,
    body:not(.login-page) .dataTables_wrapper .dataTables_filter {
        color: #98989d !important;
    }
    /* ===== POS TABS / SETTINGS NAVIGATION ===== */
    body:not(.login-page) .pos-tab-container {
        background-color: transparent !important;
    }
    body:not(.login-page) .pos-tab-menu {
        background-color: transparent !important;
    }
    body:not(.login-page) .list-group {
        background-color: transparent !important;
    }
    body:not(.login-page) .list-group-item {
        background-color: #1c1c1e !important;
        color: #98989d !important;
        border-color: #3a3a3c !important;
    }
    body:not(.login-page) .list-group-item.active,
    body:not(.login-page) .list-group-item.active:hover,
    body:not(.login-page) .list-group-item.active:focus {
        background-color: #3a3a3c !important;
        color: #ffffff !important;
        border-color: #0a84ff !important;
        border-left: 4px solid #0a84ff !important;
    }
    body:not(.login-page) .list-group-item:hover {
        background-color: #2c2c2e !important;
        color: #0a84ff !important;
    }
    body:not(.login-page) .pos-tab,
    body:not(.login-page) .pos-tab-content {
        background-color: #1c1c1e !important;
        border: 1px solid #3a3a3c !important;
        color: #ffffff !important;
    }
    body:not(.login-page) .nav-tabs-custom {
        background-color: #1c1c1e !important;
        border-color: #3a3a3c !important;
    }
    body:not(.login-page) .nav-tabs-custom > .nav-tabs {
        border-bottom-color: #3a3a3c !important;
    }
    body:not(.login-page) .nav-tabs-custom > .nav-tabs > li > a {
        color: #98989d !important;
    }
    body:not(.login-page) .nav-tabs-custom > .nav-tabs > li.active > a {
        background-color: #1c1c1e !important;
        color: #ffffff !important;
        border-color: #3a3a3c !important;
        border-bottom-color: transparent !important;
    }
    body:not(.login-page) .nav-tabs-custom > .tab-content {
        background-color: #1c1c1e !important;
    }

    /* ===== DATATABLES EXTRAS (DT-BUTTONS, FILTERS, HEADERS) ===== */
    body:not(.login-page) .dt-buttons .btn,
    body:not(.login-page) .dt-button-collection,
    body:not(.login-page) .dt-button-collection .dt-button {
        background-color: #1c1c1e !important;
        color: #ffffff !important;
        border-color: #38383a !important;
    }
    /* ===== NUCLEAR CATCH-ALL FOR ANY WHITE BACKGROUNDS ===== */
    body:not(.login-page) [style*="background-color: white"],
    body:not(.login-page) [style*="background-color: #fff"],
    body:not(.login-page) [style*="background-color:#fff"],
    body:not(.login-page) [style*="background: white"],
    body:not(.login-page) [style*="background: #fff"],
    body:not(.login-page) [class*="bg-white"],
    body:not(.login-page) [class*="tw-bg-white"],
    body:not(.login-page) .bg-white {
        background-color: #000000 !important;
        color: #ffffff !important;
    }

    /* ===== 3RD PARTY WIDGETS (DATEPICKERS, SWEETALERTS, POPOVERS) ===== */
    body:not(.login-page) .daterangepicker,
    body:not(.login-page) .daterangepicker .calendar-table,
    body:not(.login-page) .daterangepicker td,
    body:not(.login-page) .daterangepicker th,
    body:not(.login-page) .datepicker,
    body:not(.login-page) .datepicker table tr td,
    body:not(.login-page) .datepicker table tr th {
        background-color: #000000 !important;
        color: #ffffff !important;
        border-color: #38383a !important;
    }
    body:not(.login-page) .daterangepicker td.in-range {
        background-color: #1c1c1e !important;
    }
    body:not(.login-page) .daterangepicker td.active, 
    body:not(.login-page) .daterangepicker td.active:hover {
        background-color: #0a84ff !important;
        color: #ffffff !important;
    }
    body:not(.login-page) .swal-modal,
    body:not(.login-page) .swal2-popup,
    body:not(.login-page) .popover,
    body:not(.login-page) .popover-content,
    body:not(.login-page) .popover-title,
    body:not(.login-page) .toast,
    body:not(.login-page) #toast-container > div {
        background-color: #1c1c1e !important;
        color: #ffffff !important;
        border-color: #38383a !important;
    }
    
    /* ===== ADMINLTE INFO BOXES ===== */
    body:not(.login-page) .info-box,
    body:not(.login-page) .small-box {
        background-color: #1c1c1e !important;
        color: #ffffff !important;
    }
    body:not(.login-page) .info-box-content {
        color: #ffffff !important;
    }

    body:not(.login-page) .dt-button-collection .dt-button:hover {
        background-color: #3a3a3c !important;
    }
    body:not(.login-page) .dataTables_wrapper .row {
        background-color: transparent !important;
    }
    body:not(.login-page) .dataTables_wrapper {
        background-color: transparent !important;
    }
    body:not(.login-page) div.dataTables_wrapper div.dataTables_filter {
        background-color: transparent !important;
    }
    body:not(.login-page) div.dataTables_wrapper div.dataTables_length {
        background-color: transparent !important;
    }
    body:not(.login-page) table.dataTable thead .sorting, 
    body:not(.login-page) table.dataTable thead .sorting_asc, 
    body:not(.login-page) table.dataTable thead .sorting_desc {
        background-color: #000000 !important;
        color: #ffffff !important;
    }
    body:not(.login-page) .table-responsive {
        background-color: transparent !important;
    }
    /* Catch-all for any buttons in table headers or wrappers */
    body:not(.login-page) .dataTables_wrapper .btn {
        background-color: #1c1c1e !important;
        color: #ffffff !important;
        border-color: #38383a !important;
    }
    body:not(.login-page) .dataTables_wrapper .btn:hover {
        background-color: #3a3a3c !important;
    }


    /* ===== ULTIMATE BRUTE FORCE: POS TABS & SETTINGS ===== */
    body:not(.login-page) .pos-tab-container,
    body:not(.login-page) .pos-tab-menu,
    body:not(.login-page) .list-group,
    body:not(.login-page) .pos-tab,
    body:not(.login-page) .nav-tabs-custom,
    body:not(.login-page) .tab-content,
    body:not(.login-page) .tab-pane,
    body:not(.login-page) .panel-body,
    body:not(.login-page) .box-body {
        background: #1c1c1e !important;
        background-color: #1c1c1e !important;
        color: #ffffff !important;
    }
    
    body:not(.login-page) .list-group-item {
        background: #1c1c1e !important;
        background-color: #1c1c1e !important;
        border-color: #38383a !important;
        color: #98989d !important;
    }
    
    body:not(.login-page) .list-group-item.active,
    body:not(.login-page) .list-group-item.active:hover,
    body:not(.login-page) .list-group-item.active:focus {
        background: #3a3a3c !important;
        background-color: #3a3a3c !important;
        color: #ffffff !important;
        border-color: #0a84ff !important;
    }

    body:not(.login-page) .list-group-item:hover {
        background: #2c2c2e !important;
        background-color: #2c2c2e !important;
        color: #0a84ff !important;
    }

    /* Target ANY random white background set via inline style or generic class */
    body:not(.login-page) [style*="background: white"],
    body:not(.login-page) [style*="background: #fff"],
    body:not(.login-page) [style*="background-color: white"],
    body:not(.login-page) [style*="background-color: #fff"],
    body:not(.login-page) [style*="background-color:#fff"],
    body:not(.login-page) [style*="background:#fff"],
    body:not(.login-page) .bg-white,
    body:not(.login-page) .tw-bg-white,
    body:not(.login-page) .content-wrapper,
    body:not(.login-page) .wrapper {
        background: #000000 !important;
        background-color: #000000 !important;
    }

    /* ===== ULTIMATE TEXT CONTRAST FIX ===== */
    /* Nuke any inline "color: black" or "color: #333" globally */
    body:not(.login-page) [style*="color: black"],
    body:not(.login-page) [style*="color: rgb(0, 0, 0)"],
    body:not(.login-page) [style*="color: #000"],
    body:not(.login-page) [style*="color:#000"],
    body:not(.login-page) [style*="color: #333"],
    body:not(.login-page) [style*="color:#333"],
    body:not(.login-page) .text-black,
    body:not(.login-page) .text-dark,
    body:not(.login-page) .tw-text-black {
        color: #ffffff !important;
    }
    
    /* Ensure all headings and labels are absolutely white against the dark backgrounds */
    body:not(.login-page) h1, 
    body:not(.login-page) h2, 
    body:not(.login-page) h3, 
    body:not(.login-page) h4, 
    body:not(.login-page) h5, 
    body:not(.login-page) h6, 
    body:not(.login-page) .box-title,
    body:not(.login-page) .panel-title,
    body:not(.login-page) label,
    body:not(.login-page) .control-label,
    body:not(.login-page) p {
        color: #ffffff !important;
    }

    /* Muted helper text should not be pitch black either */
    body:not(.login-page) .help-block,
    body:not(.login-page) .text-muted {
        color: #98989d !important;
    }

    /* ===== THE ULTIMATE DEEP SCAN CATCH-ALL ===== */
    /* Dynamically extracted 159 selectors from compiled CSS */
    body:not(.login-page) .bg-white,
body:not(.login-page) .box,
body:not(.login-page) .box-footer,
body:not(.login-page) .btn-adn .badge,
body:not(.login-page) .btn-bitbucket .badge,
body:not(.login-page) .btn-danger .badge,
body:not(.login-page) .btn-default,
body:not(.login-page) .btn-default.disabled.focus,
body:not(.login-page) .btn-default[disabled].focus,
body:not(.login-page) .btn-dropbox .badge,
body:not(.login-page) .btn-facebook .badge,
body:not(.login-page) .btn-flickr .badge,
body:not(.login-page) .btn-foursquare .badge,
body:not(.login-page) .btn-github .badge,
body:not(.login-page) .btn-google .badge,
body:not(.login-page) .btn-info .badge,
body:not(.login-page) .btn-instagram .badge,
body:not(.login-page) .btn-linkedin .badge,
body:not(.login-page) .btn-microsoft .badge,
body:not(.login-page) .btn-openid .badge,
body:not(.login-page) .btn-pinterest .badge,
body:not(.login-page) .btn-primary .badge,
body:not(.login-page) .btn-soundcloud .badge,
body:not(.login-page) .btn-success .badge,
body:not(.login-page) .btn-tumblr .badge,
body:not(.login-page) .btn-twitter .badge,
body:not(.login-page) .btn-vimeo .badge,
body:not(.login-page) .btn-vk .badge,
body:not(.login-page) .btn-warning .badge,
body:not(.login-page) .btn-yahoo .badge,
body:not(.login-page) .btn.btn-file>input[type=file],
body:not(.login-page) .callout .highlight,
body:not(.login-page) .callout code,
body:not(.login-page) .carousel-indicators .active,
body:not(.login-page) .colorpicker.colorpicker-horizontal .colorpicker-alpha i,
body:not(.login-page) .colorpicker.colorpicker-horizontal .colorpicker-hue i,
body:not(.login-page) .content-wrapper,
body:not(.login-page) .daterangepicker,
body:not(.login-page) .daterangepicker .calendar-table,
body:not(.login-page) .daterangepicker td.off,
body:not(.login-page) .daterangepicker td.off.end-date,
body:not(.login-page) .daterangepicker td.off.in-range,
body:not(.login-page) .daterangepicker td.off.start-date,
body:not(.login-page) .drag_handler,
body:not(.login-page) .dropdown-menu,
body:not(.login-page) .dropzone,
body:not(.login-page) .dropzone .dz-preview.dz-image-preview,
body:not(.login-page) .fc-event .fc-bg,
body:not(.login-page) .fc-h-event.fc-selected .fc-resizer,
body:not(.login-page) .fc-time-grid-event.fc-selected .fc-resizer,
body:not(.login-page) .fc-unthemed .fc-popover,
body:not(.login-page) .form-control,
body:not(.login-page) .img-thumbnail,
body:not(.login-page) .info-box,
body:not(.login-page) .info-box .progress .progress-bar,
body:not(.login-page) .input-group .input-group-addon,
body:not(.login-page) .input-number .btn-default,
body:not(.login-page) .invoice,
body:not(.login-page) .kanban-item,
body:not(.login-page) .list-group-item,
body:not(.login-page) .list-group-item.active>.badge,
body:not(.login-page) .lockscreen-credentials .btn,
body:not(.login-page) .lockscreen-image,
body:not(.login-page) .lockscreen-item,
body:not(.login-page) .login-box-body,
body:not(.login-page) .main-footer,
body:not(.login-page) .modal-content,
body:not(.login-page) .nav-pills>.active>a>.badge,
body:not(.login-page) .nav-tabs-custom,
body:not(.login-page) .nav-tabs-custom>.nav-tabs>li.active>a,
body:not(.login-page) .nav-tabs-custom>.tab-content,
body:not(.login-page) .nav-tabs>li.active>a,
body:not(.login-page) .navbar-custom-menu>.navbar-nav>li>.dropdown-menu,
body:not(.login-page) .navbar-inverse .navbar-toggle .icon-bar,
body:not(.login-page) .navbar-nav>.messages-menu>.dropdown-menu>li.footer>a,
body:not(.login-page) .navbar-nav>.messages-menu>.dropdown-menu>li.header,
body:not(.login-page) .navbar-nav>.notifications-menu>.dropdown-menu>li.footer>a,
body:not(.login-page) .navbar-nav>.notifications-menu>.dropdown-menu>li.header,
body:not(.login-page) .navbar-nav>.tasks-menu>.dropdown-menu>li.footer>a,
body:not(.login-page) .navbar-nav>.tasks-menu>.dropdown-menu>li.header,
body:not(.login-page) .navbar-nav>.user-menu>.dropdown-menu>.user-body a,
body:not(.login-page) .pace .pace-progress,
body:not(.login-page) .pager .disabled>a,
body:not(.login-page) .pager .disabled>span,
body:not(.login-page) .pager li>a,
body:not(.login-page) .pager li>span,
body:not(.login-page) .pagination>.disabled>a,
body:not(.login-page) .pagination>.disabled>span,
body:not(.login-page) .pagination>li>a,
body:not(.login-page) .pagination>li>span,
body:not(.login-page) .panel,
body:not(.login-page) .panel-primary>.panel-heading .badge,
body:not(.login-page) .patt-dots,
body:not(.login-page) .popover,
body:not(.login-page) .pos-tab,
body:not(.login-page) .pos-tab-content,
body:not(.login-page) .product_box,
body:not(.login-page) .products-list>.item,
body:not(.login-page) .register-box-body,
body:not(.login-page) .select2-close-mask,
body:not(.login-page) .select2-container--classic .select2-dropdown,
body:not(.login-page) .select2-container--classic .select2-selection--multiple,
body:not(.login-page) .select2-container--default .select2-selection--multiple,
body:not(.login-page) .select2-container--default .select2-selection--single,
body:not(.login-page) .select2-dropdown,
body:not(.login-page) .skin-black .main-header .logo,
body:not(.login-page) .skin-black .main-header .navbar,
body:not(.login-page) .skin-black .main-header .navbar .nav .open>a,
body:not(.login-page) .skin-black .main-header .navbar .nav>.active>a,
body:not(.login-page) .skin-black-light .main-header .logo,
body:not(.login-page) .skin-black-light .main-header .navbar,
body:not(.login-page) .skin-black-light .main-header .navbar .nav .open>a,
body:not(.login-page) .skin-black-light .main-header .navbar .nav>.active>a,
body:not(.login-page) .skin-black-light .main-sidebar,
body:not(.login-page) .skin-black-light .sidebar-form .btn,
body:not(.login-page) .skin-black-light .sidebar-form input[type=text],
body:not(.login-page) .skin-blue-light .main-sidebar,
body:not(.login-page) .skin-blue-light .sidebar-form .btn,
body:not(.login-page) .skin-blue-light .sidebar-form input[type=text],
body:not(.login-page) .skin-green-light .main-sidebar,
body:not(.login-page) .skin-green-light .sidebar-form .btn,
body:not(.login-page) .skin-green-light .sidebar-form input[type=text],
body:not(.login-page) .skin-purple-light .main-sidebar,
body:not(.login-page) .skin-purple-light .sidebar-form .btn,
body:not(.login-page) .skin-purple-light .sidebar-form input[type=text],
body:not(.login-page) .skin-red-light .main-sidebar,
body:not(.login-page) .skin-red-light .sidebar-form .btn,
body:not(.login-page) .skin-red-light .sidebar-form input[type=text],
body:not(.login-page) .skin-yellow-light .main-sidebar,
body:not(.login-page) .skin-yellow-light .sidebar-form .btn,
body:not(.login-page) .skin-yellow-light .sidebar-form input[type=text],
body:not(.login-page) .swal-modal,
body:not(.login-page) .switch-ios.switch-light a,
body:not(.login-page) .tabcontrol>.steps>ul>li.current,
body:not(.login-page) .table .table,
body:not(.login-page) .table td,
body:not(.login-page) .table th,
body:not(.login-page) .tagify__dropdown__wrapper,
body:not(.login-page) .thumbnail,
body:not(.login-page) .timeline>.time-label>span,
body:not(.login-page) .timeline>li>.timeline-item,
body:not(.login-page) .tw-bg-white,
body:not(.login-page) .ui-icon-background,
body:not(.login-page) .ui-state-active .ui-icon-background,
body:not(.login-page) .ui-widget-content,
body:not(.login-page) body,
body:not(.login-page) div.DTFC_Blocker,
body:not(.login-page) div.DTFC_LeftFootWrapper table,
body:not(.login-page) div.DTFC_LeftHeadWrapper table,
body:not(.login-page) div.DTFC_RightFootWrapper table,
body:not(.login-page) div.DTFC_RightHeadWrapper table,
body:not(.login-page) div.dt-autofill-list,
body:not(.login-page) div.dt-button-info,
body:not(.login-page) div.pos-tab-container,
body:not(.login-page) div.pos-tab-content,
body:not(.login-page) fieldset[disabled] .btn-default.focus,
body:not(.login-page) table.DTFC_Cloned tr,
body:not(.login-page) table.dataTable.fixedHeader-floating,
body:not(.login-page) table.dataTable.fixedHeader-locked {
        background-color: #1c1c1e !important;
        color: #ffffff !important;
    }
    

    /* Tailwind Text & Background Compatibility Overrides for JerryUpdates Dashboard */
    .tw-text-gray-900, .tw-text-gray-800, .tw-text-gray-700, .tw-text-gray-600, .tw-text-indigo-600 {
        color: #f3f4f6 !important;
    }
    .tw-text-gray-500, .tw-text-gray-400 {
        color: #d1d5db !important;
    }
    .tw-bg-gray-50, .tw-bg-gray-100, .tw-bg-gray-200, .tw-bg-blue-50, .tw-bg-blue-100, .tw-bg-green-50, .tw-bg-green-100, .tw-bg-red-50, .tw-bg-red-100, .tw-bg-yellow-50, .tw-bg-yellow-100, .tw-bg-amber-100, .tw-bg-amber-50, .tw-bg-indigo-100, .tw-bg-indigo-50, .tw-bg-purple-100, .tw-bg-purple-50, .tw-bg-teal-100, .tw-bg-teal-50 {
        background-color: #2c2c2e !important;
        color: #f3f4f6 !important;
        border-color: #3a3a3c !important;
    }
    .tw-border-gray-200, .tw-border-gray-300 {
        border-color: #3a3a3c !important;
    }
    .tw-text-green-700, .tw-text-green-800 {
        color: #4ade80 !important;
    }
    .tw-text-red-700, .tw-text-red-800 {
        color: #f87171 !important;
    }
    .tw-text-blue-700, .tw-text-blue-800 {
        color: #60a5fa !important;
    }
    .tw-text-yellow-700, .tw-text-amber-800 {
        color: #fbbf24 !important;
    }
    .tw-text-indigo-800, .tw-text-purple-700, .tw-text-purple-800 {
        color: #a78bfa !important;
    }

    /* Stronger Border Pass */
    body:not(.login-page) .box,
    body:not(.login-page) .nav-tabs-custom,
    body:not(.login-page) .table-bordered,
    body:not(.login-page) .table-bordered>thead>tr>th, 
    body:not(.login-page) .table-bordered>tbody>tr>th, 
    body:not(.login-page) .table-bordered>tfoot>tr>th, 
    body:not(.login-page) .table-bordered>thead>tr>td, 
    body:not(.login-page) .table-bordered>tbody>tr>td, 
    body:not(.login-page) .table-bordered>tfoot>tr>td {
        border-color: #38383a !important;
    }
</style>';
        }

        // Add missing Tailwind CSS classes for the native black and black-light themes
        $additional_html .= '
<style>
/* Black Theme Fixes */
.tw-from-black-800 {
    --tw-gradient-from: #21243d !important;
    --tw-gradient-to: rgba(33, 36, 61, 0) !important;
    --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to) !important;
}
.tw-to-black-900 {
    --tw-gradient-to: #1d1f33 !important;
}
.tw-bg-black-800 { background-color: #21243d !important; }
.hover\:tw-bg-black-700:hover { background-color: #2a2e4d !important; }

/* Black-Light Theme Fixes */
.tw-from-black-light-800 {
    --tw-gradient-from: #222d32 !important;
    --tw-gradient-to: rgba(34, 45, 50, 0) !important;
    --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to) !important;
}
.tw-to-black-light-900 {
    --tw-gradient-to: #1a2226 !important;
}
.tw-bg-black-light-800 { background-color: #222d32 !important; }
.hover\:tw-bg-black-light-700:hover { background-color: #2e3c42 !important; }
</style>';


        $js_html = '';

        // JerryUpdates: Custom Views JavaScript Migration Engine
        // -------------------------------------------------------------
        
        return [
            'additional_css'  => $additional_html,
            'additional_views' => ['jerryupdates::javascript_tweaks', 'jerryupdates::offline_mode']
        ];
    }
}
