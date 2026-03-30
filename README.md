# Tradex Headless POS (Frontend)

This is the Next.js frontend specifically designed for the **Tradex Headless POS** system. It provides a lightning-fast, offline-capable interface for cashiers, pulling its data from the Ultimate POS backend via the internal `JerryUpdates` (Tweaks) plugin API.

## ⚠️ Important: Backend Requirements

This frontend **cannot run on its own**. It requires the dedicated `fwcv3` (Ultimate POS / Laravel) repository to serve as its backend API. 

If you are cloning this repository to work on the UI, you **must** ensure the corresponding backend meets the following requirements:

### 1. `JerryUpdates` Plugin is Installed
The core Laravel backend must have the custom `JerryUpdates` module installed in its `Modules/JerryUpdates/` folder. This plugin exposes all the optimized, headless API endpoints that this Next.js app consumes.

### 2. Vercel API Mode is ENABLED
The backend API explicitly checks for a security toggle. If this is off, all API calls will instantly return `403 Forbidden`.
*   **To Enable:** Log into the traditional Laravel Admin Panel → Go to the **Just Tweaks** Dashboard → Check the box for **Vercel API Mode** (`jerry_vercel_api`) and hit Save.

### 3. Laravel Passport Auth is Configured
This Next.js app no longer uses fallback testing mechanisms; it uses bearer tokens.
*   Your backend must have `laravel/passport` installed (`composer require laravel/passport`).
*   You must have run `php artisan passport:install`.

### 4. CORS is Properly Set Up
*   The `config/cors.php` file on the backend must allow `supports_credentials => true` and accept requests from your Vercel URL (or `localhost` for local development).

---

## 🚀 Environment Setup

Create a `.env.local` file in the root of this `tradex` folder and set the backend API path:

```env
# Local Backend Example:
NEXT_PUBLIC_API_URL=http://fwcv3.test/api/v1

# Production Backend Example:
# NEXT_PUBLIC_API_URL=https://app12.dookanwale.com/api/v1
```

## 🛠️ Local Development

First, ensure your `fwcv3` backend server is running locally. Then, run the development server:

```bash
npm install
npm run dev
```

Open [http://localhost:3000](http://localhost:3000) with your browser to see the result. The application will immediately attempt to contact the `{NEXT_PUBLIC_API_URL}/pos/init` endpoint to hydrate the store with global store settings and taxes.

## 📦 Tech Stack
- **Framework**: Next.js 16.2 (App Router)
- **Styling**: Tailwind CSS v4 & Framer Motion
- **State**: Zustand (Local Storage / IndexedDB Cached)
- **Data Fetching**: React Query & Axios
