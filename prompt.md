# MarvelStore Master Prompt (Restoration Blueprint)

Act as a Senior Full-Stack PHP Developer. Your task is to build **MarvelStore**, a premium Store Management System for an electronics retail and repair shop.

## Mandatory Background Check

**BEFORE WRITING ANY CODE**, you must:

1. Read **`prd.md`** to understand the full functional idea and business requirements.
2. Read **`final_update.md`** to understand the established technical architecture, security measures, and UI stabilization patterns.
3. Review **`new_dashboard.php`** as the "Gold Standard" for UI interactivity — this is a verified working page with hamburger menu, sidebar dropdowns, and the white topbar all functioning correctly. Use its exact structure as the foundation.

## Core Stack

- **Backend**: Vanilla PHP 8.2 (PDO for database, Sessions for auth).
- **Frontend**: Otika Bootstrap 4 Admin Template (Assets located in `/assets/`).
- **Database**: MySQL (Database name: `marvelstore_db`).

## Key Requirements

1. **UI Fidelity**: Use the **Otika Template** (in the `/Otika/` folder) exclusively for all interface elements. Achieve the "White Topbar" aesthetic by adding `class="light light-sidebar theme-white"` to the `<body>` tag.
2. **Architecture**: Create a partial-based system (`header.php`, `sidebar.php`, `footer.php`) in an `/includes/` directory. Decompose the structure from `new_dashboard.php` into these partials. Ensure `footer.php` loads scripts in the exact sequence: `app.min.js` → Extra Libraries → `scripts.js` → `custom.js`.
3. **Database Setup**: Use the existing `setup_database.php` as a starting point. It creates all tables (`users`, `products`, `categories`, `sales`, `sale_items`, `repairs`, `repair_parts`) and seeds a default admin user.
4. **API Endpoints**: Create an `/api/` directory for JSON endpoints like product search and dashboard chart data.
5. **Security**: Mandatory PDO prepared statements, output escaping via an `e()` helper, and CSRF token protection on all mutating forms.
6. **Interactive Markers**: Preserve all `data-toggle="sidebar"`, `has-dropdown`, and `menu-toggle` HTML attributes exactly as seen in the Otika template and `new_dashboard.php` — these are what the JS hooks bind to.

## Modules to Build

Based on the PRD:

- **Dashboard** (`index.php`): KPI cards, revenue chart (ApexCharts), low-stock alerts.
- **Inventory**: `products.php`, `product_add.php`, `product_edit.php`, `categories.php`.
- **POS**: `sale_new.php` with dynamic cart, Select2 product search, stock deduction. `sales.php` for history. `sale_receipt.php` for printing.
- **Repairs**: `repair_add.php`, `repairs.php`, `repair_view.php` with status workflow (Pending → Repairing → Ready → Collected).
- **Reports**: `reports.php` with date-range revenue/profit analysis (Admin only).
- **Users**: `users.php`, `user_add.php`, `user_edit.php` (Admin only).

## Critical Instructions

1. Never combine partial logic into single standalone pages. Always maintain the `/includes/` structure for global theme consistency.
2. Every page must `require_once` the header and sidebar at the top and the footer at the bottom.
3. Page-specific JS (like chart init) must come AFTER the footer include, since that's where the base libraries are loaded.
