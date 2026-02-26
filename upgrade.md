# MarvelStore v2.0 — Upgrade Plan

## Overview

Upgrading MarvelStore from v1.0 to v2.0 with 6 feature areas: Activity Logging, Staff/Technician Dashboards, Enhanced Admin Reports, Customer Database, Self-Service Password Change, and CSV/PDF Export.

---

## Phase A: Activity Log (Audit Trail)

**Goal:** Non-deletable system-wide log of every important action.

### Database

```sql
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(50) NOT NULL,        -- 'product_add', 'product_edit', 'product_delete', 'sale_create', 'repair_create', 'repair_status', 'user_create', 'user_toggle', 'login', 'logout'
    entity_type VARCHAR(50),            -- 'product', 'sale', 'repair', 'user', 'category'
    entity_id INT,
    description TEXT,                   -- Human-readable: "Added product 'iPhone 15 Case' (qty: 50)"
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_action (action),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;
```

### Files

| Action                          | File                                                                   |
| ------------------------------- | ---------------------------------------------------------------------- |
| [NEW] `includes/logger.php`     | `log_activity($action, $entity_type, $entity_id, $description)` helper |
| [NEW] `activity_log.php`        | Admin-only log viewer with filters (user, action, date range)          |
| [MODIFY] `products.php`         | Log product delete                                                     |
| [MODIFY] `product_add.php`      | Log product add                                                        |
| [MODIFY] `product_edit.php`     | Log product edit                                                       |
| [MODIFY] `sale_new.php`         | Log sale creation                                                      |
| [MODIFY] `repair_add.php`       | Log repair creation                                                    |
| [MODIFY] `repair_view.php`      | Log status change + part add                                           |
| [MODIFY] `categories.php`       | Log category add/delete                                                |
| [MODIFY] `users.php`            | Log user toggle                                                        |
| [MODIFY] `user_add.php`         | Log user creation                                                      |
| [MODIFY] `login.php`            | Log login                                                              |
| [MODIFY] `logout.php`           | Log logout                                                             |
| [MODIFY] `includes/sidebar.php` | Add Activity Log link (admin)                                          |

---

## Phase B: Staff & Technician Personal Dashboards

**Goal:** Each non-admin user sees their own performance stats on the dashboard.

### Changes to `index.php`

Instead of one dashboard for all, detect role and show:

**Staff Dashboard:**

- My Sales Today (count + revenue)
- My Sales This Month (count + revenue)
- My Recent Sales table (last 10)
- Mini ranking vs other staff

**Technician Dashboard:**

- My Active Repairs (pending + repairing count)
- My Completed Repairs (this month)
- My Repairs table (last 10)
- Avg repair turnaround time

**Admin Dashboard:** (existing + enhanced, see Phase C)

---

## Phase C: Enhanced Admin Reports

**Goal:** Richer analytics with more charts and drill-down tables.

### New Report Sections (all in `reports.php`)

| Report                     | Chart Type      | Data                                           |
| -------------------------- | --------------- | ---------------------------------------------- |
| Sales by Category          | Pie/Donut chart | Revenue per product category                   |
| Sales by Payment Method    | Pie chart       | Cash vs Transfer vs POS                        |
| Profit Margins by Category | Horizontal bar  | Profit per category                            |
| Inventory Value Summary    | KPI cards       | Total stock cost value + retail value          |
| Top 10 Products            | Bar chart       | Best-selling products by quantity              |
| Repair Turnaround          | KPI card        | Avg days pending→collected                     |
| Staff Performance Table    | Table           | Sales count, revenue, avg sale value per staff |

### Files

| Action                      | File                                                                    |
| --------------------------- | ----------------------------------------------------------------------- |
| [MODIFY] `reports.php`      | Add all new report sections with tabs/sections                          |
| [NEW] `api/report_data.php` | JSON API for chart data (category sales, payment methods, top products) |

---

## Phase D: Customer Database

**Goal:** Track customers across sales and repairs.

### Database

```sql
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    email VARCHAR(255),
    address TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_phone (phone),
    INDEX idx_name (name)
) ENGINE=InnoDB;
```

```sql
-- Add customer_id FK to sales and repairs
ALTER TABLE sales ADD COLUMN customer_id INT DEFAULT NULL AFTER user_id,
    ADD FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL;

ALTER TABLE repairs ADD COLUMN customer_id INT DEFAULT NULL AFTER user_id,
    ADD FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL;
```

### Files

| Action                          | File                                                      |
| ------------------------------- | --------------------------------------------------------- |
| [NEW] `customers.php`           | Customer list with search (DataTables)                    |
| [NEW] `customer_view.php`       | Customer detail: info + purchase history + repair history |
| [NEW] `api/customer_search.php` | AJAX search for Select2 in sale/repair forms              |
| [MODIFY] `sale_new.php`         | Add optional customer Select2 dropdown                    |
| [MODIFY] `repair_add.php`       | Add customer Select2 (auto-fill name/phone if existing)   |
| [MODIFY] `includes/sidebar.php` | Add Customers menu item                                   |

---

## Phase E: Password Change & Settings

**Goal:** Users can change their own password. Admin settings page for store info.

### Files

| Action                         | File                                                    |
| ------------------------------ | ------------------------------------------------------- |
| [NEW] `change_password.php`    | Self-service password change (current + new + confirm)  |
| [NEW] `settings.php`           | Admin-only: store name, address, phone, receipt message |
| [MODIFY] `includes/header.php` | Add "Change Password" to user dropdown menu             |
| [MODIFY] `sale_receipt.php`    | Use store settings on receipt                           |

### Database

```sql
CREATE TABLE settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default values
INSERT INTO settings (setting_key, setting_value) VALUES
('store_name', 'MarvelStore'),
('store_address', ''),
('store_phone', ''),
('receipt_footer', 'Thank you for your patronage!');
```

---

## Phase F: CSV Export

**Goal:** Export key data tables to CSV for reporting and record-keeping.

### Files

| Action                      | File                                                              |
| --------------------------- | ----------------------------------------------------------------- |
| [NEW] `export.php`          | Handles CSV generation for products, sales, repairs, activity log |
| [MODIFY] `products.php`     | Add "Export CSV" button                                           |
| [MODIFY] `sales.php`        | Add "Export CSV" button                                           |
| [MODIFY] `repairs.php`      | Add "Export CSV" button                                           |
| [MODIFY] `activity_log.php` | Add "Export CSV" button                                           |

---

## Database Migration Script

All schema changes will be added to `setup_database.php` so new installations auto-create everything, and a new `migrate_v2.php` script will handle upgrading existing v1.0 databases.

---

## Build Order

1. **Phase A** — Activity Log (foundation for all tracking)
2. **Phase D** — Customer Database (needed before modifying sale/repair forms)
3. **Phase B** — Staff/Tech Dashboards
4. **Phase C** — Enhanced Admin Reports
5. **Phase E** — Password Change & Settings
6. **Phase F** — CSV Export

---

## Summary of New Files

| #   | File                      | Purpose                      |
| --- | ------------------------- | ---------------------------- |
| 1   | `includes/logger.php`     | Activity logging helper      |
| 2   | `activity_log.php`        | Admin log viewer             |
| 3   | `customers.php`           | Customer list                |
| 4   | `customer_view.php`       | Customer detail page         |
| 5   | `api/customer_search.php` | Customer AJAX search         |
| 6   | `api/report_data.php`     | Report chart data API        |
| 7   | `change_password.php`     | Self-service password change |
| 8   | `settings.php`            | Admin store settings         |
| 9   | `export.php`              | CSV export handler           |
| 10  | `migrate_v2.php`          | Database migration script    |

## Summary of Modified Files

| #   | File                   | Changes                    |
| --- | ---------------------- | -------------------------- |
| 1   | `setup_database.php`   | Add new tables             |
| 2   | `index.php`            | Role-based dashboards      |
| 3   | `reports.php`          | Enhanced charts and tables |
| 4   | `includes/sidebar.php` | New menu items             |
| 5   | `includes/header.php`  | Change Password link       |
| 6   | `sale_new.php`         | Customer selector          |
| 7   | `repair_add.php`       | Customer selector          |
| 8   | `sale_receipt.php`     | Store settings             |
| 9   | `products.php`         | Export + logging           |
| 10  | `product_add.php`      | Logging                    |
| 11  | `product_edit.php`     | Logging                    |
| 12  | `categories.php`       | Logging                    |
| 13  | `sales.php`            | Export                     |
| 14  | `repairs.php`          | Export                     |
| 15  | `repair_view.php`      | Logging                    |
| 16  | `users.php`            | Logging                    |
| 17  | `user_add.php`         | Logging                    |
| 18  | `login.php`            | Logging                    |
| 19  | `logout.php`           | Logging                    |
