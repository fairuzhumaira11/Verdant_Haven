# Verdant Haven 🌿

A plant nursery storefront and garden care management system built with **PHP, MySQL, HTML, CSS, and a little JavaScript**. Customers can buy plants and book visits; nursery staff, gardeners, and an administrator manage the work behind the scenes. It is designed to run locally with XAMPP.

## What you can do

| Role | Features |
| --- | --- |
| Guest | Browse plants and read care tips. |
| Customer | Buy plants, use a simulated bKash or Nagad payment, follow orders and visits, read purchased-plant care guides, get watering reminders, rate gardeners, and chat with nursery staff. |
| Nursery staff | Process and dispatch orders, update stock, view gardener schedules, assign visits, and answer messages. |
| Gardener | View assigned visits and addresses, record completion notes and photos, and chat with staff. |
| Admin | Manage plants, prices, stock, staff, and gardeners; upload and privately download staff and gardener NID PDFs; review orders and services; filter sales by date and download a charted PDF report. |

The interface supports light and dark themes and adapts to desktop and mobile screens.

## Run it locally

**Requirements:** XAMPP with PHP 8+, MySQL or MariaDB, and the PHP `mysqli`, `mbstring`, `fileinfo`, and `iconv` extensions.

1. Start **MySQL** in XAMPP. Start **Apache** if you plan to serve the project through Apache.
2. For a new database, import [`database/schema.sql`](database/schema.sql), then [`database/seed.sql`](database/seed.sql) in phpMyAdmin. The second file adds starter plants.
3. From the project folder, add sample accounts and activity:

   ```powershell
   & 'C:\xampp\php\php.exe' database/scripts/seed_demo.php
   ```

4. Create the first administrator:

   ```powershell
   & 'C:\xampp\php\php.exe' database/scripts/create_admin.php 'Admin Name' 'admin@example.com' '01700000000' 'ChooseYourPassword123!'
   ```

5. Point Apache at the project folder and open `http://localhost/`. For a quick local server, keep MySQL running and use:

   ```powershell
   & 'C:\xampp\php\php.exe' -S 127.0.0.1:8080 -t . tools/router.php
   ```

   Then open `http://127.0.0.1:8080/`. If Apache serves the project from a subfolder, update `RewriteBase` in [`.htaccess`](.htaccess) to that subfolder.

For an **existing** Verdant Haven database, run `database/scripts/migrate.php` before running the sample data script. The seed script can be run again without duplicating its users, orders, or visits. Database settings are in [`config/database.php`](config/database.php); `VH_DB_HOST`, `VH_DB_PORT`, `VH_DB_NAME`, `VH_DB_USER`, and `VH_DB_PASSWORD` can override them.

### Sample accounts

The demo seed adds 13 Bangladeshi-named users, eight plant orders, eight garden visits, purchased-plant reminders, and simulated wallet payment records. These are example logins:

| Role | Email | Password |
| --- | --- | --- |
| Customer | `nusratjahan@customer.verdanthaven.com` | `Pass@customer` |
| Nursery staff | `shamimabegum@staff.verdanthaven.com` | `Pass@stuff` |
| Gardener | `abdulkarim@gardener.veranthaven.com` | `Pass@garden` |

All seeded users in the same role share that role's sample password. The gardener email domain follows the demo data's specified `veranthaven.com` spelling.

## Project layout

- [`actions/handle.php`](actions/handle.php) handles regular form actions and business rules in PHP.
- [`includes/`](includes/) contains database helpers, checkout, reminders, reports, and shared page views.
- [`pages/`](pages/) contains the role dashboards, catalog, checkout, booking, and authentication screens.
- [`api/index.php`](api/index.php) serves live chat and simulated payment requests.
- [`database/`](database/) contains the schema, starter plants, migration, and repeatable demo seed.
- [`assets/`](assets/) contains the stylesheet, chat scripts, and plant images.

## Demo boundaries

bKash and Nagad payments are **simulations**; no real transaction or provider account is involved. Watering reminders appear on the customer dashboard, which refreshes while open.

The password reset screen checks whether an email exists and then allows a password change in that browser session. This is convenient for fictional demo accounts but **must not be used with real users**, because anyone who knows an account email can reset its password.