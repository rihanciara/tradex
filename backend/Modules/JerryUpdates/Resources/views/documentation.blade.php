        {{-- Documentation Tab --}}
        <div role="tabpanel" class="tab-pane" id="documentation_tab">
            
            {{-- Feature Directory Section --}}
            <div class="tw-bg-white tw-rounded-lg tw-shadow tw-border tw-border-gray-200 tw-mt-6 tw-mb-6 jerry-premium-card">
                <div class="tw-px-5 tw-py-4 tw-border-b tw-border-gray-200 tw-bg-gray-50 tw-rounded-t-lg">
                    <h3 class="tw-text-lg tw-font-bold tw-text-indigo-900">
                        <i class="fas fa-sitemap tw-mr-2 tw-text-indigo-600"></i> Full Plugin Capabilities Directory
                    </h3>
                    <p class="tw-text-sm tw-text-gray-500 tw-m-0 tw-mt-1">A comprehensive map of every major enhancement and architecture upgrade achieved within this module.</p>
                </div>
                <div class="tw-p-5 tw-space-y-6">
                    
                    <div>
                        <h4 class="tw-text-base tw-font-bold tw-text-gray-800 tw-flex tw-items-center tw-mb-2">
                            <span class="tw-bg-blue-100 tw-text-blue-600 tw-w-8 tw-h-8 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mr-3"><i class="fas fa-shield-alt"></i></span>
                            Accounting Hardening & Ledger Stability
                        </h4>
                        <ul class="tw-list-disc tw-pl-12 tw-text-gray-600 tw-space-y-1">
                            <li><b>Deterministic Payment-Ledger Sync:</b> Eliminates data drift and duplicate phantom ledger entries on concurrent saves.</li>
                            <li><b>Advance Payment Ledger Fix:</b> Corrects upstream balance calculation anomalies during advance payments.</li>
                            <li><b>Running Balance Column:</b> Injects an accurate running balance column directly into the client ledger view.</li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="tw-text-base tw-font-bold tw-text-gray-800 tw-flex tw-items-center tw-mb-2">
                            <span class="tw-bg-emerald-100 tw-text-emerald-600 tw-w-8 tw-h-8 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mr-3"><i class="fas fa-wifi"></i></span>
                            POS Offline Queue & Engine Speed
                        </h4>
                        <ul class="tw-list-disc tw-pl-12 tw-text-gray-600 tw-space-y-1">
                            <li><b>IndexedDB Offline Sales Queue:</b> Intercepts network failures, saves POS transactions locally, and auto-syncs when reconnecting.</li>
                            <li><b>Server Heartbeat:</b> Background connectivity pinging mechanism to accurately route POS saves and eliminate false-offline states.</li>
                            <li><b>LocalStorage Speed Cache:</b> Pre-warms POS searches by loading all business products into browser memory for instant barcode/SKU matching.</li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="tw-text-base tw-font-bold tw-text-gray-800 tw-flex tw-items-center tw-mb-2">
                            <span class="tw-bg-gray-200 tw-text-gray-700 tw-w-8 tw-h-8 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mr-3"><i class="fas fa-compress-arrows-alt"></i></span>
                            POS UI Densification & Features
                        </h4>
                        <ul class="tw-list-disc tw-pl-12 tw-text-gray-600 tw-space-y-1">
                            <li><b>Compact Rendering:</b> Removes images, +/- qty buttons, and unit dropdowns to drastically halve row height in POS.</li>
                            <li><b>Cart Purchase Price (PP):</b> Dynamically uncovers and injects the purchase price column directly into the POS cart table.</li>
                            <li><b>Auto-Add & Strict Search:</b> Forces 1-result matches to auto-add to cart, while restricting vague string match selections.</li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="tw-text-base tw-font-bold tw-text-gray-800 tw-flex tw-items-center tw-mb-2">
                            <span class="tw-bg-purple-100 tw-text-purple-600 tw-w-8 tw-h-8 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mr-3"><i class="fas fa-paint-brush"></i></span>
                            Theme & Performance Topologies
                        </h4>
                        <ul class="tw-list-disc tw-pl-12 tw-text-gray-600 tw-space-y-1">
                            <li><b>SaaS System Black Theme:</b> Overrides user preferences to force modern dark headers across all tenants.</li>
                            <li><b>Low-End PC Mode:</b> Debloats the application by purging CSS layout transitions, hover box-shadows, and jQuery fade/slide animations.</li>
                            <li><b>Native Dark Mode:</b> Supplies `prefers-color-scheme: dark` filters for night-time operation.</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Legacy Architecture Documentation --}}
            <div class="tw-bg-white tw-rounded-lg tw-shadow tw-border tw-border-gray-200 tw-mb-6 jerry-premium-card">
                <div class="tw-px-5 tw-py-4 tw-border-b tw-border-gray-200 tw-bg-gray-50 ">
                    <h3 class="tw-text-base tw-font-semibold tw-text-gray-800">
                        <i class="fas fa-book tw-mr-2 tw-text-blue-500"></i> Dual-Mode Override Engine Architecture
                    </h3>
                </div>
                <div class="tw-p-5">
                    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-8">
                        <div>
                            <h4 class="tw-text-lg tw-font-bold tw-text-gray-800 tw-mb-3">1. Traditional Mode (Blade Overrides)</h4>
                            <p class="tw-text-gray-600 tw-mb-4">
                                When <b>Traditional Mode</b> is enabled, the site uses the custom Blade files located in the <code class="tw-bg-gray-100 tw-px-1 tw-rounded">custom_views/</code> directory. 
                                These files completely replace the original V12 vendor templates.
                            </p>
                            <div class="tw-bg-indigo-50 tw-p-4 tw-rounded-lg tw-border tw-border-indigo-100">
                                <h5 class="tw-text-indigo-800 tw-font-bold tw-text-base tw-mb-2">How it works:</h5>
                                <pre class="tw-text-xs tw-bg-gray-800 tw-text-gray-100 tw-p-2 tw-rounded">{{ '@' }}if($jerry_traditional_mode == '1')
    &lt;!-- Custom UI Logic --&gt;
    &lt;div class="jerry-custom-layout"&gt;...&lt;/div&gt;
{{ '@' }}else
    {{ '@' }}include('vendor_file_path')
{{ '@' }}endif</pre>
                            </div>
                        </div>

                        <div>
                            <h4 class="tw-text-lg tw-font-bold tw-text-gray-800 tw-mb-3">2. Upgrade-Safe Mode (JS Injections)</h4>
                            <p class="tw-text-gray-600 tw-mb-4">
                                In <b>Upgrade-Safe Mode</b> (Traditional Mode OFF), the system routes directly to native V12 vendor files. 
                                Customizations are applied via dynamic Javascript DOM manipulations at runtime.
                            </p>
                            <div class="tw-bg-green-50 tw-p-4 tw-rounded-lg tw-border tw-border-green-100">
                                <h5 class="tw-text-green-800 tw-font-bold tw-text-base tw-mb-2">Core Benefits:</h5>
                                <ul class="tw-list-disc tw-pl-5 tw-text-green-900 tw-space-y-1">
                                    <li>Native V12 stability & logic</li>
                                    <li>Zero breaking changes during core updates</li>
                                    <li>Dynamic DOM manipulation via <code class="tw-text-xs">javascript_tweaks.blade.php</code></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <hr class="tw-my-8 tw-border-gray-200">

                    <h4 class="tw-text-lg tw-font-bold tw-text-gray-800 tw-mb-4"><i class="fas fa-terminal tw-mr-2"></i> Developer Notes & Architecture</h4>
                    <div class="tw-bg-gray-800 tw-rounded-lg tw-p-6 tw-text-gray-100">
                        <p class="tw-mb-4"><span class="tw-text-blue-400"># Mass-Retrofitting:</span> All files in <code class="tw-text-blue-300">custom_views/</code> have been recursively wrapped with the <b>Blade Proxy Pattern</b>.</p>
                        <ul class="tw-space-y-2 tw-text-gray-300">
                            <li><i class="fas fa-check tw-text-green-400 tw-mr-2"></i> <b>Proxy Location:</b> Applied to 20+ files in <code class="tw-bg-gray-700 tw-px-1 tw-rounded">custom_views/sale_pos/</code>, <code class="tw-bg-gray-700 tw-px-1 tw-rounded">contact/</code>, and <code class="tw-bg-gray-700 tw-px-1 tw-rounded">product/</code>.</li>
                            <li><i class="fas fa-check tw-text-green-400 tw-mr-2"></i> <b>Hybrid JS Logic:</b> JS tweaks are automatically isolated to "Upgrade-Safe Mode" using Blade conditionals inside <code class="tw-bg-gray-700 tw-px-1 tw-rounded">javascript_tweaks.blade.php</code>.</li>
                            <li><i class="fas fa-check tw-text-green-400 tw-mr-2"></i> <b>SaaS/Multi-Business:</b> Settings are stored in the <code class="tw-bg-gray-700 tw-px-1 tw-rounded">businesses</code> table JSON column for independent tenant toggling.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
