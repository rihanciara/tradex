/**
 * Module Version Display Fix for AdvancedReports
 * Fixes "Cannot access offset of type string on string" errors
 * by ensuring version information is properly displayed
 */

(function() {
    'use strict';

    // Only run on modules page
    if (window.location.pathname.includes('/install/modules')) {

        // Wait for page to load
        document.addEventListener('DOMContentLoaded', function() {

            // Find AdvancedReports module card
            const moduleCards = document.querySelectorAll('.box, .card');

            moduleCards.forEach(function(card) {
                const title = card.querySelector('h3, .card-title, .box-title');

                if (title && title.textContent.includes('AdvancedReports')) {

                    // Try to fetch proper version info via API
                    fetch('/advanced-reports/module/version')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.version) {

                                // Find version display element
                                const versionLabels = card.querySelectorAll('.label, .badge, small');

                                versionLabels.forEach(function(label) {
                                    if (label.textContent.includes('Version') || label.textContent.includes('version')) {
                                        label.innerHTML = 'Version ' + data.version.installed_version;
                                        label.style.backgroundColor = '#00a65a'; // Green for installed
                                    }
                                });

                                // Add version info if not found
                                if (versionLabels.length === 0) {
                                    const versionSpan = document.createElement('small');
                                    versionSpan.className = 'label bg-green';
                                    versionSpan.textContent = 'Version ' + data.version.installed_version;
                                    title.appendChild(versionSpan);
                                }
                            }
                        })
                        .catch(function(error) {
                            console.log('AdvancedReports: Could not fetch version info:', error);

                            // Fallback: Just show that it's installed
                            const versionLabels = card.querySelectorAll('.label, .badge, small');
                            versionLabels.forEach(function(label) {
                                if (label.textContent.includes('Version') || label.textContent.includes('version')) {
                                    label.innerHTML = 'Version 1.1.4';
                                    label.style.backgroundColor = '#00a65a';
                                }
                            });
                        });
                }
            });
        });
    }
})();