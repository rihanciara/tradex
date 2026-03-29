# JerryUpdates Module

This module serves as a catch-all container for upgrade-safe UI tweaks, JS modifications, and feature patches for the Ultimate POS platform, specifically tailored for a multi-tenant (SaaS) architecture.

## Included Patches & Modifications

The module dynamically loads custom scripts based on the active page to avoid bloating the global application state. 

### POS Terminal Enhancements (Thufail's Patches)
The POS interface (`pos/create` / `sells/create`) has been severely optimized with the following patches applied by Thufail, integrated directly via `javascript_tweaks.blade.php`:

1. **Full Product Cache (IndexedDB/localStorage):**
    - The entire product catalog for a specific business location is fetched via Ajax once and cached locally on the client for 24 hours.
    - Subsequent product searches operate locally (0ms network latency), dramatically speeding up the POS interface.
    - **Multi-tenant Aware:** Cache keys utilize both the `business_id` and `location_id` (`jerry_products_biz_X_loc_Y`) to prevent cross-business data leakage.
    - Handles advanced product data mapping, including **Price Group (Wholesale, Retail)** overrides seamlessly.

2. **Auto-Select Removal for Text Searches:**
    - Standard Ultimate POS logic auto-selects the first search result if it's the only one found. This is problematic for text inputs (e.g. searching for "Apple" automatically rings up a random single product match).
    - **Patch:** Thufail's logic restricts auto-select strictly to numeric searches (representing barcode scans or exact `product_id` matches). Text searches will strictly open the dropdown for manual validation.

3. **No-Match Error Spam Removal:**
    - Removed the obtrusive `No products found` toastr pop-ups that execute on every keystroke when typing an un-cached product string.

### Settings Management
A new settings dashboard tab has been added dynamically via `JerryUpdates::settings`. This allows the Super Admin or Business owner to forcefully reset the `pos_cache` locally or disable visual POS elements without touching core files.
