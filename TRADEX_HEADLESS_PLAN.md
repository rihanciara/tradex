# Tradex Headless POS Implementation Plan

## Overview
This document outlines the comprehensive, step-by-step strategy for migrating the Ultimate POS / FWCV3 frontend into a modern, decoupled (headless) architecture utilizing a Next.js frontend hosted on Vercel. The backend will serve strictly as an API, with all custom logic encapsulated within the `JerryUpdates` module to ensure upgrade safety.

---

## Phase 1: Authentication & Security (✅ Complete)
The foundation of a headless architecture is secure, stateless communication between the frontend client and the backend API.

- [x] **1.1 Configure CORS:** Laravel backend accepts requests from Vercel frontend domains with full wildcard origin support.
- [x] **1.2 Disable CSRF for API Routes:** `VerifyCsrfToken.php` now bypasses CSRF for `/api/v1/*` and `/api/jerryupdates/*` — fixes the 403 Forbidden error on all POST endpoints (checkout, createCustomer, etc).
- [x] **1.3 Stateless Login Endpoint:** `POST /api/v1/auth/login` issues Laravel Passport Bearer tokens for cross-domain auth.
- [x] **1.4 Token Validation & Session Guards:** Axios interceptor globally handles 401 expiry and redirects to login.
- [x] **1.5 User Profile Endpoint:** `GET /api/v1/pos/profile` returns user details, permitted locations, and roles.
- [x] **1.6 Secure Logout:** Settings modal clears Bearer token cookie + wipes IndexedDB catalog cache on logout.
- [x] **1.7 SSO Bridge (Back to Dashboard):**
  - `GET /api/v1/auth/sso-url` generates a 90-second one-time token stored in **Laravel Cache** (no DB table dependency).
  - `GET /sso/magic-login/{user_id}?token=xxx` consumes the token, creates a full Laravel web session, and redirects to `/home`.
  - **Replaced broken signed-URL approach** (which caused "Invalid signature" 403 due to `APP_URL` http/https mismatch on production).

---

## Phase 2: Core Data Synchronization (✅ Complete)

- [x] **2.1 Initialization Payload** (`GET /api/v1/pos/init`): Business settings, currency, tax rates, payment methods, register status — all delivered in a single cold-start payload.
- [x] **2.2 Product Catalog** (`GET /api/v1/pos/catalog`): Raw hyper-optimized SQL (zero Eloquent overhead) returning flattened product+variation rows with prices, SKUs, stock flags, brands, and categories.
  - **Recursive paginator** bypasses the 1,000-item per-page limit — fully syncs catalogs of 20,000+ products.
  - Results cached in **IndexedDB** (`tradex_pos_db`) with `try/catch` QuotaExceeded protection.
- [x] **2.3 Taxonomies** (`GET /api/v1/pos/taxonomies`): Categories and brands for filter sidebar.
- [x] **2.4 Customer Directory** (`GET /api/v1/pos/customers`): Debounced search across name, mobile, email, contact_id.
- [x] **2.5 Create Customer** (`POST /api/v1/pos/customers`): On-the-fly customer creation with name + mobile. Auto-generates `WI-XXXXX` contact ID. Returns new customer object immediately for cart session assignment.

---

## Phase 3: Transaction Engine (✅ Core Complete)

- [x] **3.1 Checkout Endpoint** (`POST /api/v1/pos/checkout`):
  - Inserts `transactions`, `transaction_sell_lines`, and `transaction_payments` in a single DB transaction.
  - Handles cart-level discounts (fixed + percentage), cart-level taxes, and per-item taxes.
  - Calculates payment status (`paid`, `partial`, `due`) automatically.
  - Decrements `variation_location_details.qty_available` for tracked stock items.
  - Generates `INV-XXXX` invoice numbers.
- [x] **3.2 Express Cash Checkout:**
  - Green "Cash" button in the Cart footer triggers instant checkout without opening the review modal.
  - Auto-sets payment method to `cash` and tendered amount to exact final total.
  - Fires the API immediately and opens the native browser Print dialog within 500ms.
  - Uses Zustand `expressCheckoutTrigger` → `useEffect` in `CheckoutModal` to intercept and auto-submit.
- [x] **3.3 Offline Resilience:** Failed checkouts (no network) are queued in IndexedDB `sync_queue` and returned as `OFFLINE-XXXX` invoices for later sync.
- [ ] **3.4 Cash Register Open/Close** (`POST /register/open`, `POST /register/close`): Deferred.
- [ ] **3.5 Hold / Suspend Sales:** Deferred.

---

## Phase 4: Performance & Offline (✅ Core Complete)

- [x] **4.1 IndexedDB Catalog Cache:** Full catalog cached locally after first load. Subsequent sessions use cached data (1-hour stale time via React Query + IDB fallback).
- [x] **4.2 Render Safety Cap:** `ProductGrid` limits DOM output to 100 items at a time to prevent browser crashes on 20,000+ item catalogs.
- [x] **4.3 Alphabet Filter Ribbon:** Horizontal A-Z scrollable scrubber above the product grid. Clicking a letter instantly cross-filters the catalog (0ms latency, client-side). `#` matches non-alphabetical product names. Stacks with search + category filters.
- [x] **4.4 Background Sync Queue:** Offline transactions queued in IndexedDB and can be synced on reconnect.
- [ ] **4.5 Webhook Cache Invalidation:** Optional — not yet implemented.

---

## Phase 5: Frontend UI (✅ Complete)

- [x] **5.1 Project Scaffold:** Next.js 16 (App Router), TypeScript, Tailwind CSS, Zustand, React Query, Axios, Lucide icons, js-cookie.
- [x] **5.2 Login Page:** Apple-style premium login with animated gradient, username/password → Passport token stored in `auth_token` cookie.
- [x] **5.3 POS Layout:**
  - Full-screen `100dvh` layout with `flex flex-col` structure.
  - **Desktop:** dual-pane (product grid left 2/3, cart right 1/3).
  - **Mobile:** tab-based navigation — [Products] / [Current Bag] switching. Footer tab bar fixed at bottom using `min-h-0` flex columns (fixes the tab bar clipping bug where `h-full` pushed it off-screen).
- [x] **5.4 Product Grid (`ProductGrid.tsx`):**
  - Search bar (name, SKU, barcode).
  - Brand/Category filter sidebar (slide-in drawer).
  - Sticky alphabet A-Z scrubber ribbon.
  - Product cards with name, price, SKU. Tap to add to cart (increments quantity on re-tap).
- [x] **5.5 Cart (`Cart.tsx`):**
  - Full item list with quantity +/– controls and line totals.
  - Cart items scrollable above the fixed footer (fixed the `absolute` positioning problem that hid items behind the checkout button on mobile).
  - Discount modal (defaults to **Fixed Amount**).
  - Cart-level tax selector.
  - Three action buttons:
    - **Review Order** (blue) — opens full checkout modal.
    - **Pay** (dark) — opens checkout modal pre-wired to card.
    - **Cash** (green) — Express Cash instant checkout.
- [x] **5.6 Customer Select (`CustomerSelect.tsx`):**
  - Debounced search dropdown with instant selection.
  - Walk-In Customer always shown as first option (ID: 1).
  - **"+ New Customer" button** always visible at bottom of dropdown → opens inline modal with Name + Mobile fields → creates customer via API → auto-selects in cart session.
- [x] **5.7 Checkout Modal (`CheckoutModal.tsx`):**
  - Order summary (items, subtotal, discount, tax, total).
  - Cash / Card payment method selector.
  - Cash: shows tendered input + change due.
  - Card: shows exact amount.
  - Success screen with receipt printer trigger.
  - Auto-print via `window.print()` 500ms after success.
  - 5-second auto-clear cart + close modal after success.
- [x] **5.8 Receipt Printer (`ReceiptPrinter.tsx`):** `@media print` CSS shows thermal-style receipt with invoice no, date, items, totals, payment method.
- [x] **5.9 Settings Modal (`SettingsModal.tsx`):**
  - User identity display (name, business).
  - **Force Resync**: wipes IndexedDB and reloads.
  - **Back to Dashboard**: SSO bridge button → calls `sso-url` API → redirects to Laravel `/home`.
  - **Logout**: clears token cookie + IDB + cart → redirects to `/login`.

---

## Phase 6: Testing & Deployment (🔄 In Progress)

- [ ] **6.1 API Integration Tests:** PHPUnit tests for each endpoint (pending).
- [ ] **6.2 Frontend E2E Tests:** Cypress/Playwright critical path (pending).
- [x] **6.3 Vercel Deployment:** Auto-deploys from `main` branch on GitHub. Live at configured Vercel domain.
- [ ] **6.4 Production Cutover & Staff Training:** Pending.

---

## Known Issues & Next Actions

| # | Item | Status |
|---|------|--------|
| 1 | `POST /pos/checkout` returns 403 on production | ✅ Fixed — CSRF bypass added for `/api/v1/*` in `VerifyCsrfToken.php`. Requires `git pull` on server. |
| 2 | SSO "Invalid signature" 403 | ✅ Fixed — Replaced signed URLs with Laravel Cache token. Requires `git pull` on server. |
| 3 | Express Cash "request failed 403" | ✅ Fixed — CSRF fix covers this. Same server pull required. |
| 4 | Mobile tab bar clipped off screen | ✅ Fixed — Changed `h-full` to `min-h-0` on section wrappers. Live on Vercel. |
| 5 | Cart items hidden behind footer on mobile | ✅ Fixed — Changed footer from `absolute` to `flex-none`. Live on Vercel. |
| 6 | A-Z filter not working | ✅ Fixed — `activeLetter` state wired into useMemo filter chain. Live on Vercel. |
| 7 | Discount default was Percentage | ✅ Fixed — State default changed to `'fixed'`. Live on Vercel. |
| 8 | Cannot create new customer at POS | ✅ Fixed — Inline modal + `POST /pos/customers` backend endpoint. Requires server pull for backend. |
| 9 | `fwcv3` backend push | ⚠️ **Needs manual push** — run `git push origin enhanced` in `d:\laragon\www\fwcv3`, then `git pull` on server. |
| 10 | Card payment flow (`POST /register` open/close) | 🔲 Not yet implemented. |
| 11 | Offline sync queue drain | 🔲 Background sync worker not yet wired. |

---

## Architecture Reference

```
Vercel (Next.js 16)          Laravel (fwcv3 / JerryUpdates Module)
─────────────────────         ──────────────────────────────────────
/login          ──POST──▶  /api/v1/auth/login        (Passport token)
/pos            ──GET───▶  /api/v1/pos/init           (cold-start payload)
ProductGrid     ──GET───▶  /api/v1/pos/catalog        (recursive paginator)
CustomerSelect  ──GET───▶  /api/v1/pos/customers      (debounced search)
                ──POST──▶  /api/v1/pos/customers      (create walk-in)
CheckoutModal   ──POST──▶  /api/v1/pos/checkout       (transaction insert)
SettingsModal   ──GET───▶  /api/v1/auth/sso-url       (Cache token)
Browser         ──GET───▶  /sso/magic-login/{id}      (web session bridge)
```

## Recent Accomplishments (Latest Pull)
- **Settings Modal & Security:** Implemented a secure logout flow that destroys the `auth_token` cookie, clears the Zustand cart state, and purges the `tradex_pos_db` IndexedDB.
- **Manual Sync:** Added a "Force Resync" button to the Settings Modal allowing cashiers to manually purge their offline database and fetch the freshest prices.
- **Card & Partial Payments:** Unified the payment input in `CheckoutModal.tsx`, allowing exact amounts for both Cash and Card. Added safety guardrails against overcharging cards, and visual alerts for partial payments ("Remaining balance will be marked as Due").
- **Catalog Pagination Bypass:** Rewrote `fetchCatalog` in `api.ts` to use a recursive `while` loop that chunks requests. This completely bypasses the 1,000-item hard limit, successfully syncing 20,000+ product catalogs into IndexedDB without blowing out server memory.
- **Error Handling:** Improved UI error boundaries in `PosInitializer` to render the actual backend error trace rather than masking it. Hardened Axios interceptors to prevent infinite reload loops on 403s.
