# Food Ordering System — Setup Guide

## Before you run it — manual checklist

1. **Create `.env`** (see step 2 below) — the app won't connect to a database without it.
2. **Import `data/database.sql`** into MySQL (step 3).
3. **Create demo login accounts** for customer/kitchen/delivery by running:
   ```bash
   php data/seed_demo_users.php
   ```
   (Admin is already seeded by `database.sql` — see credentials table below.)
   This only works from the command line and is safe to re-run.
4. **Make `/image` writable by the web server** — admin-uploaded food photos
   are saved there:
   ```bash
   chmod -R 755 image/
   ```
   (or `775` + correct group ownership, depending on your server setup.)
5. **If using Apache**, confirm `AllowOverride All` is set for this project's
   directory in your vhost/Apache config — otherwise the `.htaccess` files
   that protect `.env`, `data/*.sql`, and `/includes/*.php` won't take effect.
   On nginx, see the note at the bottom of this file instead.
6. **Log in and change the seeded passwords** (or delete those demo accounts)
   before this goes anywhere near a real server — see "Change Password" in
   any account's nav once logged in.

## Login credentials

| Role     | Email                  | Password      |
|----------|-------------------------|---------------|
| Admin    | admin@example.com       | admin123      |
| Customer | customer@example.com    | customer123   |
| Kitchen  | kitchen@example.com     | kitchen123    |
| Delivery | delivery@example.com    | delivery123   |

The Admin login comes from `data/database.sql`. The other three only exist
once you've run `php data/seed_demo_users.php` (step 3 above) — until then,
those accounts don't exist and those emails will fail to log in. You can
also just register your own customer/kitchen/delivery account from
`auth/Register.php` instead of using the seeded ones.

**These are demo credentials, not production ones.** Change or delete them
before deploying anywhere reachable by the public.

## 1. Requirements

- PHP 7.4+ (uses `mysqli`, `password_hash`, `random_bytes`)
- MySQL / MariaDB
- Apache with `mod_rewrite`/`.htaccess` support (or equivalent config on nginx — see note below)

## 2. Configure your environment

Copy the example env file and edit it for your machine:

```bash
cp .env.example .env
```

Edit `.env`:

```
APP_ENV=local            # "production" on a real server — disables error display
DB_HOST=localhost
DB_USER=root
DB_PASS=your_db_password
DB_NAME=food-ordering-system
APP_FORCE_SECURE_COOKIES=false   # set to "true" once served over HTTPS
```

`.env` is gitignored — never commit real credentials. On a production host, prefer
setting these as real environment variables on the server; the app checks
real env vars first and only falls back to `.env`.

## 3. Import the database

```bash
mysql -u root -p < data/database.sql
```

This creates the `food-ordering-system` database, all tables, the menu/review/team
seed data, **and a default admin account**:

```
Email:    admin@example.com
Password: admin123
```

**Log in and change this password immediately** (Admin dashboard → Change Password,
or use the same page as any role).

## 4. Run it

Point Apache/nginx at the project root, or for quick local testing:

```bash
php -S localhost:8000
```

Then visit `http://localhost:8000/`.

> **nginx note:** the `.htaccess` files in this project (root, `/image`, `/includes`,
> `/data`) only apply to Apache. On nginx, replicate the same restrictions in your
> server block: deny `.env`/`.sql`, deny direct access to `/includes/*.php`, and
> disable PHP execution inside `/image/`.

## 5. Roles & dashboards

| Role      | Dashboard                | What they can do |
|-----------|---------------------------|-------------------|
| admin     | `pages/admin.php`         | Manage users, orders, food items; view kitchen/delivery queues |
| customer  | `pages/customer.php`      | Browse menu, place orders, view order history, edit profile |
| kitchen   | `pages/kitchen.php`       | Advance orders: Pending → Preparing → Ready for Delivery |
| delivery  | `pages/delivery.php`      | Mark "Ready for Delivery" orders as Delivered |

Anyone can register as Customer, Kitchen, or Delivery from `auth/Register.php`.
Admin accounts are created directly in the database (or by editing an existing
user's role from the admin panel).

Order status flow: `Pending → Preparing → Ready for Delivery → Delivered`
(or `Cancelled` at any point, set manually from Admin → Manage Orders).

## 6. What changed in this hardening pass

- **Environment config**: DB credentials moved out of code and into `.env`
  (`includes/env.php`, `includes/bootstrap.php`).
- **Session security**: sessions are now started centrally with `httponly`
  + `SameSite=Lax` cookies, and the session ID is regenerated on login.
- **CSRF protection**: every state-changing form (login, register, place order,
  add/edit/delete food item, edit/delete user, edit order status, change
  password) now carries and checks a CSRF token (`includes/csrf.php`).
- **Missing pages added**: `pages/kitchen.php`, `pages/delivery.php`,
  `admin/edit_user.php`, `admin/delete_user.php`, `admin/edit_order.php`,
  `account/change_password.php` — all previously linked from the UI but
  didn't exist.
- **Bug fixes**:
  - `auth/Logout.php` didn't match the `logout.php` casing used in every link
    to it — renamed so logout actually works on case-sensitive (Linux) hosts.
  - Several admin/customer pages had `</html>` closing the document before
    the page's real content — fixed so markup is valid and rendering is
    predictable across browsers.
  - Admin-uploaded food images were saved to a `uploads/` folder that nothing
    else looked in, while the menu display and admin list expected `image/` —
    unified so uploaded photos actually show up everywhere.
  - Fixed an undefined `$error` variable in the "edit food item" form.
  - File uploads are now validated (extension allow-list, 5MB limit) and
    saved under random generated names instead of the client-supplied name.
  - Deleting a food item or a user now requires a POST + CSRF token instead
    of a bare link, and deleting a user is blocked if it's your own account
    or the last remaining admin.
- **New**: order line items are now recorded per order (`order_items` table)
  so order history can show what was actually ordered.

## 7. Second pass: navigation, profile unification, demo data

- **Unified Profile page** (`account/profile.php`): every role now has a
  "Profile" link in its nav that shows their info (editable name/phone/
  address) *and* a change-password form on the same page. The old separate
  `account/change_password.php` and `customer/profile.php` still work as
  redirects, so no old bookmarks break.
- **Fixed**: Admin clicking "Kitchen View" / "Delivery View" no longer gets
  bounced to the login page. Those dashboards previously only accepted the
  `kitchen`/`delivery` role exactly; admin can now view (and act on) both,
  and sees a "Back to Admin" link while doing so.
- **Customers can now reach the exact guest-facing pages** (`Home.php`,
  `Menu.php`, `About.php`, `Reviews.php`) from their own nav/sidebar, not
  just the separate `browse_menu.php` ordering screen. The "Order Now"
  buttons on `Menu.php`/`Home.php` now send a logged-in customer straight
  to placing an order instead of back through the login page.
- **Demo data**: `php data/seed_demo_users.php` now also seeds 5 sample
  orders for the demo customer — one each in Pending, Preparing, Ready for
  Delivery, and two Delivered — so the kitchen queue, delivery queue,
  order history, and admin's order list all have real content immediately
  after setup. Safe to re-run; skipped if the demo customer already has
  orders.
- **Fixed**: `script.js` had 4 hardcoded `/FO-System/...` redirect paths —
  one of them was silently overriding the customer "Order Now" button
  fix from the previous pass (it force-redirected every click to the
  register page no matter what the button's actual link said). Paths are
  now root-relative (`/auth/Register.php`, `/Home.php#Home`) instead of
  baking in the project folder's name, so renaming the folder — or
  deploying it at the web root under any name — won't break these.
