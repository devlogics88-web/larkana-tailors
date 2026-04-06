# Larkana Tailors & Cloth House

**Gents Specialist** | Owner: Lakhmir Khan | Phone: 0300-2151261  
SOAN GARDEN, Shahid Arcade, Main Double Road, Opposite Bank Islami, Islamabad

---

## About

This is a lightweight shop management web application for Larkana Tailors & Cloth House. Built with:
- **PHP 8.4** (server-side rendering)
- **SQLite** (local database via PHP PDO — no server needed)
- **Vanilla JavaScript** (interactivity)
- **Custom CSS** (compact old-style business UI)

---

## Login Credentials

| Role  | Username | Password |
|-------|----------|----------|
| Admin | larkana  | tailor   |

Workers can be added from the Admin panel (Workers section).

> **First worker setup:** After logging in as admin, navigate to **Workers** in the sidebar and click
> **Add New Worker**. Set a username and password for each tailor/assistant. Workers can then log in
> and manage orders but will not see financial data, reports, or deletion controls.

**Admin can:**
- Full access to all modules
- View financial data (sales, profits, remaining)
- Manage stock, workers, delete records
- View reports

**Worker can:**
- Create and edit orders
- Search customers
- Print invoices (customer copy shows order amount/advance/remaining for payment collection)
- Cannot delete master data, manage stock, view profit/loss reports, or access aggregate financial summaries

---

## Features

1. **Login** — Role-based access (Admin / Worker)
2. **Dashboard** — Live order summary and quick stats
3. **New Order** — Full order entry with customer search and measurement form
4. **All Orders** — Search/filter orders by status, customer, phone
5. **Customer Search** — Find customer history by name or phone
6. **Stock Management** — Add cloth brands, track meters, auto-deduct from orders
7. **Invoice Printing** — Customer Copy + Stitching Labour Copy (thermal printer friendly)
8. **Reports** — Admin-only sales summary, profit/loss estimate, monthly breakdown
9. **Workers** — Add/remove worker accounts

---

## Running Locally (Development)

### Option 1: Replit (Current)

Already running — use the Replit preview URL.

### Option 2: XAMPP / WAMP on Windows

1. Install [XAMPP](https://www.apachefriends.org/) or [WAMP](https://www.wampserver.com/)
2. Copy the `larkana-tailors` folder to your `htdocs` (XAMPP) or `www` (WAMP) directory
3. Make sure the `data/` folder exists and is writable
4. Open: `http://localhost/larkana-tailors/`
5. Enable **SQLite PDO** in `php.ini` (usually enabled by default in XAMPP/WAMP):
   ```
   extension=pdo_sqlite
   ```

### Option 3: PHP Built-in Server (Any OS with PHP)

```bash
cd artifacts/larkana-tailors
php -S 0.0.0.0:8080 router.php
```

Then open: `http://localhost:8080`

---

## Folder Structure

```
larkana-tailors/
├── index.php           # Front controller (routes all requests)
├── router.php          # PHP built-in server router
├── data/
│   └── larkana.db      # SQLite database (auto-created on first run)
├── includes/
│   ├── db.php          # Database connection + schema init + seeding
│   ├── auth.php        # Authentication helpers
│   ├── functions.php   # Business logic (CRUD helpers)
│   ├── header.php      # HTML layout header + sidebar
│   └── footer.php      # HTML layout footer
├── views/
│   ├── login.php
│   ├── dashboard.php
│   ├── order_form.php  # New & edit order (same view)
│   ├── order_list.php
│   ├── customers.php
│   ├── customer_orders.php
│   ├── stock.php
│   ├── reports.php
│   ├── workers.php
│   └── invoice.php     # Customer Copy + Labour Copy
├── assets/
│   ├── style.css
│   └── app.js
└── README.md
```

---

## Thermal Printer Setup

Invoices use `@media print` CSS for compact thermal printer layout (72mm).

1. Open the invoice (Customer Copy or Labour Copy) in browser
2. Press **Print** button
3. In print dialog:
   - Select your **thermal printer** (e.g. Xprinter, EPSON TM-T88)
   - Set paper size to **72mm** or **Receipt**
   - Set margins to **None** or **Minimum**
   - Disable headers/footers

---

## Backup

The entire database is in one file: `data/larkana.db`

**To backup:** Copy `data/larkana.db` to a USB drive or cloud storage.  
**To restore:** Replace `data/larkana.db` with your backup copy.

---

## Windows Desktop App (Future)

To wrap this as a Windows desktop app using Electron:

1. Install [Node.js](https://nodejs.org/)
2. Run: `npm install -g electron`
3. Create `main.js` with Electron BrowserWindow pointing to `http://localhost:8080`
4. Start the PHP server in background via Node's `child_process`
5. Build with `electron-builder` for Windows

This codebase is fully Electron-ready — no changes needed to the PHP code.

---

## Support

**Owner:** Lakhmir Khan  
**Phone:** 0300-2151261  
**Shop:** Larkana Tailors & Cloth House, SOAN GARDEN, Islamabad
