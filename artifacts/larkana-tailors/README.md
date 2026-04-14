# Larkana Fabrics & Tailors — Shop Management System

**Gents Specialist** | Owner: Lakhmir Khan | Phone: 0300-2151261  
SOAN GARDEN, Shahid Arcade, Main Double Road, Islamabad

---

## Tech Stack

| Layer       | Technology                                          |
|-------------|-----------------------------------------------------|
| Backend     | PHP 8.4 (built-in server or XAMPP)                  |
| Database    | SQLite 3 via PDO (no MySQL or server required)      |
| Frontend    | Vanilla JavaScript — no frameworks                  |
| Styling     | Custom CSS — no Bootstrap or Tailwind               |
| Exports     | CSV via PHP native `fputcsv` / `fgetcsv`            |

No Composer or external PHP packages required.

---

## Features

- **Admin / Worker login** with role-based access control
- **Customer management** with CID (Customer ID) number and search
- **Order management** — create, edit, track status (Pending / Ready / Delivered)
- **Measurement form** — 20+ measurement fields, all completely optional
- **Itemized invoices** — stitching, button, pancha add-ons, discount, advance tracking
- **Dual print copies** — Customer Copy (no measurements) + Labour/Stitching Copy (with measurements)
- **Stock management** — meter stock, box sets, date received, CSV export & import
- **Admin-configurable pricing** — stitching types, button types, pancha types
- **Payment tracking** — advance paid, balance due, dues clearing per order
- **Dark #1B242D professional color theme**

---

## Installation on Windows (XAMPP)

### Step 1 — Download and Install XAMPP

1. Go to [https://www.apachefriends.org/](https://www.apachefriends.org/) and download XAMPP for Windows.
2. Run the installer. You only need **Apache** and **PHP** — MySQL is not required for this app.
3. Install to `C:\xampp\` (default location).

### Step 2 — Enable SQLite in PHP

1. Open `C:\xampp\php\php.ini` in Notepad (run Notepad as Administrator if needed).
2. Press **Ctrl+F** and search for `extension=pdo_sqlite`.
3. If the line starts with a semicolon `;`, remove the semicolon to uncomment it:
   ```
   extension=pdo_sqlite
   ```
4. Also uncomment `extension=sqlite3` the same way.
5. Save and close `php.ini`.

### Step 3 — Copy the Application Files

Copy the entire `larkana-tailors` folder to:

```
C:\xampp\htdocs\larkana-tailors\
```

The final structure should look like:

```
C:\xampp\htdocs\larkana-tailors\
    index.php
    router.php
    assets\
    includes\
    views\
    data\        ← SQLite database will be created here automatically
```

### Step 4 — Set Write Permissions on the Data Folder

The `data\` folder needs write access so Apache can create and write the SQLite database file.

**Option A — Using File Explorer:**
1. Right-click `C:\xampp\htdocs\larkana-tailors\data\`
2. Click Properties → Security tab → Edit → Add
3. Type `Everyone` → click OK → check **Full control** → click Apply → OK

**Option B — Using Command Prompt (run as Administrator):**

```cmd
icacls "C:\xampp\htdocs\larkana-tailors\data" /grant Everyone:(OI)(CI)F
```

### Step 5 — Start Apache in XAMPP

1. Open **XAMPP Control Panel** from the Start Menu or `C:\xampp\xampp-control.exe`.
2. Click **Start** next to **Apache**.
3. The status indicator should turn green and show the port number.

### Step 6 — Open the Application

Open your browser and navigate to:

```
http://localhost/larkana-tailors/
```

The app will automatically create the `data/larkana.db` SQLite database on first load.

### Step 7 — Login

| Field    | Value      |
|----------|------------|
| Username | `larkana`  |
| Password | `tailor`   |

---

## Running Without XAMPP (PHP Built-in Server)

If PHP 8.4 is installed on your system (or you are running on Replit):

```bash
php -S 0.0.0.0:8000 router.php
```

Then open: `http://localhost:8000/`

---

## Database Backup

The entire database is a single portable file:

```
C:\xampp\htdocs\larkana-tailors\data\larkana.db
```

- **To back up:** Copy `larkana.db` to a USB drive, external hard disk, or cloud storage (Google Drive / Dropbox).
- **To restore:** Replace `data\larkana.db` with your saved backup file.
- **Recommended:** Perform a backup at least once a week.

---

## Stock CSV Export & Import

### Export

1. Go to **Stock** page in the app.
2. Click **Export CSV** button.
3. A CSV file named `larkana-stock-YYYY-MM-DD.csv` will download automatically.

### Import

1. Click **Import CSV** on the Stock page.
2. Select a CSV file with the correct column format:

```
ID, Brand Name, Cloth Type, Stock Date, Total Meters, Available Meters,
Cost/Meter, Sell/Meter, Has Box (Yes/No), Box Quantity, Box Price, Notes
```

The first row is treated as a header and skipped. New rows are added; existing items are not overwritten.

---

## Thermal Printer Setup

Invoices are designed for **58mm or 80mm thermal receipt printers**.

1. Connect the thermal printer via USB to your Windows PC.
2. Install the printer driver (supplied with the printer or downloadable from manufacturer website).
3. In Windows: Settings → Printers & Scanners → set your thermal printer as default.
4. Open an invoice in the app → click **Print**.
5. In the print dialog:
   - Select your **thermal printer**
   - Set paper size to **58mm** or **80mm**
   - Set margins to **None** or **Minimum**
   - Disable browser headers/footers if shown

---

## Login Credentials

| Role  | Username | Password |
|-------|----------|----------|
| Admin | larkana  | tailor   |

To add worker accounts: log in as Admin → Settings → Workers → Add New Worker.

**Admin can:** Full access — all modules, financial data, reports, delete records, manage stock and pricing.  
**Worker can:** Create/edit orders, search customers, print invoices. Cannot delete master data, manage stock, or view profit reports.

---

## Folder Structure

```
larkana-tailors/
├── index.php           Main router and all action handlers
├── router.php          PHP built-in server router
├── assets/
│   ├── style.css       All UI styles
│   ├── app.js          Frontend JavaScript
│   └── logo.jpeg       Shop logo
├── includes/
│   ├── db.php          Database init, schema, migrations, seeding
│   ├── functions.php   All business logic functions
│   ├── header.php      Topbar, sidebar, navigation
│   └── footer.php      Status bar, developer credit
├── views/
│   ├── dashboard.php
│   ├── customers.php
│   ├── customer_orders.php
│   ├── order_form.php
│   ├── orders.php
│   ├── stock.php
│   ├── invoice.php
│   ├── reports.php
│   ├── workers.php
│   └── settings.php
└── data/
    └── larkana.db      SQLite database (auto-created on first run)
```

---

## Developer Contact

Developed with love by:

**SYED SHAAN HAIDER**  
Full Stack Web Developer / Software Engineer  
Phone / WhatsApp: **+923440986924**
