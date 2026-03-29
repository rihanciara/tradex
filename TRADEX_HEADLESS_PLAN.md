# Tradex Headless POS Implementation Plan

## Overview
This document outlines the comprehensive, step-by-step strategy for migrating the Ultimate POS / FWCV3 frontend into a modern, decoupled (headless) architecture utilizing a Next.js frontend hosted on Vercel. The backend will serve strictly as an API, with all custom logic encapsulated within the `JerryUpdates` module to ensure upgrade safety.

---

## Phase 1: Authentication & Security (✅ Mostly Complete)
The foundation of a headless architecture is secure, stateless communication between the frontend client and the backend API.

- [x] **1.1 Configure CORS:** Ensure the Laravel backend accepts requests from the Vercel frontend domains.
- [x] **1.2 Disable CSRF for API:** Bypass Laravel's CSRF protection for `api/*` routes, as the headless app will use token-based authentication (Sanctum/Passport).
- [x] **1.3 Cross-Domain Login Endpoint:** Leveraged standard `/login` route with CORS/CSRF bypass for session/cookie-based auth, or token creation if utilizing Sanctum. (Note: Currently `ApiPosController` has a fallback user mechanism for testing).
- [ ] **1.4 Token Validation & Refresh:** Ensure tokens/sessions can be validated on boot and refreshed if close to expiry.
- [ ] **1.5 User Profile Endpoint:** Create an endpoint to fetch the authenticated user's details, permissions, and assigned locations.

---

## Phase 2: Core Data Synchronization (Read-Only API)
The Next.js POS needs essential data to render the UI before any transaction occurs.

- [ ] **2.1 Initialization Payload (`/api/jerryupdates/v1/pos/init`):** 
  - Fetch global business settings (currency, tax rates, rounding rules).
  - Fetch active payment methods.
  - Fetch cash register status (open/closed).
- [x] **2.2 Product Catalog Endpoint (`/api/jerryupdates/v1/pos/products`):**
  - Fetch products, variations, prices, and stock levels.
  - Implement pagination and search functionality.
- [ ] **2.3 Categories & Brands:**
  - Endpoints to fetch taxonomy data for the left-hand navigation/filtering sidebar.
- [x] **2.4 Customer Directory (`/api/jerryupdates/v1/pos/customers`):**
  - Fetch list of customers for selection during a sale.
  - Implement search and basic customer details.

---

## Phase 3: Transaction Engine (Write API)
The core logic for processing sales, holding carts, and managing register state.

- [ ] **3.1 Cash Register Operations:**
  - `POST /register/open`: Open a new shift with starting cash.
  - `POST /register/close`: Close the shift and submit closing totals.
- [x] **3.2 Checkout/Sell Endpoint (`POST /api/jerryupdates/v1/pos/checkout`):**
  - Process a completed cart.
  - Validate stock, calculate final taxes/discounts, and record the payment(s).
  - Return the receipt data/invoice URL.
- [ ] **3.3 Suspend/Hold Sale:**
  - `POST /sales/suspend`: Save the current cart state to the server.
  - `GET /sales/suspended`: Retrieve a list of suspended sales.
  - `POST /sales/resume/{id}`: Load a suspended sale back into the active cart.
- [ ] **3.4 Quotations & Drafts:**
  - Endpoints to save a cart as a formal quotation instead of a final sale.

---

## Phase 4: Offline Resilience & Performance (Frontend/Backend Coordination)
Ensuring the POS remains functional and lightning-fast under heavy load or poor network conditions.

- [ ] **4.1 Product Catalog Sync:** Implement a mechanism (e.g., ETags or Last-Modified headers) to allow the Next.js app to cache the entire product catalog locally (IndexedDB) and only fetch updates.
- [ ] **4.2 Background Queueing:** If a checkout request fails due to network loss, the Next.js app should queue the transaction locally and automatically sync it to the backend when the connection is restored.
- [ ] **4.3 Webhook Triggers:** (Optional) Configure the backend to send webhooks to Vercel when critical data changes (e.g., mass stock update) to invalidate Vercel's Edge Cache.

---

## Phase 5: Next.js Frontend Development (Vercel)
The actual implementation of the user interface on the new stack.

- [ ] **5.1 Project Scaffold:** Initialize the Next.js application with Tailwind CSS, TypeScript, and state management (Zustand or Redux).
- [ ] **5.2 Authentication Flow:** Implement login screens, secure token storage (HttpOnly cookies or secure local storage), and route guards.
- [ ] **5.3 Layout & UI Components:** Build the core POS layout (sidebar, product grid, cart panel, numpad).
- [ ] **5.4 Cart Logic (State Management):** Implement local cart calculations (subtotals, taxes, discounts) that perfectly mirror the backend's logic to prevent discrepancies during checkout.
- [ ] **5.5 Payment Modals & Receipt Printing:** Build the final checkout flow UI and integrate with browser printing for receipts.

---

## Phase 6: Testing & Deployment
- [ ] **6.1 API Integration Tests:** Write automated PHPUnit tests within Laravel to ensure every endpoint handles valid and invalid payloads correctly.
- [ ] **6.2 Frontend End-to-End Tests:** Use Cypress or Playwright to test the critical path: Login -> Add Item -> Checkout.
- [ ] **6.3 Staging Deployment:** Deploy the backend API to a staging server and the Next.js app to Vercel Staging.
- [ ] **6.4 Production Cutover:** Final data migration, DNS updates, and staff training.

---

## Current Status (As of Last Session)
- **Monorepo Migration:** We have combined the frontend (Next.js) and backend (Laravel/fwcv3) into a single unified `tradex` repository with `/frontend` and `/backend` directories.
- **Focus:** We have successfully configured foundational security (CORS, CSRF bypass) to allow the standard `/login` route to accept cross-domain requests. We created the initial scaffold for the `checkout`, `catalog` (products), and `customers` endpoints in `ApiPosController` within the `JerryUpdates` module.
- **Note on Auth:** Currently, `ApiPosController` has a hardcoded fallback (`\App\User::where('business_id', '!=', null)->first()`) to allow rapid frontend testing without strict token exchange.
- **Next Action:** We need to define the `init` endpoint (Phase 2.1) to feed the frontend its required configuration on boot, OR improve the Authentication architecture to formally issue API Tokens (e.g., via Sanctum) rather than relying on the fallback user.
