# Ultimate POS / FWCV3 Agent Instructions

This repository contains the Ultimate POS / FWCV3 application, built primarily on the Laravel (PHP 8+) ecosystem, utilizing a modular architecture (e.g., `Modules/`) and a system of custom views and asset injection (`custom_views/`). 

As an AI coding agent operating in this repository, you **MUST** adhere to the following guidelines and instructions when reading, writing, or executing code.

---

## 1. Build, Lint, and Test Commands

### 🔴 Running Tests
The project relies on PHPUnit configured via `phpunit.xml`. Always prefer `php artisan test` for better output formatting, falling back to `./vendor/bin/phpunit` if necessary.

*   **Run all tests in the project:**
    ```bash
    php artisan test
    ```
    *(Note: Running the full suite may take significant time; prefer targeted testing.)*

*   **Run all tests in a specific directory or test suite:**
    ```bash
    php artisan test tests/Feature
    php artisan test tests/Unit
    ```

*   **Run a single test file (Highly Recommended for iterative work):**
    ```bash
    php artisan test tests/Feature/ExampleTest.php
    # or
    ./vendor/bin/phpunit tests/Feature/ExampleTest.php
    ```

*   **Run a specific test method within a file:**
    ```bash
    php artisan test --filter test_method_name tests/Feature/ExampleTest.php
    # or
    ./vendor/bin/phpunit --filter test_method_name tests/Feature/ExampleTest.php
    ```

### 🟡 Linting and Formatting
The repository contains `.php_cs` and `.prettierrc` configuration files, indicating standard formatting tools are in use.

*   **PHP Formatting:** Use PHP-CS-Fixer (if available in the environment) to conform to PSR-12 and custom rules:
    ```bash
    ./vendor/bin/php-cs-fixer fix
    ```
*   **Frontend Formatting (JS/CSS/HTML):** Use Prettier:
    ```bash
    npx prettier --write "resources/**/*.{js,css,vue,blade.php}"
    ```

### 🟢 Build and Asset Management
*   **Asset compilation:** Use standard npm commands if package.json defines them:
    ```bash
    npm install
    npm run dev   # for local development
    npm run build # for production builds
    ```
*   **Important Note on Custom Scripts:** There are numerous Python (`.py`) scripts in the root directory (e.g., `fix_dark_mode.py`, `inject_custom_views_js.py`, `wrap_custom_views.php`). These scripts are used for automated asset patching, CSS generation, and view manipulation. If you are modifying base views or styles, you **must** consider if these scripts need to be re-run or if your changes should be made within the `custom_views` directory instead.

---

## 2. Code Style Guidelines

### 📦 Imports and Namespaces
*   Follow standard PSR-4 autoloading standards.
*   **Alphabetical Order:** Keep `use` statements organized alphabetically.
*   **No Unused Imports:** Always remove unused `use` statements.
*   **Absolute over Relative:** Always import classes absolutely from the root namespace (e.g., `use App\Models\User;` instead of `use User;` in the same namespace if referencing dynamically).

### 🖋 Formatting and Syntax
*   **Indentation:** Use 4 spaces for PHP. Use 2 spaces for JS/CSS/HTML.
*   **Braces:** 
    *   Classes and Methods: Opening brace on the **next line**.
    *   Control Structures (`if`, `foreach`, `while`): Opening brace on the **same line**.
*   **Line Length:** Aim for a maximum of 120 characters per line. Break down long arrays or method calls across multiple lines.
*   **Trailing Commas:** Use trailing commas in multi-line arrays to keep git diffs clean.

### 🏷 Typing
*   **PHP 8.x Types:** The project uses PHP `^8.0`. You **MUST** use scalar type declarations for function/method arguments and return types whenever possible.
    ```php
    // Good
    public function calculateTotal(int $quantity, float $price): float 
    {
        return $quantity * $price;
    }
    
    // Bad
    public function calculateTotal($quantity, $price) 
    {
        return $quantity * $price;
    }
    ```
*   **Nullable Types:** Use the `?Type` syntax for arguments or return values that can be null.
*   **DocBlocks:** Omit redundant DocBlocks if the types are strictly defined in the signature, unless explaining complex logic, array structures (e.g., `@param array<int, string>`), or external API behaviors.

### 📝 Naming Conventions
*   **Classes, Interfaces, and Traits:** `PascalCase` (e.g., `InvoiceController`, `PaymentMethodInterface`).
*   **Methods and Functions:** `camelCase` (e.g., `calculateTotal()`, `findActiveUser()`).
*   **Variables and Properties:** `camelCase` (e.g., `$invoiceAmount`, `$activeUsers`).
*   **Constants:** `UPPER_SNAKE_CASE` (e.g., `MAX_LOGIN_ATTEMPTS`).
*   **Database Tables and Columns:** `snake_case` (e.g., `business_locations`, `created_at`).
*   **Blade Views:** `kebab-case.blade.php` (e.g., `user-profile.blade.php`).

### 🛡 Error Handling and Logging
*   **Exceptions over Returns:** Throw descriptive exceptions for failure states rather than returning `false` or null, especially in core business logic or Services.
*   **Laravel Exception Handler:** Let standard exceptions bubble up to the Laravel Exception Handler when appropriate (e.g., `ModelNotFoundException`, `ValidationException`).
*   **Try-Catch Blocks:** Use try-catch blocks when integrating with external APIs, file systems, or database transactions where a rollback is needed.
*   **Database Transactions:** Always wrap multi-step database writes in a transaction:
    ```php
    DB::beginTransaction();
    try {
        // ... operations ...
        DB::commit();
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Operation failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        throw $e;
    }
    ```
*   **Logging:** Use Laravel's `Log` facade for critical errors, system boundaries, and unexpected states. Include context arrays (e.g., User ID, payload) to make debugging easier.

### 🏗 Architecture and Patterns
*   **Fat Models, Thin Controllers:** Keep controllers focused on handling HTTP requests, authorization, validation, and returning responses.
*   **Business Logic:** Move complex business rules into Service classes, Action classes, or the `Modules/` context to keep the core application clean.
*   **Custom Views:** Before modifying a core view in `resources/views/`, check if an override exists or should exist in `custom_views/`. The project leverages scripts to wrap and inject customizations.
*   **Modularity:** Respect the boundaries of the `Modules/` directory (like `JerryUpdates`). Do not tightly couple core `app/` logic to specific module implementations unless through defined interfaces or events.
