---
name: Ultimate POS Accumulated Knowledge
description: A comprehensive repository of Ultimate POS functionalities, bug fixes, and feature implementations aggregated from the devs25 repository.
---

# Ultimate POS Knowledge Base

This skill folder contains direct documentation and code snippets for over 30 custom features, bug fixes, and modules developed for Ultimate POS (v6 to v12).

Whenever the user requests a new feature or mentions one of these topics, you should **use the `view_file` tool** on the corresponding `.mdx` file located inside the `docs/` subdirectory of this `.agents/skills/ultimate_pos_knowledge/` folder to learn exactly how to implement it correctly.

## Available Modules and Fixes

The `docs/` folder contains step-by-step guides for the following features:

- **Invoice Enhancements**: Watermarks (`adding-a-watermark-feature-to-invoice-templates...`), Delete Invoice Password Protection (`delete-invoice-password-protection...`)
- **Reports & Analytics**: Enhanced Profit by Product (`enhanced-profit-by-products-report...`), Products Sales Report Discount Columns (`adding-discount-columns-to-products-sales-report...`), Customer Monthly Sales Report (`customer-monthly-sales-report...`), Enhanced Dashboard Widgets (`enhanced-performance-dashboard-widgets...`)
- **Product & Stock Management**: Adding Supplier to Products (`adding-supplier-to-products...`), Sub Category Filters (`adding-sub-category-filter...`), Combo Product Auto Addition (`combo-product-auto-addition...`), Multiple Barcodes for Products (`multiple-barcodes-for-products...`), Print Stock Need (`print-stock-need...`), Product Field Visibility (`product-field-visibility-settings...`), Stock Report Enhancements (`stock-report-filter-enhancement...`)
- **POS Interface Updates**: Offline Mode Engine Architecture (`v12-offline-pos-engine-architecture.md`), Upgrade-Safe Frontend Tweaks (`v12-upgrade-safe-frontend-tweaks.md`), Previous Sell Price Display (`previous-sell-price-display...`), Camera Barcode Scanner (`camera-barcode-scanner...`), Edit Customer Button (`edit-customer-button-pos...`), Quick Add Product Modal (`quick-add-product-modal...`), Product Price Check (`product-price-check...`), Product History in POS (`product-history-in-pos...`)
- **Financial Features**: Commission Agent in Sells List (`commission-agent-in-the-sells-list...`), Currency Management System (`currency-management-system...`), Previous Due Amount Display (`previous-due-amount-display...`), Expense Print Functionality (`expense-print-functionality...`)
- **And Many More**: (See the `docs/` directory for the full list of `.mdx` and `.md` files)

### How to use this Skill:
If you are tasked with implementing a feature listed above, simply list the directory `docs/`, find the exact filename, and read the `mdx` file to get the exact code implementation, DB migrations, and required routes. This ensures 100% adherence to the user's expected architecture and reduces bug generation heavily.
