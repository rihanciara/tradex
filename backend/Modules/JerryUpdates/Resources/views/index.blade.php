@extends('layouts.app')
{{-- Dual-Mode Dashboard Active --}}

@section('title', 'Just Tweaks Dashboard')


@section('content')

@section('css')
<style>
    .jerry-premium-card {
        transition: all 0.3s ease;
        border-top: 4px solid #4f46e5;
    }
    .jerry-premium-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    }
    .jerry-header-gradient {
        background: linear-gradient(135deg, #1e1b4b 0%, #4338ca 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
</style>
@endsection

<section class="content-header">
    <h1>
        <span class="tw-text-2xl tw-font-extrabold jerry-header-gradient tw-tracking-tight">
            <i class="fas fa-puzzle-piece tw-mr-2 tw-text-indigo-600"></i> Just Tweaks
        </span>
        <small class="tw-text-base tw-text-gray-500">Module Dashboard</small>
    </h1>
</section>

<section class="content">
    {{-- Tab Navigation --}}
    <div class="tw-mb-6">
        <ul class="nav nav-tabs tw-border-b-0" role="tablist">
            <li role="presentation" class="active">
                <a href="#dashboard_tab" aria-controls="dashboard_tab" role="tab" data-toggle="tab" class="tw-rounded-t-lg tw-font-semibold">
                    <i class="fas fa-desktop tw-mr-2"></i> Dashboard
                </a>
            </li>
            <li role="presentation">
                <a href="#custom_views_tab" aria-controls="custom_views_tab" role="tab" data-toggle="tab" class="tw-rounded-t-lg tw-font-semibold">
                    <i class="fas fa-eye tw-mr-2"></i> Migration Memory
                </a>
            </li>
            <li role="presentation">
                <a href="#developer_tab" aria-controls="developer_tab" role="tab" data-toggle="tab" class="tw-rounded-t-lg tw-font-semibold">
                    <i class="fas fa-server tw-mr-2"></i> Technical Status
                </a>
            </li>
            <li role="presentation">
                <a href="#update_log_tab" aria-controls="update_log_tab" role="tab" data-toggle="tab" class="tw-rounded-t-lg tw-font-semibold">
                    <i class="fas fa-history tw-mr-2"></i> Tweaks Update Log
                </a>
            </li>
            <li role="presentation">
                <a href="#documentation_tab" aria-controls="documentation_tab" role="tab" data-toggle="tab" class="tw-rounded-t-lg tw-font-semibold">
                    <i class="fas fa-book tw-mr-2"></i> Documentation
                </a>
            </li>
        </ul>
    </div>

    <div class="tab-content tw-bg-transparent">
        {{-- Dashboard Tab --}}
        <div role="tabpanel" class="tab-pane active" id="dashboard_tab">
            <form action="{{ route('jerryupdates.settings') }}" id="jerry_main_form" method="POST">
                @csrf

            {{-- Module Status Cards --}}
            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-4 tw-gap-4 tw-mb-6">
                
                {{-- 1. Module Status --}}
                <div class="tw-bg-white tw-rounded-lg tw-shadow tw-p-5 tw-border tw-border-gray-200 jerry-premium-card">
                    <div class="tw-flex tw-items-center tw-justify-between tw-mb-3">
                        <h4 class="tw-text-base tw-font-semibold tw-text-gray-500 tw-uppercase tw-tracking-wide">Module Status</h4>
                        @if($moduleStatus['enabled'])
                            <span class="tw-inline-flex tw-items-center tw-px-2.5 tw-py-0.5 tw-rounded-full tw-text-base tw-font-medium tw-bg-green-100 tw-text-green-800">
                                <span class="tw-w-2 tw-h-2 tw-bg-green-400 tw-rounded-full tw-mr-1.5 tw-animate-pulse"></span> Active
                            </span>
                        @else
                            <span class="tw-inline-flex tw-items-center tw-px-2.5 tw-py-0.5 tw-rounded-full tw-text-base tw-font-medium tw-bg-red-100 tw-text-red-800">
                                <span class="tw-w-2 tw-h-2 tw-bg-red-400 tw-rounded-full tw-mr-1.5"></span> Disabled
                            </span>
                        @endif
                    </div>
                    <div class="tw-space-y-2">
                        <div class="tw-flex tw-justify-between tw-text-base">
                            <span class="tw-text-gray-500">Name</span>
                            <span class="tw-font-medium tw-text-gray-800">{{ $moduleStatus['name'] }}</span>
                        </div>
                        <div class="tw-flex tw-justify-between tw-text-base">
                            <span class="tw-text-gray-500">Version</span>
                            <span class="tw-font-medium tw-text-gray-800">{{ $moduleStatus['base_version'] }}</span>
                        </div>
                    </div>
                </div>

                {{-- 2. Architecture Status --}}
                <div class="tw-bg-white tw-rounded-lg tw-shadow tw-p-5 tw-border tw-border-gray-200 jerry-premium-card tw-relative">
                    <div class="tw-flex tw-items-center tw-justify-between tw-mb-3">
                        <h4 class="tw-text-base tw-font-semibold tw-text-gray-500 tw-uppercase tw-tracking-wide">Tweak Architecture</h4>
                        <span class="tw-inline-flex tw-items-center tw-px-2.5 tw-py-0.5 tw-rounded-full tw-text-base tw-font-medium tw-bg-green-100 tw-text-green-800">
                            <i class="fas fa-shield-alt tw-mr-1"></i> Upgrade-Safe
                        </span>
                    </div>
                    <p class="tw-text-base tw-font-bold tw-text-green-700 tw-m-0">Upgrade-Safe JS Engine</p>
                    <p class="tw-text-sm tw-text-gray-500 tw-mt-1">All tweaks use DOM injection. Safe across V12 upgrades.</p>
                </div>

                {{-- 3. Dark Mode --}}
                <div class="tw-bg-white tw-rounded-lg tw-shadow tw-p-5 tw-border tw-border-gray-200 jerry-premium-card">
                    <div class="tw-flex tw-items-center tw-justify-between tw-mb-3">
                        <h4 class="tw-text-base tw-font-semibold tw-text-gray-500 tw-uppercase tw-tracking-wide">Dark Mode</h4>
                        @if($dark_mode_enabled == 'inverted')
                            <span class="tw-inline-flex tw-items-center tw-px-2.5 tw-py-0.5 tw-rounded-full tw-text-base tw-font-medium tw-bg-purple-100 tw-text-purple-800">
                                <i class="fas fa-moon tw-mr-1"></i> Inverted
                            </span>
                        @elseif($dark_mode_enabled == 'normal' || $dark_mode_enabled == '1')
                            <span class="tw-inline-flex tw-items-center tw-px-2.5 tw-py-0.5 tw-rounded-full tw-text-base tw-font-medium tw-bg-indigo-100 tw-text-indigo-800">
                                <i class="fas fa-moon tw-mr-1"></i> Normal
                            </span>
                        @else
                            <span class="tw-inline-flex tw-items-center tw-px-2.5 tw-py-0.5 tw-rounded-full tw-text-base tw-font-medium tw-bg-gray-100 tw-text-gray-600">
                                Disabled
                            </span>
                        @endif
                    </div>
                    <p class="tw-text-base tw-text-gray-600 tw-mt-2">
                        System prefers-color-scheme listener overrides.
                    </p>
                </div>

                {{-- 4. Bug Fixes --}}
                <div class="tw-bg-white tw-rounded-lg tw-shadow tw-p-5 tw-border tw-border-gray-200 jerry-premium-card">
                    <div class="tw-flex tw-items-center tw-justify-between tw-mb-3">
                        <h4 class="tw-text-base tw-font-semibold tw-text-gray-500 tw-uppercase tw-tracking-wide">Bug Fixes</h4>
                        <span class="tw-inline-flex tw-items-center tw-px-2.5 tw-py-0.5 tw-rounded-full tw-text-base tw-font-medium tw-bg-amber-100 tw-text-amber-800">
                            {{ count($bugFixes) }} applied
                        </span>
                    </div>
                    <p class="tw-text-base tw-text-gray-600 tw-font-medium tw-mt-2">
                        Custom to custom tweaks used
                    </p>
                </div>
            </div>

            {{-- Settings Form --}}
            <div class="tw-bg-white tw-rounded-lg tw-shadow tw-border tw-border-gray-200 tw-mb-6 jerry-premium-card">
                <div class="tw-px-5 tw-py-4 tw-border-b tw-border-gray-200">
                    <h3 class="tw-text-base tw-font-semibold tw-text-gray-800">
                        <i class="fas fa-cog tw-mr-2 tw-text-gray-500"></i> Module Settings
                    </h3>
                </div>
                <div class="tw-p-5">
                        <div class="tw-space-y-4">
                            {{-- Search Filter --}}
                            <div class="tw-mb-4 tw-relative">
                                <div class="tw-absolute tw-inset-y-0 tw-left-0 tw-pl-3 tw-flex tw-items-center tw-pointer-events-none">
                                    <i class="fas fa-search tw-text-gray-400"></i>
                                </div>
                                <input type="text" id="jerry_toggle_search" class="form-control tw-pl-10 tw-py-3 tw-rounded-lg tw-border-gray-300 tw-shadow-sm focus:tw-border-indigo-500 focus:tw-ring-indigo-500 tw-w-full tw-text-base" placeholder="Search tweaks (e.g. Offline, Dense, Ledger)...">
                            </div>

                            <label class="tw-flex tw-items-center tw-p-4 tw-rounded-lg tw-border tw-border-indigo-300 tw-bg-indigo-50 hover:tw-border-indigo-500 hover:tw-shadow-md tw-transition-all tw-cursor-pointer tw-mb-4 jerry-setting-item">
                                <div class="tw-flex-shrink-0 tw-w-12 tw-h-12 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-bg-indigo-600 tw-text-white tw-mr-4">
                                    <i class="fas fa-magic tw-text-xl"></i>
                                </div>
                                <div class="tw-flex-grow">
                                    <span class="tw-block tw-text-lg tw-font-extrabold tw-text-indigo-900 jerry-setting-title">Run Entire Plugin (Just Tweaks)</span>
                                    <p class="tw-text-sm tw-text-indigo-700 tw-m-0 jerry-setting-desc">Enables all jerry_* toggles in one click for this business.</p>
                                </div>
                                <div class="tw-ml-4">
                                    <input type="checkbox" name="jerry_apply_all_tweaks" id="jerry_apply_all_tweaks" value="1" class="tw-toggle tw-toggle-primary tw-scale-125" />
                                </div>
                            </label>

                            <details class="tw-border tw-border-gray-200 tw-rounded-lg" open>
                                <summary class="tw-cursor-pointer tw-font-semibold tw-text-gray-700 tw-bg-gray-100 tw-px-3 tw-py-2 tw-text-sm tw-rounded-t"> 📂 
                                    <i class="fas fa-paint-brush tw-mr-2 tw-text-gray-500"></i> Theme & Global UI
                                </summary>
                                <div class="tw-p-3 tw-grid tw-grid-cols-1 md:tw-grid-cols-3 lg:tw-grid-cols-4 tw-gap-3">
                                    <div class="tw-flex tw-items-center tw-p-4 tw-rounded-lg tw-border tw-border-gray-200 tw-bg-white hover:tw-border-indigo-400 tw-transition-all jerry-setting-item">
                                        <div class="tw-flex-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-text-sm tw-bg-purple-100 tw-text-purple-600 tw-mr-4"><i class="fas fa-moon"></i></div>
                                        <div class="tw-flex-grow">
                                            <label for="jerry_dark_mode" class="tw-block tw-text-base tw-font-bold tw-text-gray-800 tw-cursor-pointer tw-mb-0 jerry-setting-title">Dark Mode Style</label>
                                            <p class="tw-text-[11px] tw-leading-tight tw-text-gray-500 tw-m-0 jerry-setting-desc">Choose dark mode style.</p>
                                        </div>
                                        <div class="tw-ml-2 tw-w-32">
                                            <select name="jerry_dark_mode" id="jerry_dark_mode" class="form-control tw-text-sm tw-h-8 tw-py-1">
                                                <option value="0" {{ $dark_mode_enabled == '0' || !$dark_mode_enabled ? 'selected' : '' }}>Off</option>
                                                <option value="inverted" {{ $dark_mode_enabled == 'inverted' ? 'selected' : '' }}>Inverted</option>
                                                <option value="normal" {{ $dark_mode_enabled == 'normal' || $dark_mode_enabled == '1' ? 'selected' : '' }}>Normal Layout</option>
                                            </select>
                                        </div>
                                    </div>
                                    <label class="tw-flex tw-items-center tw-p-2 tw-rounded-md tw-border tw-border-gray-200 tw-bg-white hover:tw-border-indigo-400 tw-transition-all tw-cursor-pointer jerry-setting-item">
                                        <div class="tw-flex-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-text-sm tw-bg-gray-100 tw-text-gray-600 tw-mr-4"><i class="fas fa-desktop"></i></div>
                                        <div class="tw-flex-grow">
                                            <span class="tw-block tw-text-sm tw-font-bold tw-text-gray-800 jerry-setting-title">System Black Theme</span>
                                            <p class="tw-text-[11px] tw-leading-tight tw-text-gray-500 tw-m-0 jerry-setting-desc">Forces skin-black across users.</p>
                                        </div>
                                        <div class="tw-ml-4"><input type="checkbox" name="jerry_system_black_theme" value="1" class="tw-toggle tw-toggle-primary" {{ $system_black_theme_enabled ? 'checked' : '' }} /></div>
                                    </label>
                                    <label class="tw-flex tw-items-center tw-p-2 tw-rounded-md tw-border tw-border-gray-200 tw-bg-white hover:tw-border-indigo-400 tw-transition-all tw-cursor-pointer jerry-setting-item">
                                        <div class="tw-flex-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-text-sm tw-bg-red-100 tw-text-red-500 tw-mr-4"><i class="fas fa-eye-slash"></i></div>
                                        <div class="tw-flex-grow">
                                            <span class="tw-block tw-text-sm tw-font-bold tw-text-gray-800 jerry-setting-title">Disable Tour</span>
                                            <p class="tw-text-[11px] tw-leading-tight tw-text-gray-500 tw-m-0 jerry-setting-desc">Hides onboarding tour popup.</p>
                                        </div>
                                        <div class="tw-ml-4"><input type="checkbox" name="jerry_disable_tour" value="1" class="tw-toggle tw-toggle-primary" {{ \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_disable_tour') == '1' ? 'checked' : '' }} /></div>
                                    </label>
                                    <label class="tw-flex tw-items-center tw-p-4 tw-rounded-lg tw-border tw-border-gray-200 tw-bg-white hover:tw-border-indigo-400 tw-shadow-sm tw-transition-all tw-cursor-pointer jerry-setting-item">
                                        <div class="tw-flex-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-text-sm tw-bg-orange-100 tw-text-orange-600 tw-mr-4"><i class="fas fa-microchip"></i></div>
                                        <div class="tw-flex-grow">
                                            <span class="tw-block tw-text-sm tw-font-bold tw-text-gray-800 jerry-setting-title">Low-End PC Mode</span>
                                            <p class="tw-text-[11px] tw-leading-tight tw-text-gray-500 tw-m-0 jerry-setting-desc">Disables animations & shadows.</p>
                                        </div>
                                        <div class="tw-ml-4"><input type="checkbox" name="jerry_low_end_pc" value="1" class="tw-toggle tw-toggle-primary" {{ \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_low_end_pc') == '1' ? 'checked' : '' }} /></div>
                                    </label>
                                </div>
                            </details>

                            <details class="tw-border tw-border-gray-200 tw-rounded-lg tw-mb-4" open>
                                <summary class="tw-cursor-pointer tw-font-semibold tw-text-gray-700 tw-bg-gray-100 tw-px-3 tw-py-2 tw-text-sm tw-rounded-t"> 📂 
                                    <i class="fas fa-book-open tw-mr-2 tw-text-gray-500"></i> Accounts & Ledger
                                </summary>
                                <div class="tw-p-3 tw-grid tw-grid-cols-1 md:tw-grid-cols-3 lg:tw-grid-cols-4 tw-gap-3">
                                    <label class="tw-flex tw-items-center tw-p-2 tw-rounded-md tw-border tw-border-gray-200 tw-bg-white hover:tw-border-indigo-400 tw-transition-all tw-cursor-pointer jerry-setting-item">
                                        <div class="tw-flex-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-text-sm tw-bg-blue-100 tw-text-blue-600 tw-mr-4"><i class="fas fa-wrench"></i></div>
                                        <div class="tw-flex-grow">
                                            <span class="tw-block tw-text-sm tw-font-bold tw-text-gray-800 jerry-setting-title">Ledger Fix</span>
                                            <p class="tw-text-[11px] tw-leading-tight tw-text-gray-500 tw-m-0 jerry-setting-desc">Advance-payment ledger correctness.</p>
                                        </div>
                                        <div class="tw-ml-4"><input type="checkbox" name="jerry_ledger_fix" value="1" class="tw-toggle tw-toggle-primary" {{ $ledger_fix_enabled ? 'checked' : '' }} /></div>
                                    </label>
                                    <label class="tw-flex tw-items-center tw-p-2 tw-rounded-md tw-border tw-border-gray-200 tw-bg-white hover:tw-border-indigo-400 tw-transition-all tw-cursor-pointer jerry-setting-item">
                                        <div class="tw-flex-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-text-sm tw-bg-blue-100 tw-text-blue-600 tw-mr-4"><i class="fas fa-balance-scale"></i></div>
                                        <div class="tw-flex-grow">
                                            <span class="tw-block tw-text-sm tw-font-bold tw-text-gray-800 jerry-setting-title">Running Balance</span>
                                            <p class="tw-text-[11px] tw-leading-tight tw-text-gray-500 tw-m-0 jerry-setting-desc">Adds running balance column in ledger.</p>
                                        </div>
                                        <div class="tw-ml-4"><input type="checkbox" name="jerry_running_balance" value="1" class="tw-toggle tw-toggle-primary" {{ $running_balance_enabled ? 'checked' : '' }} /></div>
                                    </label>
                                    <label class="tw-flex tw-items-center tw-p-4 tw-rounded-lg tw-border tw-border-gray-200 tw-bg-white hover:tw-border-indigo-400 tw-shadow-sm tw-transition-all tw-cursor-pointer md:tw-col-span-2 jerry-setting-item">
                                        <div class="tw-flex-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-text-sm tw-bg-blue-100 tw-text-blue-600 tw-mr-4"><i class="fas fa-shield-alt"></i></div>
                                        <div class="tw-flex-grow">
                                            <span class="tw-block tw-text-sm tw-font-bold tw-text-gray-800 jerry-setting-title">Accounting Hardening (SaaS)</span>
                                            <p class="tw-text-[11px] tw-leading-tight tw-text-gray-500 tw-m-0 jerry-setting-desc">Deterministic payment-ledger sync and drift prevention.</p>
                                        </div>
                                        <div class="tw-ml-4"><input type="checkbox" name="jerry_account_hardening" value="1" class="tw-toggle tw-toggle-primary" {{ $jerry_account_hardening_enabled ? 'checked' : '' }} /></div>
                                    </label>
                                </div>
                            </details>

                            <details class="tw-border tw-border-gray-200 tw-rounded-lg tw-mb-4" open>
                                <summary class="tw-cursor-pointer tw-font-semibold tw-text-gray-700 tw-bg-gray-100 tw-px-3 tw-py-2 tw-text-sm tw-rounded-t"> 📂 
                                    <i class="fas fa-shopping-cart tw-mr-2 tw-text-gray-500"></i> POS Engine & Workflow
                                </summary>
                                <div class="tw-p-3 tw-grid tw-grid-cols-1 md:tw-grid-cols-3 lg:tw-grid-cols-4 tw-gap-3">

                                    <label class="tw-flex tw-items-center tw-p-2 tw-rounded-md tw-border tw-border-gray-200 tw-bg-white hover:tw-border-indigo-400 tw-transition-all tw-cursor-pointer jerry-setting-item">
                                        <div class="tw-flex-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-text-sm tw-bg-green-100 tw-text-green-600 tw-mr-4"><i class="fas fa-keyboard"></i></div>
                                        <div class="tw-flex-grow">
                                            <span class="tw-block tw-text-sm tw-font-bold tw-text-gray-800 jerry-setting-title">Sell/POS Tweaks</span>
                                            <p class="tw-text-[11px] tw-leading-tight tw-text-gray-500 tw-m-0 jerry-setting-desc">F8 save & workflow optimizations.</p>
                                        </div>
                                        <div class="tw-ml-4"><input type="checkbox" name="jerry_sell_tweaks" value="1" class="tw-toggle tw-toggle-primary" {{ \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_sell_tweaks') == '1' ? 'checked' : '' }} /></div>
                                    </label>
                                    <label class="tw-flex tw-items-center tw-p-2 tw-rounded-md tw-border tw-border-gray-200 tw-bg-white hover:tw-border-indigo-400 tw-transition-all tw-cursor-pointer jerry-setting-item">
                                        <div class="tw-flex-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-text-sm tw-bg-blue-100 tw-text-blue-600 tw-mr-4"><i class="fas fa-layer-group"></i></div>
                                        <div class="tw-flex-grow">
                                            <span class="tw-block tw-text-sm tw-font-bold tw-text-gray-800 jerry-setting-title">Enable Bulk Edit</span>
                                            <p class="tw-text-[11px] tw-leading-tight tw-text-gray-500 tw-m-0 jerry-setting-desc">Enable V12 product bulk editing feature globally.</p>
                                        </div>
                                        <div class="tw-ml-4"><input type="checkbox" name="jerry_product_bulk_edit" value="1" class="tw-toggle tw-toggle-primary" {{ \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_product_bulk_edit') == '1' ? 'checked' : '' }} /></div>
                                    </label>
                                </div>
                            </details>

                            <details class="tw-border tw-border-purple-200 tw-rounded-lg tw-mb-4 tw-border-l-4 tw-border-l-purple-500" open>
                                <summary class="tw-cursor-pointer tw-font-bold tw-text-purple-800 tw-bg-purple-50 tw-px-3 tw-py-2 tw-text-base tw-rounded-t hover:tw-bg-purple-100 tw-transition-colors"> 🚀 
                                    <i class="fas fa-satellite-dish tw-mr-2 tw-text-purple-500"></i> Headless SaaS Architecture
                                </summary>
                                <div class="tw-p-3 tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-3">
                                    <label class="tw-flex tw-items-center tw-p-2 tw-rounded-md tw-border tw-border-purple-200 tw-bg-white hover:tw-border-purple-400 tw-transition-all tw-cursor-pointer jerry-setting-item">
                                        <div class="tw-flex-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-text-sm tw-bg-purple-100 tw-text-purple-600 tw-mr-4"><i class="fas fa-server"></i></div>
                                        <div class="tw-flex-grow">
                                            <span class="tw-block tw-text-sm tw-font-bold tw-text-gray-800 jerry-setting-title">Enable Vercel API Mode</span>
                                            <p class="tw-text-[11px] tw-leading-tight tw-text-gray-500 tw-m-0 jerry-setting-desc">Activates the JSON API endpoints for the external Next.js frontend.</p>
                                        </div>
                                        <div class="tw-ml-4"><input type="checkbox" name="jerry_vercel_api" value="1" class="tw-toggle tw-toggle-secondary" {{ \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_vercel_api') == '1' ? 'checked' : '' }} /></div>
                                    </label>
                                    
                                    <div class="tw-col-span-1 md:tw-col-span-1 lg:tw-col-span-2 tw-flex tw-items-center tw-p-2 tw-rounded-md tw-border tw-border-purple-200 tw-bg-white">
                                        <div class="tw-flex-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-text-sm tw-bg-purple-50 tw-text-purple-500 tw-mr-4"><i class="fas fa-link"></i></div>
                                        <div class="tw-flex-grow">
                                            <span class="tw-block tw-text-sm tw-font-bold tw-text-gray-800 jerry-setting-title">Parent App API URL</span>
                                            <p class="tw-text-[11px] tw-leading-tight tw-text-gray-500 tw-m-0 jerry-setting-desc">Copy this server-detected base URL into your Vercel Next.js <code>.env</code> file (NEXT_PUBLIC_API_URL).</p>
                                            <div class="tw-mt-1 tw-flex tw-items-center">
                                                <code class="tw-text-xs tw-bg-gray-100 tw-text-purple-700 tw-px-2 tw-py-1 tw-rounded tw-border tw-border-gray-200 tw-flex-grow tw-break-all">{{ rtrim(url('/api/jerryupdates/v1'), '/') }}</code>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </details>

                            <details class="tw-border tw-border-gray-200 tw-rounded-lg tw-mb-4 tw-border-l-4 tw-border-l-blue-500" open>
                                <summary class="tw-cursor-pointer tw-font-bold tw-text-gray-800 tw-bg-blue-50 tw-px-3 tw-py-2 tw-text-base tw-rounded-t hover:tw-bg-blue-100 tw-transition-colors"> 🔌 
                                    <i class="fas fa-wifi tw-mr-2 tw-text-blue-500"></i> POS Offline Engine (IndexedDB)
                                </summary>
                                <div class="tw-p-3 tw-grid tw-grid-cols-1 md:tw-grid-cols-3 lg:tw-grid-cols-4 tw-gap-3">
                                    <label class="tw-flex tw-items-center tw-p-2 tw-rounded-md tw-border tw-border-gray-200 tw-bg-white hover:tw-border-indigo-400 tw-transition-all tw-cursor-pointer jerry-setting-item">
                                        <div class="tw-flex-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-text-sm tw-bg-emerald-100 tw-text-emerald-600 tw-mr-4"><i class="fas fa-wifi"></i></div>
                                        <div class="tw-flex-grow">
                                            <span class="tw-block tw-text-sm tw-font-bold tw-text-gray-800 jerry-setting-title">Hybrid Offline Mode</span>
                                            <p class="tw-text-[11px] tw-leading-tight tw-text-gray-500 tw-m-0 jerry-setting-desc">IndexedDB offline queue engine.</p>
                                        </div>
                                        <div class="tw-ml-4"><input type="checkbox" name="jerry_offline_mode" value="1" class="tw-toggle tw-toggle-primary" {{ $jerry_offline_mode_enabled ? 'checked' : '' }} /></div>
                                    </label>
                                    <label class="tw-flex tw-items-center tw-p-2 tw-rounded-md tw-border tw-border-gray-200 tw-bg-white hover:tw-border-indigo-400 tw-transition-all tw-cursor-pointer jerry-setting-item">
                                        <div class="tw-flex-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-text-sm tw-bg-blue-100 tw-text-blue-500 tw-mr-4"><i class="fas fa-heartbeat"></i></div>
                                        <div class="tw-flex-grow">
                                            <span class="tw-block tw-text-sm tw-font-bold tw-text-gray-800 jerry-setting-title">Server Heartbeat</span>
                                            <p class="tw-text-[11px] tw-leading-tight tw-text-gray-500 tw-m-0 jerry-setting-desc">Real-time connectivity pinging.</p>
                                        </div>
                                        <div class="tw-ml-4"><input type="checkbox" name="jerry_offline_heartbeat" value="1" class="tw-toggle tw-toggle-primary" {{ \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_offline_heartbeat', '1') == '1' ? 'checked' : '' }} /></div>
                                    </label>
                                    <div class="tw-flex tw-items-center tw-p-2 tw-rounded-md tw-border tw-border-gray-200 tw-bg-white hover:tw-border-indigo-400 tw-transition-all jerry-setting-item">
                                        <div class="tw-flex-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-text-sm tw-bg-indigo-100 tw-text-indigo-600 tw-mr-4"><i class="fas fa-hdd"></i></div>
                                        <div class="tw-flex-grow">
                                            <label for="jerry_offline_row_cache_max" class="tw-block tw-text-sm tw-font-bold tw-text-gray-800 tw-mb-0 jerry-setting-title">Row Cache Size (products)</label>
                                            <p class="tw-text-[11px] tw-leading-tight tw-text-gray-500 tw-m-0 jerry-setting-desc">Max products stored in IndexedDB offline cache. Match your catalog size. Default: 5000.</p>
                                        </div>
                                        <div class="tw-ml-4 tw-w-28">
                                            <input type="number" name="jerry_offline_row_cache_max" id="jerry_offline_row_cache_max" min="100" max="100000" step="100" value="{{ \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_offline_row_cache_max', '5000') }}" class="form-control tw-text-sm tw-h-8 tw-py-1 tw-text-center" />
                                        </div>
                                    </div>
                                    <label class="tw-flex tw-items-center tw-p-2 tw-rounded-md tw-border tw-border-orange-200 tw-bg-orange-50 hover:tw-border-orange-400 tw-transition-all tw-cursor-pointer jerry-setting-item md:tw-col-span-2">
                                        <div class="tw-flex-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-text-sm tw-bg-orange-100 tw-text-orange-600 tw-mr-4"><i class="fas fa-store-alt"></i></div>
                                        <div class="tw-flex-grow">
                                            <span class="tw-block tw-text-sm tw-font-bold tw-text-gray-800 jerry-setting-title">Big Shop Mode (20,000+ products)</span>
                                            <p class="tw-text-[11px] tw-leading-tight tw-text-gray-500 tw-m-0 jerry-setting-desc">Disables background pre-warming. Products cache lazily on first scan. Safe for 20,000+ product catalogs where pre-warming would overload the server.</p>
                                        </div>
                                        <div class="tw-ml-4"><input type="checkbox" name="jerry_offline_big_shop" value="1" class="tw-toggle tw-toggle-warning" {{ \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_offline_big_shop') == '1' ? 'checked' : '' }} /></div>
                                    </label>
                                </div>
                            </details>

                            <details class="tw-border tw-border-gray-200 tw-rounded-lg tw-mb-4 tw-border-l-4 tw-border-l-yellow-400" open>
                                <summary class="tw-cursor-pointer tw-font-bold tw-text-gray-800 tw-bg-yellow-50 tw-px-3 tw-py-2 tw-text-base tw-rounded-t hover:tw-bg-yellow-100 tw-transition-colors"> ⚡ 
                                    <i class="fas fa-tachometer-alt tw-mr-2 tw-text-yellow-500"></i> POS Speed Cache & Search
                                </summary>
                                <div class="tw-p-3 tw-grid tw-grid-cols-1 md:tw-grid-cols-3 lg:tw-grid-cols-4 tw-gap-3">
                                    <label class="tw-flex tw-items-center tw-p-2 tw-rounded-md tw-border tw-border-gray-200 tw-bg-white hover:tw-border-indigo-400 tw-transition-all tw-cursor-pointer jerry-setting-item">
                                        <div class="tw-flex-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-text-sm tw-bg-yellow-100 tw-text-yellow-600 tw-mr-4"><i class="fas fa-tachometer-alt"></i></div>
                                        <div class="tw-flex-grow">
                                            <span class="tw-block tw-text-sm tw-font-bold tw-text-gray-800 jerry-setting-title">POS Speed Cache</span>
                                            <p class="tw-text-[11px] tw-leading-tight tw-text-gray-500 tw-m-0 jerry-setting-desc">Preloads products to local memory.</p>
                                        </div>
                                        <div class="tw-ml-4"><input type="checkbox" name="jerry_pos_cache" value="1" class="tw-toggle tw-toggle-primary" {{ $jerry_pos_cache_enabled ? 'checked' : '' }} /></div>
                                    </label>
                                    
                                    <div class="tw-flex tw-items-center tw-p-2 tw-rounded-md tw-border tw-border-red-200 tw-bg-red-50 hover:tw-border-red-400 tw-transition-all jerry-setting-item md:tw-col-span-2">
                                        <div class="tw-flex-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-text-sm tw-bg-red-100 tw-text-red-600 tw-mr-4"><i class="fas fa-shield-alt"></i></div>
                                        <div class="tw-flex-grow">
                                            <label for="jerry_speed_cache_max" class="tw-block tw-text-sm tw-font-bold tw-text-gray-800 tw-mb-0 jerry-setting-title">Speed Cache Max Size (Browser Memory Guard)</label>
                                            <p class="tw-text-[11px] tw-leading-tight tw-text-gray-500 tw-m-0 jerry-setting-desc">If product count exceeds this, Speed Cache disables itself to prevent browser OOM crashes. Falls back to AJAX search automatically. Default: 20000.</p>
                                        </div>
                                        <div class="tw-ml-4 tw-w-28">
                                            <input type="number" name="jerry_speed_cache_max" id="jerry_speed_cache_max" min="1000" max="50000" step="1000" value="{{ \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_speed_cache_max', '20000') }}" class="form-control tw-text-sm tw-h-8 tw-py-1 tw-text-center" />
                                        </div>
                                    </div>

                                    <label class="tw-flex tw-items-center tw-p-2 tw-rounded-md tw-border tw-border-gray-200 tw-bg-white hover:tw-border-indigo-400 tw-transition-all tw-cursor-pointer jerry-setting-item">
                                        <div class="tw-flex-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-text-sm tw-bg-gray-100 tw-text-gray-600 tw-mr-4"><i class="fas fa-search-plus"></i></div>
                                        <div class="tw-flex-grow">
                                            <span class="tw-block tw-text-sm tw-font-bold tw-text-gray-800 jerry-setting-title">Server Auto-Add</span>
                                            <p class="tw-text-[11px] tw-leading-tight tw-text-gray-500 tw-m-0 jerry-setting-desc">Auto-add product if 1 result found.</p>
                                        </div>
                                        <div class="tw-ml-4"><input type="checkbox" name="jerry_pos_auto_add" value="1" class="tw-toggle tw-toggle-primary" {{ \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_pos_auto_add') == '1' ? 'checked' : '' }} /></div>
                                    </label>
                                    <label class="tw-flex tw-items-center tw-p-2 tw-rounded-md tw-border tw-border-gray-200 tw-bg-white hover:tw-border-indigo-400 tw-transition-all tw-cursor-pointer jerry-setting-item">
                                        <div class="tw-flex-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-text-sm tw-bg-gray-100 tw-text-gray-600 tw-mr-4"><i class="fas fa-lock"></i></div>
                                        <div class="tw-flex-grow">
                                            <span class="tw-block tw-text-sm tw-font-bold tw-text-gray-800 jerry-setting-title">Restrict Auto-Select</span>
                                            <p class="tw-text-[11px] tw-leading-tight tw-text-gray-500 tw-m-0 jerry-setting-desc">Limit auto-select to barcodes only.</p>
                                        </div>
                                        <div class="tw-ml-4"><input type="checkbox" name="jerry_pos_auto_select_patch" value="1" class="tw-toggle tw-toggle-primary" {{ \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_pos_auto_select_patch') == '1' ? 'checked' : '' }} /></div>
                                    </label>
                                    <label class="tw-flex tw-items-center tw-p-2 tw-rounded-md tw-border tw-border-gray-200 tw-bg-white hover:tw-border-indigo-400 tw-transition-all tw-cursor-pointer jerry-setting-item">
                                        <div class="tw-flex-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-text-sm tw-bg-red-50 tw-text-red-500 tw-mr-4"><i class="fas fa-exclamation-circle"></i></div>
                                        <div class="tw-flex-grow">
                                            <span class="tw-block tw-text-sm tw-font-bold tw-text-gray-800 jerry-setting-title">Show Search Errors</span>
                                            <p class="tw-text-[11px] tw-leading-tight tw-text-gray-500 tw-m-0 jerry-setting-desc">Show 'No Product Found' toastr alerts.</p>
                                        </div>
                                        <div class="tw-ml-4"><input type="checkbox" name="jerry_pos_no_match_toast" value="1" class="tw-toggle tw-toggle-primary" {{ \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_pos_no_match_toast') == '1' ? 'checked' : '' }} /></div>
                                    </label>
                                </div>
                            </details>

                            <details class="tw-border tw-border-gray-200 tw-rounded-lg tw-mb-4" open>
                                <summary class="tw-cursor-pointer tw-font-semibold tw-text-gray-700 tw-bg-gray-100 tw-px-3 tw-py-2 tw-text-sm tw-rounded-t"> 📂 
                                    <i class="fas fa-compress-arrows-alt tw-mr-2 tw-text-gray-500"></i> POS UI Densification
                                </summary>
                                <div class="tw-p-3 tw-grid tw-grid-cols-1 md:tw-grid-cols-3 lg:tw-grid-cols-4 tw-gap-3">
                                    <label class="tw-flex tw-items-center tw-p-2 tw-rounded-md tw-border tw-border-gray-200 tw-bg-white hover:tw-border-indigo-400 tw-transition-all tw-cursor-pointer jerry-setting-item">
                                        <div class="tw-flex-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-text-sm tw-bg-gray-100 tw-text-gray-600 tw-mr-4"><i class="fas fa-image"></i></div>
                                        <div class="tw-flex-grow">
                                            <span class="tw-block tw-text-sm tw-font-bold tw-text-gray-800 jerry-setting-title">Hide Cart Images</span>
                                            <p class="tw-text-[11px] tw-leading-tight tw-text-gray-500 tw-m-0 jerry-setting-desc">Hide product images in POS cart rows.</p>
                                        </div>
                                        <div class="tw-ml-4"><input type="checkbox" name="jerry_pos_cart_hide_images" value="1" class="tw-toggle tw-toggle-primary" {{ \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_pos_cart_hide_images') == '1' ? 'checked' : '' }} /></div>
                                    </label>
                                    <label class="tw-flex tw-items-center tw-p-2 tw-rounded-md tw-border tw-border-gray-200 tw-bg-white hover:tw-border-indigo-400 tw-transition-all tw-cursor-pointer jerry-setting-item">
                                        <div class="tw-flex-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-text-sm tw-bg-gray-100 tw-text-gray-600 tw-mr-4"><i class="fas fa-th"></i></div>
                                        <div class="tw-flex-grow">
                                            <span class="tw-block tw-text-sm tw-font-bold tw-text-gray-800 jerry-setting-title">Hide Grid Images</span>
                                            <p class="tw-text-[11px] tw-leading-tight tw-text-gray-500 tw-m-0 jerry-setting-desc">Hide product images in POS grid.</p>
                                        </div>
                                        <div class="tw-ml-4"><input type="checkbox" name="jerry_pos_list_hide_images" value="1" class="tw-toggle tw-toggle-primary" {{ \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_pos_list_hide_images') == '1' ? 'checked' : '' }} /></div>
                                    </label>
                                    <label class="tw-flex tw-items-center tw-p-2 tw-rounded-md tw-border tw-border-gray-200 tw-bg-white hover:tw-border-indigo-400 tw-transition-all tw-cursor-pointer jerry-setting-item">
                                        <div class="tw-flex-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-text-sm tw-bg-gray-100 tw-text-gray-600 tw-mr-4"><i class="fas fa-compress"></i></div>
                                        <div class="tw-flex-grow">
                                            <span class="tw-block tw-text-sm tw-font-bold tw-text-gray-800 jerry-setting-title">Compact Cart Rows</span>
                                            <p class="tw-text-[11px] tw-leading-tight tw-text-gray-500 tw-m-0 jerry-setting-desc">Smaller padding in POS cart.</p>
                                        </div>
                                        <div class="tw-ml-4"><input type="checkbox" name="jerry_pos_hide_qty_buttons" value="1" class="tw-toggle tw-toggle-primary" {{ \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_pos_hide_qty_buttons') == '1' ? 'checked' : '' }} /></div>
                                    </label>
                                    <label class="tw-flex tw-items-center tw-p-2 tw-rounded-md tw-border tw-border-gray-200 tw-bg-white hover:tw-border-indigo-400 tw-transition-all tw-cursor-pointer jerry-setting-item">
                                        <div class="tw-flex-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-text-sm tw-bg-gray-100 tw-text-gray-600 tw-mr-4"><i class="fas fa-list-alt"></i></div>
                                        <div class="tw-flex-grow">
                                            <span class="tw-block tw-text-sm tw-font-bold tw-text-gray-800 jerry-setting-title">Hide Unit Dropdown</span>
                                            <p class="tw-text-[11px] tw-leading-tight tw-text-gray-500 tw-m-0 jerry-setting-desc">Hide unit-of-measure dropdown.</p>
                                        </div>
                                        <div class="tw-ml-4"><input type="checkbox" name="jerry_pos_hide_unit_dropdown" value="1" class="tw-toggle tw-toggle-primary" {{ \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_pos_hide_unit_dropdown') == '1' ? 'checked' : '' }} /></div>
                                    </label>
                                    <label class="tw-flex tw-items-center tw-p-2 tw-rounded-md tw-border tw-border-gray-200 tw-bg-white hover:tw-border-indigo-400 tw-transition-all tw-cursor-pointer jerry-setting-item">
                                        <div class="tw-flex-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-text-sm tw-bg-green-50 tw-text-green-600 tw-mr-4"><i class="fas fa-dollar-sign"></i></div>
                                        <div class="tw-flex-grow">
                                            <span class="tw-block tw-text-sm tw-font-bold tw-text-gray-800 jerry-setting-title">Show Purchase Price (Cart)</span>
                                            <p class="tw-text-[11px] tw-leading-tight tw-text-gray-500 tw-m-0 jerry-setting-desc">Display PP column in POS cart.</p>
                                        </div>
                                        <div class="tw-ml-4"><input type="checkbox" name="jerry_pos_cart_show_pp" value="1" class="tw-toggle tw-toggle-primary" {{ \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_pos_cart_show_pp') == '1' ? 'checked' : '' }} /></div>
                                    </label>
                                </div>
                            </details>

                            <details class="tw-border tw-border-gray-200 tw-rounded-lg tw-mb-4" open>
                                <summary class="tw-cursor-pointer tw-font-semibold tw-text-gray-700 tw-bg-gray-100 tw-px-3 tw-py-2 tw-text-sm tw-rounded-t"> 📂 
                                    <i class="fas fa-boxes tw-mr-2 tw-text-gray-500"></i> Product & Addons
                                </summary>
                                <div class="tw-p-3 tw-grid tw-grid-cols-1 md:tw-grid-cols-3 lg:tw-grid-cols-4 tw-gap-3">
                                    <label class="tw-flex tw-items-center tw-p-2 tw-rounded-md tw-border tw-border-gray-200 tw-bg-white hover:tw-border-indigo-400 tw-transition-all tw-cursor-pointer jerry-setting-item">
                                        <div class="tw-flex-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-text-sm tw-bg-pink-100 tw-text-pink-600 tw-mr-4"><i class="fas fa-tags"></i></div>
                                        <div class="tw-flex-grow">
                                            <span class="tw-block tw-text-sm tw-font-bold tw-text-gray-800 jerry-setting-title">Product UI Tweaks</span>
                                            <p class="tw-text-[11px] tw-leading-tight tw-text-gray-500 tw-m-0 jerry-setting-desc">Quick-add category + helpers.</p>
                                        </div>
                                        <div class="tw-ml-4"><input type="checkbox" name="jerry_product_tweaks" value="1" class="tw-toggle tw-toggle-primary" {{ \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_product_tweaks') == '1' ? 'checked' : '' }} /></div>
                                    </label>
                                    <label class="tw-flex tw-items-center tw-p-2 tw-rounded-md tw-border tw-border-gray-200 tw-bg-white hover:tw-border-indigo-400 tw-transition-all tw-cursor-pointer jerry-setting-item">
                                        <div class="tw-flex-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-text-sm tw-bg-pink-100 tw-text-pink-600 tw-mr-4"><i class="fas fa-eraser"></i></div>
                                        <div class="tw-flex-grow">
                                            <span class="tw-block tw-text-sm tw-font-bold tw-text-gray-800 jerry-setting-title">Product Clean UI</span>
                                            <p class="tw-text-[11px] tw-leading-tight tw-text-gray-500 tw-m-0 jerry-setting-desc">Hide middle fields for compact form.</p>
                                        </div>
                                        <div class="tw-ml-4"><input type="checkbox" name="jerry_product_hide_middle" value="1" class="tw-toggle tw-toggle-primary" {{ \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_product_hide_middle') == '1' ? 'checked' : '' }} /></div>
                                    </label>
                                    <label class="tw-flex tw-items-center tw-p-2 tw-rounded-md tw-border tw-border-gray-200 tw-bg-white hover:tw-border-indigo-400 tw-transition-all tw-cursor-pointer jerry-setting-item">
                                        <div class="tw-flex-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-text-sm tw-bg-pink-100 tw-text-pink-600 tw-mr-4"><i class="fas fa-percentage"></i></div>
                                        <div class="tw-flex-grow">
                                            <span class="tw-block tw-text-sm tw-font-bold tw-text-gray-800 jerry-setting-title">Tax Inclusive Default</span>
                                            <p class="tw-text-[11px] tw-leading-tight tw-text-gray-500 tw-m-0 jerry-setting-desc">Default product selling tax to inc.</p>
                                        </div>
                                        <div class="tw-ml-4"><input type="checkbox" name="jerry_product_tax_inclusive" value="1" class="tw-toggle tw-toggle-primary" {{ \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_product_tax_inclusive') == '1' ? 'checked' : '' }} /></div>
                                    </label>
                                    <label class="tw-flex tw-items-center tw-p-2 tw-rounded-md tw-border tw-border-gray-200 tw-bg-white hover:tw-border-indigo-400 tw-transition-all tw-cursor-pointer jerry-setting-item">
                                        <div class="tw-flex-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-text-sm tw-bg-pink-100 tw-text-pink-600 tw-mr-4"><i class="fas fa-coins"></i></div>
                                        <div class="tw-flex-grow">
                                            <span class="tw-block tw-text-sm tw-font-bold tw-text-gray-800 jerry-setting-title">Purchase Price = 0</span>
                                            <p class="tw-text-[11px] tw-leading-tight tw-text-gray-500 tw-m-0 jerry-setting-desc">Default PP to zero in product form.</p>
                                        </div>
                                        <div class="tw-ml-4"><input type="checkbox" name="jerry_product_purchase_zero" value="1" class="tw-toggle tw-toggle-primary" {{ \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_product_purchase_zero') == '1' ? 'checked' : '' }} /></div>
                                    </label>
                                </div>
                            </details>

                            <details class="tw-border tw-border-gray-200 tw-rounded-lg tw-mb-4" open>
                                <summary class="tw-cursor-pointer tw-font-semibold tw-text-gray-700 tw-bg-gray-100 tw-px-3 tw-py-2 tw-text-sm tw-rounded-t"> 📂 
                                    <i class="fas fa-id-card tw-mr-2 tw-text-gray-500"></i> Contacts & Expenses
                                </summary>
                                <div class="tw-p-3 tw-grid tw-grid-cols-1 md:tw-grid-cols-3 lg:tw-grid-cols-4 tw-gap-3">
                                    <label class="tw-flex tw-items-center tw-p-2 tw-rounded-md tw-border tw-border-gray-200 tw-bg-white hover:tw-border-indigo-400 tw-transition-all tw-cursor-pointer jerry-setting-item">
                                        <div class="tw-flex-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-text-sm tw-bg-teal-100 tw-text-teal-600 tw-mr-4"><i class="fas fa-user-edit"></i></div>
                                        <div class="tw-flex-grow">
                                            <span class="tw-block tw-text-sm tw-font-bold tw-text-gray-800 jerry-setting-title">Contact Tweaks</span>
                                            <p class="tw-text-[11px] tw-leading-tight tw-text-gray-500 tw-m-0 jerry-setting-desc">Hide business-name for individuals.</p>
                                        </div>
                                        <div class="tw-ml-4"><input type="checkbox" name="jerry_contact_tweaks" value="1" class="tw-toggle tw-toggle-primary" {{ \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_contact_tweaks') == '1' ? 'checked' : '' }} /></div>
                                    </label>
                                    <label class="tw-flex tw-items-center tw-p-2 tw-rounded-md tw-border tw-border-gray-200 tw-bg-white hover:tw-border-indigo-400 tw-transition-all tw-cursor-pointer jerry-setting-item">
                                        <div class="tw-flex-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-text-sm tw-bg-teal-100 tw-text-teal-600 tw-mr-4"><i class="fas fa-receipt"></i></div>
                                        <div class="tw-flex-grow">
                                            <span class="tw-block tw-text-sm tw-font-bold tw-text-gray-800 jerry-setting-title">Expense Tweaks</span>
                                            <p class="tw-text-[11px] tw-leading-tight tw-text-gray-500 tw-m-0 jerry-setting-desc">Quick-add category + amount sync.</p>
                                        </div>
                                        <div class="tw-ml-4"><input type="checkbox" name="jerry_expense_tweaks" value="1" class="tw-toggle tw-toggle-primary" {{ \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_expense_tweaks') == '1' ? 'checked' : '' }} /></div>
                                    </label>
                                </div>
                            </details>

                            <details class="tw-border tw-border-gray-200 tw-rounded-lg tw-mb-4" open>
                                <summary class="tw-cursor-pointer tw-font-semibold tw-text-gray-700 tw-bg-gray-100 tw-px-3 tw-py-2 tw-text-sm tw-rounded-t"> 📂 
                                    <i class="fas fa-barcode tw-mr-2 tw-text-gray-500"></i> Labels & QR Codes
                                </summary>
                                <div class="tw-p-3 tw-grid tw-grid-cols-1 md:tw-grid-cols-3 lg:tw-grid-cols-4 tw-gap-3">
                                    <label class="tw-flex tw-items-center tw-p-2 tw-rounded-md tw-border tw-border-gray-200 tw-bg-white hover:tw-border-indigo-400 tw-transition-all tw-cursor-pointer jerry-setting-item">
                                        <div class="tw-flex-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-text-sm tw-bg-gray-100 tw-text-gray-600 tw-mr-4"><i class="fas fa-barcode"></i></div>
                                        <div class="tw-flex-grow">
                                            <span class="tw-block tw-text-sm tw-font-bold tw-text-gray-800 jerry-setting-title">Barcode/Label Tweaks</span>
                                            <p class="tw-text-[11px] tw-leading-tight tw-text-gray-500 tw-m-0 jerry-setting-desc">Default unchecked business for labels.</p>
                                        </div>
                                        <div class="tw-ml-4"><input type="checkbox" name="jerry_label_tweaks" value="1" class="tw-toggle tw-toggle-primary" {{ \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_label_tweaks') == '1' ? 'checked' : '' }} /></div>
                                    </label>
                                    <label class="tw-flex tw-items-center tw-p-2 tw-rounded-md tw-border tw-border-gray-200 tw-bg-white hover:tw-border-indigo-400 tw-transition-all tw-cursor-pointer jerry-setting-item">
                                        <div class="tw-flex-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-text-sm tw-bg-cyan-100 tw-text-cyan-600 tw-mr-4"><i class="fas fa-qrcode"></i></div>
                                        <div class="tw-flex-grow">
                                            <span class="tw-block tw-text-sm tw-font-bold tw-text-gray-800 jerry-setting-title">Invoice UPI</span>
                                            <p class="tw-text-[11px] tw-leading-tight tw-text-gray-500 tw-m-0 jerry-setting-desc">UPI field and invoice QR rendering.</p>
                                        </div>
                                        <div class="tw-ml-4"><input type="checkbox" name="jerry_invoice_upi" value="1" class="tw-toggle tw-toggle-primary" {{ \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_invoice_upi') == '1' ? 'checked' : '' }} /></div>
                                    </label>
                                </div>
                            </details>

                            <details class="tw-border tw-border-gray-200 tw-rounded-lg tw-mb-4" open>
                                <summary class="tw-cursor-pointer tw-font-semibold tw-text-gray-700 tw-bg-gray-100 tw-px-3 tw-py-2 tw-text-sm tw-rounded-t"> 📂 
                                    <i class="fas fa-language tw-mr-2 tw-text-gray-500"></i> UI Translations & Labels
                                </summary>
                                <div class="tw-p-4">
                                    <div class="tw-bg-indigo-50 tw-p-3 tw-rounded tw-border tw-border-indigo-100 tw-mb-3">
                                        <h4 class="tw-text-sm tw-font-bold tw-text-indigo-800 tw-mb-1"><i class="fas fa-info-circle tw-mr-1"></i> Upgrade-Safe Text Replacer</h4>
                                        <p class="tw-text-xs tw-text-indigo-700 tw-m-0">
                                            Change built-in labels (like "Commission Agent" to "Broker") safely without editing core code. <br>
                                            Enter rules in valid JSON format: <code>{"Find Text": "Replace With"}</code>
                                        </p>
                                    </div>
                                    <textarea name="jerry_custom_translations" rows="4" class="form-control hover:tw-border-indigo-400 tw-font-mono tw-text-sm" placeholder='{
    "Commission Agent": "Broker",
    "Total Sales": "Total Revenue"
}'>{{ \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_custom_translations') }}</textarea>
                                </div>
                            </details>
                        </div>

                        <script>
                        (function(){
                            // Apply dynamic bookmark border highlight to active tweaking cards
                            function jerryUpdateCardStyle(cb) {
                                var card = cb.closest('.jerry-setting-item');
                                if (card) {
                                    var iconDiv = card.querySelector('.tw-flex-shrink-0');
                                    var colorMatch = iconDiv ? iconDiv.className.match(/tw-text-([a-z]+)-[0-9]+/) : null;
                                    var colorName = colorMatch ? colorMatch[1] : 'indigo';
                                    
                                    var colorsMap = {
                                        'purple': { bg: '#faf5ff', border: '#a855f7' },
                                        'indigo': { bg: '#eef2ff', border: '#6366f1' },
                                        'red': { bg: '#fef2f2', border: '#ef4444' },
                                        'orange': { bg: '#fff7ed', border: '#f97316' },
                                        'blue': { bg: '#eff6ff', border: '#3b82f6' },
                                        'cyan': { bg: '#ecfeff', border: '#06b6d4' },
                                        'green': { bg: '#f0fdf4', border: '#22c55e' },
                                        'gray': { bg: '#f9fafb', border: '#6b7280' },
                                        'emerald': { bg: '#ecfdf5', border: '#10b981' },
                                        'amber': { bg: '#fffbeb', border: '#f59e0b' },
                                        'teal': { bg: '#f0fdfa', border: '#14b8a6' }
                                    };
                                    var hex = colorsMap[colorName] || colorsMap['indigo'];

                                    if (cb.checked) {
                                        card.classList.remove('tw-bg-white', 'tw-border-gray-200');
                                        card.style.backgroundColor = hex.bg;
                                        card.style.border = '1px solid ' + hex.border;
                                        card.style.borderLeftWidth = '5px';
                                        card.style.borderLeftColor = hex.border;
                                    } else {
                                        card.classList.add('tw-bg-white', 'tw-border-gray-200');
                                        card.style.backgroundColor = '';
                                        card.style.border = '';
                                        card.style.borderLeftWidth = '';
                                        card.style.borderLeftColor = '';
                                    }
                                }
                            }

                            // Run on initial load
                            document.querySelectorAll('input[type="checkbox"][name^="jerry_"]:not([name="jerry_apply_all_tweaks"])').forEach(function(cb){
                                jerryUpdateCardStyle(cb);
                            });

                            // Master Toggle & Individual Toggles
                            document.addEventListener('change', function(e){
                                if(e.target && e.target.id === 'jerry_apply_all_tweaks'){
                                    var isChecked = e.target.checked;
                                    document.querySelectorAll('input[type="checkbox"][name^="jerry_"]:not([name="jerry_apply_all_tweaks"])').forEach(function(cb){ 
                                        cb.checked = isChecked; 
                                        jerryUpdateCardStyle(cb);
                                    });
                                    var dark = document.getElementById('jerry_dark_mode');
                                    if(isChecked && dark && (!dark.value || dark.value === '0')){ dark.value = 'normal'; }
                                    else if (!isChecked && dark) { dark.value = '0'; }
                                } else if (e.target && e.target.matches('input[type="checkbox"][name^="jerry_"]')) {
                                    jerryUpdateCardStyle(e.target);
                                }
                            });

                            // Search Filter functionality
                            var searchInput = document.getElementById('jerry_toggle_search');
                            if(searchInput) {
                                searchInput.addEventListener('input', function(e) {
                                    var query = e.target.value.toLowerCase();
                                    
                                    // Iterate over all details groups
                                    var detailsBlocks = document.querySelectorAll('details.tw-border');
                                    detailsBlocks.forEach(function(details) {
                                        var items = details.querySelectorAll('.jerry-setting-item');
                                        var visibleCount = 0;
                                        
                                        items.forEach(function(item) {
                                            var textContent = item.textContent.toLowerCase();
                                            if (textContent.indexOf(query) !== -1) {
                                                item.style.display = 'flex';
                                                visibleCount++;
                                            } else {
                                                item.style.display = 'none';
                                            }
                                        });

                                        // Hide details block if nothing matches inside
                                        if (visibleCount === 0 && query !== '') {
                                            details.style.display = 'none';
                                        } else {
                                            details.style.display = 'block';
                                            // Auto-expand if there's a search string and we have matches
                                            if (query !== '') {
                                                details.setAttribute('open', '');
                                            }
                                        }
                                    });
                                });
                            }
                        })();
                        </script>

                        <div class="tw-mt-6 tw-flex tw-justify-end">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save tw-mr-1"></i> Save All Settings</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- System Tools & Cache --}}
            <div class="tw-bg-white tw-rounded-lg tw-shadow tw-border tw-border-gray-200 tw-mb-6 jerry-premium-card">
                <div class="tw-px-5 tw-py-4 tw-border-b tw-border-gray-200">
                    <h3 class="tw-text-base tw-font-semibold tw-text-gray-800">
                        <i class="fas fa-magic tw-mr-2 tw-text-orange-500"></i> Laravel Clear Methodologies
                    </h3>
                </div>
                <div class="tw-p-5">
                    <p class="tw-text-base tw-text-gray-600 tw-mb-4">
                        Execute clear commands independently to solve stubborn UI, routing, or general cache issues.
                    </p>
                    
                    <div class="tw-grid tw-grid-cols-2 md:tw-grid-cols-5 tw-gap-4">
                        <form action="{{ route('jerryupdates.clear_cache') }}" method="POST">
                            @csrf
                            <input type="hidden" name="type" value="config">
                            <button type="submit" class="tw-w-full tw-py-2 tw-px-4 tw-bg-gray-100 hover:tw-bg-gray-200 tw-text-gray-800 tw-rounded-md tw-font-medium tw-transition tw-duration-150 tw-ease-in-out tw-text-base tw-border tw-border-gray-300 tw-flex tw-items-center tw-justify-center" onclick="return confirm('Clear Configuration Cache?');">
                                <i class="fas fa-cogs tw-mr-2"></i> Config
                            </button>
                        </form>

                        <form action="{{ route('jerryupdates.clear_cache') }}" method="POST">
                            @csrf
                            <input type="hidden" name="type" value="route">
                            <button type="submit" class="tw-w-full tw-py-2 tw-px-4 tw-bg-gray-100 hover:tw-bg-gray-200 tw-text-gray-800 tw-rounded-md tw-font-medium tw-transition tw-duration-150 tw-ease-in-out tw-text-base tw-border tw-border-gray-300 tw-flex tw-items-center tw-justify-center" onclick="return confirm('Clear Routes Cache?');">
                                <i class="fas fa-route tw-mr-2"></i> Route
                            </button>
                        </form>

                        <form action="{{ route('jerryupdates.clear_cache') }}" method="POST">
                            @csrf
                            <input type="hidden" name="type" value="view">
                            <button type="submit" class="tw-w-full tw-py-2 tw-px-4 tw-bg-gray-100 hover:tw-bg-gray-200 tw-text-gray-800 tw-rounded-md tw-font-medium tw-transition tw-duration-150 tw-ease-in-out tw-text-base tw-border tw-border-gray-300 tw-flex tw-items-center tw-justify-center" onclick="return confirm('Clear Compiled Views?');">
                                <i class="fas fa-paint-brush tw-mr-2"></i> View
                            </button>
                        </form>

                        <form action="{{ route('jerryupdates.clear_cache') }}" method="POST">
                            @csrf
                            <input type="hidden" name="type" value="optimize">
                            <button type="submit" class="tw-w-full tw-py-2 tw-px-4 tw-bg-red-50 hover:tw-bg-red-100 tw-text-red-700 tw-rounded-md tw-font-medium tw-transition tw-duration-150 tw-ease-in-out tw-text-base tw-border tw-border-red-200 tw-flex tw-items-center tw-justify-center" onclick="return confirm('Run full Application Optimize Clear?');">
                                <i class="fas fa-bomb tw-mr-2"></i> Optimize
                            </button>
                        </form>

                        @if(auth()->user()->can('superadmin'))
                        <form action="{{ route('jerryupdates.run_accounting_migration') }}" method="POST">
                            @csrf
                            <button type="submit" class="tw-w-full tw-py-2 tw-px-4 tw-bg-amber-50 hover:tw-bg-amber-100 tw-text-amber-700 tw-rounded-md tw-font-medium tw-transition tw-duration-150 tw-ease-in-out tw-text-base tw-border tw-border-amber-200 tw-flex tw-items-center tw-justify-center" onclick="return confirm('Run accounting hardening migration now?');">
                                <i class="fas fa-database tw-mr-2"></i> Accounting Migration
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Technical Status Tab --}}
        <div role="tabpanel" class="tab-pane" id="developer_tab">
            <div class="tw-bg-white tw-rounded-lg tw-shadow tw-border tw-border-gray-200 tw-mb-6">
                <div class="tw-px-5 tw-py-4 tw-border-b tw-border-gray-200">
                    <h3 class="tw-text-base tw-font-semibold tw-text-gray-800">
                        <i class="fas fa-heartbeat tw-mr-2 tw-text-emerald-500"></i> Runtime Compatibility Checks
                    </h3>
                </div>
                <div class="tw-overflow-x-auto">
                    <table class="tw-w-full tw-text-base">
                        <thead>
                            <tr class="tw-bg-gray-50">
                                <th class="tw-px-5 tw-py-3 tw-text-left tw-text-base tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider">Check</th>
                                <th class="tw-px-5 tw-py-3 tw-text-left tw-text-base tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider">Hint</th>
                                <th class="tw-px-5 tw-py-3 tw-text-center tw-text-base tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="tw-divide-y tw-divide-gray-200">
                            @foreach($technicalChecks as $check)
                            <tr>
                                <td class="tw-px-5 tw-py-3">
                                    <code class="tw-text-base tw-bg-gray-100 tw-px-2 tw-py-1 tw-rounded">{{ $check['label'] }}</code>
                                </td>
                                <td class="tw-px-5 tw-py-3 tw-text-gray-600">{{ $check['hint'] }}</td>
                                <td class="tw-px-5 tw-py-3 tw-text-center">
                                    @if($check['status'] === 'ok')
                                        <span class="tw-inline-flex tw-items-center tw-px-2 tw-py-0.5 tw-rounded-full tw-text-base tw-font-medium tw-bg-green-100 tw-text-green-700"><i class="fas fa-check tw-mr-1"></i> OK</span>
                                    @elseif($check['status'] === 'missing_optional')
                                        <span class="tw-inline-flex tw-items-center tw-px-2 tw-py-0.5 tw-rounded-full tw-text-base tw-font-medium tw-bg-amber-100 tw-text-amber-700"><i class="fas fa-exclamation-triangle tw-mr-1"></i> Optional Missing</span>
                                    @else
                                        <span class="tw-inline-flex tw-items-center tw-px-2 tw-py-0.5 tw-rounded-full tw-text-base tw-font-medium tw-bg-red-100 tw-text-red-700"><i class="fas fa-times tw-mr-1"></i> Missing</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Active Overrides --}}
            <div class="tw-bg-white tw-rounded-lg tw-shadow tw-border tw-border-gray-200 tw-mb-6 jerry-premium-card">
                <div class="tw-px-5 tw-py-4 tw-border-b tw-border-gray-200">
                    <h3 class="tw-text-base tw-font-semibold tw-text-gray-800">
                        <i class="fas fa-layer-group tw-mr-2 tw-text-blue-500"></i> Active Overrides
                    </h3>
                </div>
                <div class="tw-overflow-x-auto">
                    <table class="tw-w-full tw-text-base">
                        <thead>
                            <tr class="tw-bg-gray-50">
                                <th class="tw-px-5 tw-py-3 tw-text-left tw-text-base tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider">Method</th>
                                <th class="tw-px-5 tw-py-3 tw-text-left tw-text-base tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider">Class</th>
                                <th class="tw-px-5 tw-py-3 tw-text-left tw-text-base tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider">Type</th>
                                <th class="tw-px-5 tw-py-3 tw-text-left tw-text-base tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider">Description</th>
                                <th class="tw-px-5 tw-py-3 tw-text-center tw-text-base tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="tw-divide-y tw-divide-gray-200">
                            @foreach($overrides as $override)
                            <tr class="hover:tw-bg-gray-50 tw-transition-colors">
                                <td class="tw-px-5 tw-py-3">
                                    <code class="tw-text-base tw-bg-blue-50 tw-text-blue-700 tw-px-2 tw-py-1 tw-rounded tw-font-mono">{{ $override['method'] }}</code>
                                </td>
                                <td class="tw-px-5 tw-py-3 tw-text-gray-700">
                                    <span class="tw-font-medium">{{ $override['class'] }}</span>
                                    <br><span class="tw-text-base tw-text-gray-400">extends {{ $override['extends'] }}</span>
                                </td>
                                <td class="tw-px-5 tw-py-3">
                                    <span class="tw-inline-flex tw-items-center tw-px-2 tw-py-0.5 tw-rounded tw-text-base tw-font-medium
                                        @if($override['type'] === 'Service Binding') tw-bg-purple-100 tw-text-purple-700
                                        @elseif($override['type'] === 'View Injection') tw-bg-teal-100 tw-text-teal-700
                                        @else tw-bg-gray-100 tw-text-gray-600
                                        @endif
                                    ">{{ $override['type'] }}</span>
                                </td>
                                <td class="tw-px-5 tw-py-3 tw-text-gray-600 tw-text-base tw-max-w-xs">{{ $override['description'] }}</td>
                                <td class="tw-px-5 tw-py-3 tw-text-center">
                                    @if($override['status'] === 'active')
                                        <span class="tw-inline-flex tw-items-center tw-px-2 tw-py-0.5 tw-rounded-full tw-text-base tw-font-medium tw-bg-green-100 tw-text-green-700">
                                            <i class="fas fa-check-circle tw-mr-1"></i> Active
                                        </span>
                                    @elseif($override['status'] === 'synced')
                                        <span class="tw-inline-flex tw-items-center tw-px-2 tw-py-0.5 tw-rounded-full tw-text-base tw-font-medium tw-bg-blue-100 tw-text-blue-700">
                                            <i class="fas fa-sync-alt tw-mr-1"></i> Synced
                                        </span>
                                    @elseif($override['status'] === 'disabled')
                                        <span class="tw-inline-flex tw-items-center tw-px-2 tw-py-0.5 tw-rounded-full tw-text-base tw-font-medium tw-bg-gray-100 tw-text-gray-500">
                                            <i class="fas fa-times-circle tw-mr-1"></i> Disabled
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Bug Fixes Detail --}}
            <div class="tw-bg-white tw-rounded-lg tw-shadow tw-border tw-border-gray-200 tw-mb-6">
                <div class="tw-px-5 tw-py-4 tw-border-b tw-border-gray-200">
                    <h3 class="tw-text-base tw-font-semibold tw-text-gray-800">
                            <i class="fas fa-bug tw-mr-2 tw-text-amber-500"></i> Bug Fixes (Tweaks)
                    </h3>
                </div>
                <div class="tw-p-5 tw-space-y-4">
                    @foreach($bugFixes as $fix)
                    <div class="tw-border tw-border-gray-200 tw-rounded-lg tw-p-4 hover:tw-border-amber-300 tw-transition-colors">
                        <div class="tw-flex tw-items-start tw-justify-between tw-mb-2">
                            <div>
                                <span class="tw-inline-flex tw-items-center tw-px-2 tw-py-0.5 tw-rounded tw-text-base tw-font-mono tw-font-medium tw-bg-gray-800 tw-text-gray-100 tw-mr-2">{{ $fix['id'] }}</span>
                                <span class="tw-font-semibold tw-text-gray-800">{{ $fix['title'] }}</span>
                            </div>
                            <div class="tw-flex tw-gap-2">
                                @if($fix['severity'] === 'high')
                                    <span class="tw-inline-flex tw-items-center tw-px-2 tw-py-0.5 tw-rounded-full tw-text-base tw-font-medium tw-bg-red-100 tw-text-red-700">High</span>
                                @elseif($fix['severity'] === 'medium')
                                    <span class="tw-inline-flex tw-items-center tw-px-2 tw-py-0.5 tw-rounded-full tw-text-base tw-font-medium tw-bg-yellow-100 tw-text-yellow-700">Medium</span>
                                @endif
                                <span class="tw-inline-flex tw-items-center tw-px-2 tw-py-0.5 tw-rounded-full tw-text-base tw-font-medium tw-bg-green-100 tw-text-green-700">
                                    <i class="fas fa-check tw-mr-1"></i> {{ $fix['status'] }}
                                </span>
                            </div>
                        </div>
                        <p class="tw-text-base tw-text-gray-600 tw-mb-3">{{ $fix['description'] }}</p>
                        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-3">
                            <div class="tw-bg-red-50 tw-rounded tw-p-3">
                                <div class="tw-text-base tw-font-semibold tw-text-red-600 tw-mb-1">
                                    <i class="fas fa-times-circle tw-mr-1"></i> Base Code (Bug)
                                </div>
                                <code class="tw-text-base tw-text-red-800 tw-font-mono tw-break-all">{{ $fix['base_code'] }}</code>
                            </div>
                            <div class="tw-bg-green-50 tw-rounded tw-p-3">
                                <div class="tw-text-base tw-font-semibold tw-text-green-600 tw-mb-1">
                                        <i class="fas fa-check-circle tw-mr-1"></i> Tweaks Fix
                                </div>
                                <code class="tw-text-base tw-text-green-800 tw-font-mono tw-break-all">{{ $fix['jerry_code'] }}</code>
                            </div>
                        </div>
                        <div class="tw-mt-2 tw-text-base tw-text-gray-400">
                            <i class="fas fa-map-marker-alt tw-mr-1"></i> {{ $fix['affected_line'] }}
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Custom Views Tab --}}
        <div role="tabpanel" class="tab-pane" id="custom_views_tab">
            <div class="tw-bg-white tw-rounded-lg tw-shadow tw-border tw-border-gray-200 tw-p-6">
                <div class="tw-flex tw-items-center tw-mb-6">
                    <div class="tw-bg-indigo-100 tw-p-3 tw-rounded-lg tw-mr-4">
                        <i class="fas fa-microscope tw-text-indigo-600 tw-text-xl"></i>
                    </div>
                    <div>
                        <h3 class="tw-text-xl tw-font-bold tw-text-gray-900">Migration Memory (Custom Views vs V12 Analysis)</h3>
                        <p class="tw-text-base tw-text-gray-500">Per-file comparison of <code class="tw-text-base">custom_views/</code> against original <code class="tw-text-base">resources/views/</code> with pros, cons and missing features.</p>
                    </div>
                </div>

                <div class="tw-space-y-6">

                    {{-- 1. sell/create --}}
                    <div class="tw-border tw-border-gray-200 tw-rounded-xl tw-p-5">
                        <div class="tw-flex tw-items-center tw-mb-3">
                            <span class="tw-text-xl tw-mr-3">🛒</span>
                            <h4 class="tw-font-bold tw-text-gray-800">sell/create.blade.php</h4>
                            <span class="tw-ml-auto tw-text-base tw-bg-green-100 tw-text-green-700 tw-px-2 tw-py-0.5 tw-rounded-full">+5 / -21 lines</span>
                        </div>
                        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                            <div class="tw-bg-green-50 tw-rounded-lg tw-p-3">
                                <h5 class="tw-text-base tw-font-bold tw-text-green-700 tw-mb-2"><i class="fas fa-plus-circle tw-mr-1"></i> PROS (Custom Additions)</h5>
                                <ul class="tw-text-base tw-text-green-800 tw-space-y-1">
                                    <li>• <b>F8 Keyboard Shortcut</b> — instantly triggers "Save" button</li>
                                    <li>• <b>Status defaults to "final"</b> — skips draft/quotation selection</li>
                                    <li>• <b>Shipping details hidden</b> — cleaner form for non-shipping businesses</li>
                                    <li>• <b>Invoice scheme hidden</b> — prevents accidental scheme changes</li>
                                    <li>• <b>Discount type defaults to "Fixed"</b> — faster entry</li>
                                </ul>
                            </div>
                            <div class="tw-bg-red-50 tw-rounded-lg tw-p-3">
                                <h5 class="tw-text-base tw-font-bold tw-text-red-700 tw-mb-2"><i class="fas fa-exclamation-triangle tw-mr-1"></i> CONS (Missing from V12)</h5>
                                <ul class="tw-text-base tw-text-red-800 tw-space-y-1">
                                    <li>• <b>Upload document field hidden</b> — users can't attach sale documents</li>
                                    <li>• Extra closing <code>&lt;/div&gt;</code> tag may cause layout issues on some pages</li>
                                    <li>• No significant v12 logic missing — mostly UI simplifications</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {{-- 2. expense/create --}}
                    <div class="tw-border tw-border-gray-200 tw-rounded-xl tw-p-5">
                        <div class="tw-flex tw-items-center tw-mb-3">
                            <span class="tw-text-xl tw-mr-3">💸</span>
                            <h4 class="tw-font-bold tw-text-gray-800">expense/create.blade.php</h4>
                            <span class="tw-ml-auto tw-text-base tw-bg-red-100 tw-text-red-700 tw-px-2 tw-py-0.5 tw-rounded-full">+59 / -107 lines ⚠️</span>
                        </div>
                        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                            <div class="tw-bg-green-50 tw-rounded-lg tw-p-3">
                                <h5 class="tw-text-base tw-font-bold tw-text-green-700 tw-mb-2"><i class="fas fa-plus-circle tw-mr-1"></i> PROS (Custom Additions)</h5>
                                <ul class="tw-text-base tw-text-green-800 tw-space-y-1">
                                    <li>• <b>Quick-Add Expense Category</b> — inline modal to create categories on-the-fly</li>
                                    <li>• <b>Amount Auto-Sync</b> — total amount automatically fills the payment field</li>
                                    <li>• <b>Expense category modal container</b> — added at bottom of page</li>
                                </ul>
                            </div>
                            <div class="tw-bg-red-50 tw-rounded-lg tw-p-3">
                                <h5 class="tw-text-base tw-font-bold tw-text-red-700 tw-mb-2"><i class="fas fa-exclamation-triangle tw-mr-1"></i> CONS (Missing from V12)</h5>
                                <ul class="tw-text-base tw-text-red-800 tw-space-y-1">
                                    <li>• <b>Sub-category dropdown MISSING</b> — v12 has expense sub-categories, custom doesn't</li>
                                    <li>• <b>Final total field ID different</b> — custom uses <code>expense_final_total</code> vs v12's <code>final_total</code></li>
                                    <li>• JS structure rewritten — potential issues with future v12 JS updates</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {{-- 3. product/create + edit --}}
                    <div class="tw-border tw-border-gray-200 tw-rounded-xl tw-p-5">
                        <div class="tw-flex tw-items-center tw-mb-3">
                            <span class="tw-text-xl tw-mr-3">📦</span>
                            <h4 class="tw-font-bold tw-text-gray-800">product/create.blade.php & edit.blade.php</h4>
                            <span class="tw-ml-auto tw-text-base tw-bg-yellow-100 tw-text-yellow-700 tw-px-2 tw-py-0.5 tw-rounded-full">+31 / -46 lines</span>
                        </div>
                        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                            <div class="tw-bg-green-50 tw-rounded-lg tw-p-3">
                                <h5 class="tw-text-base tw-font-bold tw-text-green-700 tw-mb-2"><i class="fas fa-plus-circle tw-mr-1"></i> PROS (Custom Additions)</h5>
                                <ul class="tw-text-base tw-text-green-800 tw-space-y-1">
                                    <li>• <b>Quick-Add HSN Category</b> — plus button to add categories inline</li>
                                    <li>• <b>Manual dropdown rendering</b> — custom fields use foreach loop for better control</li>
                                </ul>
                            </div>
                            <div class="tw-bg-red-50 tw-rounded-lg tw-p-3">
                                <h5 class="tw-text-base tw-font-bold tw-text-red-700 tw-mb-2"><i class="fas fa-exclamation-triangle tw-mr-1"></i> CONS (Missing from V12)</h5>
                                <ul class="tw-text-base tw-text-red-800 tw-space-y-1">
                                    <li>• <b>Custom field dropdowns break select2</b> — v12 uses <code>Form::select</code> with select2 class, custom uses raw <code>&lt;select&gt;</code></li>
                                    <li>• <b>Missing placeholder option</b> — v12 dropdowns have a placeholder, custom doesn't</li>
                                    <li>• Original category section commented out instead of cleanly replaced</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {{-- 4. contact/create --}}
                    <div class="tw-border tw-border-gray-200 tw-rounded-xl tw-p-5">
                        <div class="tw-flex tw-items-center tw-mb-3">
                            <span class="tw-text-xl tw-mr-3">👤</span>
                            <h4 class="tw-font-bold tw-text-gray-800">contact/create.blade.php</h4>
                            <span class="tw-ml-auto tw-text-base tw-bg-yellow-100 tw-text-yellow-700 tw-px-2 tw-py-0.5 tw-rounded-full">+9 / -11 lines</span>
                        </div>
                        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                            <div class="tw-bg-green-50 tw-rounded-lg tw-p-3">
                                <h5 class="tw-text-base tw-font-bold tw-text-green-700 tw-mb-2"><i class="fas fa-plus-circle tw-mr-1"></i> PROS (Custom Additions)</h5>
                                <ul class="tw-text-base tw-text-green-800 tw-space-y-1">
                                    <li>• <b>Business name hidden for individuals</b> — faster retail customer entry</li>
                                    <li>• <b>Address columns col-md-3</b> — fits more fields per row</li>
                                </ul>
                            </div>
                            <div class="tw-bg-red-50 tw-rounded-lg tw-p-3">
                                <h5 class="tw-text-base tw-font-bold tw-text-red-700 tw-mb-2"><i class="fas fa-exclamation-triangle tw-mr-1"></i> CONS (Missing from V12)</h5>
                                <ul class="tw-text-base tw-text-red-800 tw-space-y-1">
                                    <li>• <b>First name validation REMOVED</b> — no longer required, risking blank contacts</li>
                                    <li>• <b>additional_number label changed</b> — uses wrong translation key <code>additional_number</code> instead of v12's <code>additional_number_secondary</code></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {{-- 5. Receipts --}}
                    <div class="tw-border tw-border-gray-200 tw-rounded-xl tw-p-5">
                        <div class="tw-flex tw-items-center tw-mb-3">
                            <span class="tw-text-xl tw-mr-3">🧾</span>
                            <h4 class="tw-font-bold tw-text-gray-800">sale_pos/receipts/ (slim, elegant, detailed, classic)</h4>
                            <span class="tw-ml-auto tw-text-base tw-bg-red-100 tw-text-red-700 tw-px-2 tw-py-0.5 tw-rounded-full">~580 lines changed ⚠️</span>
                        </div>
                        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                            <div class="tw-bg-green-50 tw-rounded-lg tw-p-3">
                                <h5 class="tw-text-base tw-font-bold tw-text-green-700 tw-mb-2"><i class="fas fa-plus-circle tw-mr-1"></i> PROS (Custom Additions)</h5>
                                <ul class="tw-text-base tw-text-green-800 tw-space-y-1">
                                    <li>• <b>Custom QR/UPI layout</b> — side-by-side QR code with "Scan to Pay" label</li>
                                    <li>• <b>Invoice alignment tweaked</b> — f-center instead of f-right for invoice number</li>
                                    <li>• <b>Custom v2/v3 receipt variants</b> — elegantv2, elegantv3, detailedv2 templates</li>
                                </ul>
                            </div>
                            <div class="tw-bg-red-50 tw-rounded-lg tw-p-3">
                                <h5 class="tw-text-base tw-font-bold tw-text-red-700 tw-mb-2"><i class="fas fa-exclamation-triangle tw-mr-1"></i> CONS (Missing from V12)</h5>
                                <ul class="tw-text-base tw-text-red-800 tw-space-y-1">
                                    <li>• <b>total_quantity_label MISSING</b> — v12 displays quantity count on receipts</li>
                                    <li>• <b>hide_price check MISSING</b> — v12 conditionally hides prices, custom doesn't</li>
                                    <li>• <b>Discount/line discount sections removed</b> — receipts won't show discounts properly</li>
                                    <li>• <b>Heavily restructured</b> — hard to merge future v12 receipt updates</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {{-- 6. labels/show --}}
                    <div class="tw-border tw-border-gray-200 tw-rounded-xl tw-p-5">
                        <div class="tw-flex tw-items-center tw-mb-3">
                            <span class="tw-text-xl tw-mr-3">🏷️</span>
                            <h4 class="tw-font-bold tw-text-gray-800">labels/show.blade.php</h4>
                            <span class="tw-ml-auto tw-text-base tw-bg-yellow-100 tw-text-yellow-700 tw-px-2 tw-py-0.5 tw-rounded-full">Defaults changed</span>
                        </div>
                        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                            <div class="tw-bg-green-50 tw-rounded-lg tw-p-3">
                                <h5 class="tw-text-base tw-font-bold tw-text-green-700 tw-mb-2"><i class="fas fa-plus-circle tw-mr-1"></i> PROS (Custom Additions)</h5>
                                <ul class="tw-text-base tw-text-green-800 tw-space-y-1">
                                    <li>• <b>Larger default font sizes</b> — name (12→15), price (12→17), business (12→20)</li>
                                    <li>• <b>CSRF token added</b> — security improvement in form submission</li>
                                </ul>
                            </div>
                            <div class="tw-bg-red-50 tw-rounded-lg tw-p-3">
                                <h5 class="tw-text-base tw-font-bold tw-text-red-700 tw-mb-2"><i class="fas fa-exclamation-triangle tw-mr-1"></i> CONS (Missing from V12)</h5>
                                <ul class="tw-text-base tw-text-red-800 tw-space-y-1">
                                    <li>• <b>Business name checkbox defaults to OFF</b> — value="0" vs v12's value="1"</li>
                                    <li>• <b>Packing date checkbox defaults to OFF</b> — value="0" vs v12's value="1"</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {{-- 7. invoice_layout/edit --}}
                    <div class="tw-border tw-border-gray-200 tw-rounded-xl tw-p-5">
                        <div class="tw-flex tw-items-center tw-mb-3">
                            <span class="tw-text-xl tw-mr-3">📋</span>
                            <h4 class="tw-font-bold tw-text-gray-800">invoice_layout/edit.blade.php</h4>
                            <span class="tw-ml-auto tw-text-base tw-bg-green-100 tw-text-green-700 tw-px-2 tw-py-0.5 tw-rounded-full">+18 lines (custom only)</span>
                        </div>
                        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                            <div class="tw-bg-green-50 tw-rounded-lg tw-p-3">
                                <h5 class="tw-text-base tw-font-bold tw-text-green-700 tw-mb-2"><i class="fas fa-plus-circle tw-mr-1"></i> PROS (Custom Additions)</h5>
                                <ul class="tw-text-base tw-text-green-800 tw-space-y-1">
                                    <li>• <b>UPI ID field</b> — stores UPI payment address in common_settings</li>
                                    <li>• <b>UPI Payment QR checkbox</b> — enables/disables QR code on invoices</li>
                                </ul>
                            </div>
                            <div class="tw-bg-red-50 tw-rounded-lg tw-p-3">
                                <h5 class="tw-text-base tw-font-bold tw-text-red-700 tw-mb-2"><i class="fas fa-exclamation-triangle tw-mr-1"></i> CONS</h5>
                                <ul class="tw-text-base tw-text-red-800 tw-space-y-1">
                                    <li>• No v12 features missing — this is purely additive</li>
                                    <li>• UPI QR logic may need backend controller support to function</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {{-- 8. POS form actions --}}
                    <div class="tw-border tw-border-gray-200 tw-rounded-xl tw-p-5">
                        <div class="tw-flex tw-items-center tw-mb-3">
                            <span class="tw-text-xl tw-mr-3">🖥️</span>
                            <h4 class="tw-font-bold tw-text-gray-800">sale_pos/partials/pos_form_actions.blade.php</h4>
                            <span class="tw-ml-auto tw-text-base tw-bg-yellow-100 tw-text-yellow-700 tw-px-2 tw-py-0.5 tw-rounded-full">Commented-out features</span>
                        </div>
                        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                            <div class="tw-bg-green-50 tw-rounded-lg tw-p-3">
                                <h5 class="tw-text-base tw-font-bold tw-text-green-700 tw-mb-2"><i class="fas fa-plus-circle tw-mr-1"></i> PROS (Custom Additions)</h5>
                                <ul class="tw-text-base tw-text-green-800 tw-space-y-1">
                                    <li>• <b>"Clear Cache" button</b> — added (but commented out) for cache clearing from POS</li>
                                    <li>• <b>"Add New Sale" button</b> — added (but commented out) for quick new sale</li>
                                </ul>
                            </div>
                            <div class="tw-bg-red-50 tw-rounded-lg tw-p-3">
                                <h5 class="tw-text-base tw-font-bold tw-text-red-700 tw-mb-2"><i class="fas fa-exclamation-triangle tw-mr-1"></i> CONS</h5>
                                <ul class="tw-text-base tw-text-red-800 tw-space-y-1">
                                    <li>• Both features are <b>commented out</b> — not active, dead code</li>
                                    <li>• Includes unused <code>openInNewTab()</code> JS function</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Summary Table --}}
                <div class="tw-mt-8">
                    <h4 class="tw-font-bold tw-text-gray-800 tw-mb-3"><i class="fas fa-clipboard-list tw-mr-2"></i> Risk Summary</h4>
                    <div class="tw-overflow-x-auto">
                        <table class="tw-w-full tw-text-base">
                            <thead>
                                <tr class="tw-bg-gray-50">
                                    <th class="tw-px-3 tw-py-2 tw-text-left tw-font-medium tw-text-gray-500">File</th>
                                    <th class="tw-px-3 tw-py-2 tw-text-center tw-font-medium tw-text-gray-500">Risk</th>
                                    <th class="tw-px-3 tw-py-2 tw-text-left tw-font-medium tw-text-gray-500">Action Needed</th>
                                </tr>
                            </thead>
                            <tbody class="tw-divide-y tw-divide-gray-200">
                                <tr>
                                    <td class="tw-px-3 tw-py-2"><code>sell/create</code></td>
                                    <td class="tw-px-3 tw-py-2 tw-text-center"><span class="tw-bg-green-100 tw-text-green-700 tw-px-2 tw-py-0.5 tw-rounded-full">Upgraded</span></td>
                                    <td class="tw-px-3 tw-py-2 tw-text-gray-600">Rebuilt from v12. Custom tweaks now toggleable.</td>
                                </tr>
                                <tr>
                                    <td class="tw-px-3 tw-py-2"><code>expense/create</code></td>
                                    <td class="tw-px-3 tw-py-2 tw-text-center"><span class="tw-bg-green-100 tw-text-green-700 tw-px-2 tw-py-0.5 tw-rounded-full">Upgraded</span></td>
                                    <td class="tw-px-3 tw-py-2 tw-text-gray-600">Rebuilt from v12. Category quick-add toggleable.</td>
                                </tr>
                                <tr>
                                    <td class="tw-px-3 tw-py-2"><code>product/create & product/edit</code></td>
                                    <td class="tw-px-3 tw-py-2 tw-text-center"><span class="tw-bg-green-100 tw-text-green-700 tw-px-2 tw-py-0.5 tw-rounded-full">Upgraded</span></td>
                                    <td class="tw-px-3 tw-py-2 tw-text-gray-600">Rebuilt from v12. Select2 fixed. Quick-add toggleable.</td>
                                </tr>
                                <tr>
                                    <td class="tw-px-3 tw-py-2"><code>contact/create</code></td>
                                    <td class="tw-px-3 tw-py-2 tw-text-center"><span class="tw-bg-green-100 tw-text-green-700 tw-px-2 tw-py-0.5 tw-rounded-full">Upgraded</span></td>
                                    <td class="tw-px-3 tw-py-2 tw-text-gray-600">Rebuilt from v12. Business group customizations preserved.</td>
                                </tr>
                                <tr>
                                    <td class="tw-px-3 tw-py-2"><code>invoice_layout/edit</code></td>
                                    <td class="tw-px-3 tw-py-2 tw-text-center"><span class="tw-bg-green-100 tw-text-green-700 tw-px-2 tw-py-0.5 tw-rounded-full">Upgraded</span></td>
                                    <td class="tw-px-3 tw-py-2 tw-text-gray-600">Rebuilt from v12. Added UPI settings fields cleanly via toggle.</td>
                                </tr>
                                <tr>
                                    <td class="tw-px-3 tw-py-2"><code>labels/show</code></td>
                                    <td class="tw-px-3 tw-py-2 tw-text-center"><span class="tw-bg-green-100 tw-text-green-700 tw-px-2 tw-py-0.5 tw-rounded-full">Upgraded</span></td>
                                    <td class="tw-px-3 tw-py-2 tw-text-gray-600">Rebuilt from v12. Default labels/sizes mapped to a toggle.</td>
                                </tr>
                                <tr>
                                    <td class="tw-px-3 tw-py-2"><code>sale_pos/create</code></td>
                                    <td class="tw-px-3 tw-py-2 tw-text-center"><span class="tw-bg-blue-100 tw-text-blue-700 tw-px-2 tw-py-0.5 tw-rounded-full">Clean v12</span></td>
                                    <td class="tw-px-3 tw-py-2 tw-text-gray-600">Reverted completely to original v12 source to remove buggy code.</td>
                                </tr>
                                <tr>
                                    <td class="tw-px-3 tw-py-2"><code>sale_pos/partials/*</code><br>(edit_discount_modal, keyboard_shortcuts, pos_form_actions)</td>
                                    <td class="tw-px-3 tw-py-2 tw-text-center"><span class="tw-bg-blue-100 tw-text-blue-700 tw-px-2 tw-py-0.5 tw-rounded-full">Clean v12</span></td>
                                    <td class="tw-px-3 tw-py-2 tw-text-gray-600">Reverted to original v12 sources as no active custom features were needed.</td>
                                </tr>
                                <tr>
                                    <td class="tw-px-3 tw-py-2"><code>layouts/partials/*</code><br>(footer_pos, header-pos, header)</td>
                                    <td class="tw-px-3 tw-py-2 tw-text-center"><span class="tw-bg-blue-100 tw-text-blue-700 tw-px-2 tw-py-0.5 tw-rounded-full">Clean v12</span></td>
                                    <td class="tw-px-3 tw-py-2 tw-text-gray-600">Reverted completely to original v12 sources to maintain core stability.</td>
                                </tr>
                                <tr>
                                    <td class="tw-px-3 tw-py-2"><code>labels/partials/*</code><br>(preview, preview_2, show_table_rows)</td>
                                    <td class="tw-px-3 tw-py-2 tw-text-center"><span class="tw-bg-blue-100 tw-text-blue-700 tw-px-2 tw-py-0.5 tw-rounded-full">Clean v12</span></td>
                                    <td class="tw-px-3 tw-py-2 tw-text-gray-600">Reverted to v12 sources. Custom additions were non-functional or duplicated.</td>
                                </tr>
                                <tr>
                                    <td class="tw-px-3 tw-py-2"><code>product/partials/quick_add_product</code></td>
                                    <td class="tw-px-3 tw-py-2 tw-text-center"><span class="tw-bg-blue-100 tw-text-blue-700 tw-px-2 tw-py-0.5 tw-rounded-full">Clean v12</span></td>
                                    <td class="tw-px-3 tw-py-2 tw-text-gray-600">Reverted completely to original v12 source.</td>
                                </tr>
                                <tr>
                                    <td class="tw-px-3 tw-py-2"><code>expense/add_expense_modal</code></td>
                                    <td class="tw-px-3 tw-py-2 tw-text-center"><span class="tw-bg-blue-100 tw-text-blue-700 tw-px-2 tw-py-0.5 tw-rounded-full">Clean v12</span></td>
                                    <td class="tw-px-3 tw-py-2 tw-text-gray-600">Reverted entirely to the v12 codebase state. No changes needed.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Untouched Files Form --}}
                <div class="tw-mt-8 tw-bg-gray-50 tw-rounded-lg tw-p-5 tw-border tw-border-gray-200">
                    <h4 class="tw-font-bold tw-text-gray-800 tw-mb-3"><i class="fas fa-shield-alt tw-mr-2 tw-text-blue-500"></i> Untouched Custom Views</h4>
                    <p class="tw-text-base tw-text-gray-600 tw-mb-4">The following files were deliberately <strong>NOT</strong> rebuilt from V12 source files in order to preserve their completely custom UI/UX structures:</p>
                    <ul class="tw-list-disc tw-pl-5 tw-text-base tw-text-gray-700 tw-space-y-1">
                        <li><code>sale_pos/receipts/classic.blade.php</code> — (Fully customized UI)</li>
                        <li><code>sale_pos/receipts/slim.blade.php</code> — (Fully customized UI)</li>
                        <li><code>sale_pos/receipts/detailed.blade.php</code> — (Fully customized UI)</li>
                        <li><code>sale_pos/receipts/elegant.blade.php</code> — (Fully customized UI)</li>
                        <li><code>sale_pos/receipts/elegantv2.blade.php</code> — (Custom variant)</li>
                        <li><code>sale_pos/receipts/elegantv3.blade.php</code> — (Custom variant)</li>
                        <li><code>sale_pos/receipts/detailedv2.blade.php</code> — (Custom variant)</li>
                        <li><code>home/index.blade.php</code> — (Fully custom dashboard layout)</li>
                        <li><code>home/indexv2.blade.php</code> — (Fully custom dashboard layout)</li>
                    </ul>
                    <div class="tw-mt-4 tw-p-3 tw-bg-blue-50 tw-text-blue-800 tw-text-base tw-rounded tw-border tw-border-blue-100">
                        <i class="fas fa-info-circle tw-mr-1"></i> All other custom views (19 files) were successfully upgraded to V12 base and tagged with <code>&lt;!-- Jerry Updated --&gt;</code> at the top of the file.
                    </div>
                </div>

                <div class="tw-mt-6 tw-p-4 tw-bg-amber-50 tw-rounded-lg tw-border tw-border-amber-200">
                    <p class="tw-text-base tw-text-amber-700 tw-flex tw-items-start">
                        <i class="fas fa-exclamation-triangle tw-mr-2 tw-mt-0.5"></i> 
                        <span><b>Priority:</b> The receipt templates and expense/create have the highest risk of missing v12 functionality. Consider re-syncing these files with the v12 originals while preserving the custom additions (QR/UPI, quick-add category, amount sync).</span>
                    </p>
                </div>
            </div>
        </div>

        {{-- Update Log Tab --}}
        <div role="tabpanel" class="tab-pane" id="update_log_tab">
            <div class="tw-bg-white tw-rounded-lg tw-shadow tw-border tw-border-gray-200 tw-mt-6 tw-mb-6">
                <div class="tw-px-5 tw-py-4 tw-border-b tw-border-gray-200">
                    <h3 class="tw-text-base tw-font-semibold tw-text-gray-800">
                        <i class="fas fa-history tw-mr-2 tw-text-green-500"></i> Complete Update History
                    </h3>
                </div>
                <div class="tw-p-5">
                    @if(isset($changelogs) && count($changelogs) > 0)
                        <div class="tw-space-y-6">
                            @foreach($changelogs as $log)
                            <div class="tw-relative tw-pl-4 tw-border-l-2 tw-border-green-400">
                                <div class="tw-absolute tw-w-3 tw-h-3 tw-bg-green-500 tw-rounded-full tw--left-[7px] tw-top-1.5 tw-ring-4 tw-ring-white"></div>
                                <div class="tw-flex tw-items-center tw-mb-1">
                                    <h4 class="tw-text-base tw-font-bold tw-text-gray-900 tw-mr-2">Version {{ $log['version'] }}</h4>
                                    <span class="tw-text-base tw-text-gray-500 tw-bg-gray-100 tw-px-2 tw-py-0.5 tw-rounded">{{ $log['date'] }}</span>
                                </div>
                                <ul class="tw-list-disc tw-list-inside tw-text-base tw-text-gray-600 tw-mt-2 tw-space-y-1">
                                    @foreach($log['features'] as $feature)
                                    <li>{{ $feature }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <p class="tw-text-base tw-text-gray-500 tw-italic">No update logs found.</p>
                    @endif
                </div>
            </div>
        </div>
        @include('jerryupdates::documentation')
    </div>
</section>

@endsection

