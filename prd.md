## Technical Stack

- **Backend:** PHP 8.x (Standard/Vanilla)
- **Database:** MySQL
- **Frontend:** Utility the Otika template (bootstrap) and create any other elements (like print reeceipt) using the simplest and the most suitable technology options.

## Product Requirements Document (PRD): MarvelStore v1.0

### 1. Executive Summary

**MarvelStore** is a custom-built, internal web application designed for an electronics retail and repair shop. It replaces manual ledgers and fragmented spreadsheets with a single "source of truth" for inventory, sales, and service tracking.

### 2. User Roles

- **Admin:** Full system access, including deleting records, viewing total shop profit, and managing staff accounts.
- **Staff:** Can record sales, view stock levels, and create repair tickets.
- **Technician:** Focused on the Repair Module—updating repair statuses and logging parts used.

### 3. Functional Requirements

#### **A. Inventory & Stock Management**

- **Item Registry:** Manual entry for Name, Brand, Category, and SKU.
- **Electronic Specifics:** Dedicated field for **IMEI/Serial Numbers** for phones and laptops.
- **Stock Tracking:** Automatic deduction of stock upon a completed sale.
- **Low-Stock Visuals:** Dashboard alerts when specific items (like chargers or screen protectors) fall below a set quantity.

#### **B. Sales & Transaction Module**

- **Simple Checkout:** A form to search for products, select quantity, and choose a **Payment Method** (Cash, Transfer, or POS).
- **Discounting:** Ability to apply a manual discount to a total sale.
- **Receipt Preview:** A "Print View" that generates a clean, text-only receipt formatted for standard A4 or thermal printers.
- **Staff Attribution:** Every sale is tagged to the logged-in user for performance monitoring.

#### **C. Repair Service Module (Job Cards)**

- **Intake Logging:** Capture customer details, device model, passcode (if needed), and initial fault description.
- **Status Workflow:** Simple dropdown to move a job from `Pending` → `Repairing` → `Ready` → `Collected`.
- **Parts Integration:** Ability to link an inventory item (e.g., "iPhone 11 Battery") to a repair job card so it is removed from stock and added to the bill.

#### **D. Profit & Performance Tracking**

- **Daily Summary:** Total revenue generated in the last 24 hours.
- **Profit Calculation:** Uses the formula $Selling Price - Cost Price = Profit$ to show actual earnings rather than just revenue.
- **Staff Leaderboard:** A simple table showing which staff member has closed the most sales or repairs.

### 4. Non-Functional Requirements

- **Simplicity:** No complex navigation;
- **Security:** Password-protected login and **SQL Prepared Statements** to ensure data safety.
- **Responsiveness:** Must work on both a desktop computer at the counter and a tablet/phone for stock taking.

### 5. Technical Stack

- **Backend:** PHP 8.x (Standard/Vanilla)
- **Database:** MySQL
- **Frontend:** Utility the Otika template (bootstrap) and create any other elements (like print reeceipt) using the simplest and the most suitable technology options.
