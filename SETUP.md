# Food Ordering System — Setup Guide

## Before you run it — manual checklist

1. **Create `.env`** (see step 2 below) — the app won't connect to a database without it.
2. **Import `data/database.sql`** into MySQL (step 3). If you already have
   a database from an earlier version of this project, run
   `data/migration_v2.sql` instead (see section 8 below) to add the new
   columns without losing existing data.
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

## 8. Third pass: real ordering flow + operational features

**Run `mysql -u root -p food-ordering-system < data/migration_v2.sql`
first** if you already have data you want to keep — this adds all the new
columns below without touching existing rows. Fresh installs get them
automatically via `database.sql`.

- **The customer ordering page now shows the exact same menu as the public
  Menu page** (`customer/browse_menu.php`) — same category carousel, same
  item cards — instead of a bare table. Each card has a quantity stepper
  and its own "Order Now" button; clicking it (even at the default
  quantity of 0) adds that item and takes you to checkout with everything
  selected so far across the whole page.
- **Real checkout flow**: `customer/checkout.php` shows an order summary,
  then collects delivery address (prefilled from your profile, editable
  per order), delivery time (ASAP or scheduled), special instructions,
  and payment method — before `customer/place_order.php` finalizes it.
  Delivery fee (flat $2.50) and 8% tax are calculated server-side in
  `includes/pricing.php`, the single place that formula lives so the
  preview and the saved order can never disagree.
- **Payment**: structured as "Cash on Delivery" for now, with a `Card
  Payment (coming soon)` option shown-but-disabled — the `orders` table
  already has `Payment_Method`/`Payment_Status` columns so a real gateway
  (Stripe, PayHere, etc.) can be dropped in later without a schema change.
  **This needs your own payment provider account/API keys** — not
  something that can be wired up without them.
- **Order cancellation**: customers can cancel a still-`Pending` order
  from Order History (`customer/cancel_order.php`); any reserved stock is
  automatically given back.
- **Inventory tracking**: food items now have an optional
  `Stock_Quantity` (admin's Add/Edit Food Item forms) — leave it blank
  for unlimited (the old behavior), or set a number to have it decrement
  per order and auto-mark the item unavailable at zero.
- **Forgot / reset password**: `auth/forgot_password.php` generates a
  time-limited reset token and attempts to email it; since most local/dev
  setups have no SMTP configured, the reset link is also shown directly
  on screen so the flow is testable without a mail server. Wire up real
  SMTP (or a transactional email service) for production use.
- **Login rate limiting**: 5 failed attempts locks the account for 15
  minutes (`users.Failed_Login_Attempts` / `Lockout_Until`). Login error
  messages no longer reveal whether an email is registered.
- **Stale order warning**: the kitchen dashboard flags any order that's
  been sitting for more than 15 minutes. Kitchen and delivery dashboards
  also auto-refresh every 30 seconds so new orders show up without a
  manual reload.
- **Admin analytics** (`admin/analytics.php`): total revenue, order
  count, average order value, orders by status, last-7-days sales, and
  top 5 items by quantity sold.

## 9. Fourth pass: payments, email, HTTPS, CI, legal pages

**Run `mysql -u root -p food-ordering-system < data/migration_v3.sql`
first** if you already have data — adds email verification and Stripe
tracking columns without touching existing rows.

Everything below is genuinely optional and off by default — leave the
relevant `.env` values blank and the app behaves exactly as before
(Cash on Delivery only, no real email sending, no HTTPS redirect).

### Card payments (Stripe)

Built directly against Stripe's REST API over cURL — no Composer/SDK
needed (`includes/stripe.php`).

1. Create a free Stripe account, then grab **test-mode** keys (no real
   business or bank account required) from
   https://dashboard.stripe.com/test/apikeys
2. In `.env`, set `STRIPE_SECRET_KEY` and `STRIPE_PUBLISHABLE_KEY`.
3. Set `APP_BASE_URL` to wherever you're running the app (e.g.
   `http://localhost:8000` for `php -S`) — this is used to build the
   success/cancel URLs Stripe redirects back to.
4. "Card Payment" becomes selectable at checkout once
   `STRIPE_SECRET_KEY` is set — the option is hidden/disabled otherwise.
5. **Webhook (required for payment status to actually update):**
   `orders.Payment_Status` is only ever flipped to `Paid` by
   `webhooks/stripe_webhook.php` — Stripe's redirect back to your site is
   just UI, never proof of payment on its own. For local testing, use the
   [Stripe CLI](https://stripe.com/docs/stripe-cli):
   ```
   stripe listen --forward-to localhost:8000/webhooks/stripe_webhook.php
   ```
   This prints a `whsec_...` value — put that in `STRIPE_WEBHOOK_SECRET`.
   For production, add the webhook URL in the Stripe dashboard instead
   (Developers → Webhooks) listening for `checkout.session.completed`,
   and use the signing secret it gives you.
6. Test-mode card number `4242 4242 4242 4242`, any future expiry, any
   CVC, completes a test payment.

### Email (SMTP)

`includes/mailer.php` is a small hand-rolled SMTP client (STARTTLS/SSL +
AUTH LOGIN) — again, no Composer dependency. Works with any provider:

- **Local testing**: [Mailtrap](https://mailtrap.io) (free tier) — catches
  mail in a web inbox instead of actually delivering it, ideal for dev.
- **Production**: SendGrid, Amazon SES, your own mail server, or Gmail
  with an [app password](https://myaccount.google.com/apppasswords)
  (regular Gmail passwords won't work over SMTP).

Set `SMTP_HOST`, `SMTP_PORT`, `SMTP_SECURE` (`tls` or `ssl`), `SMTP_USER`,
`SMTP_PASS`, `SMTP_FROM` in `.env`. Leave `SMTP_HOST` blank to disable —
the app falls back to logging what would have been sent instead of
erroring.

Wired into: registration (email verification), forgot password, order
confirmation, and every order status change (kitchen/delivery/admin
updates all notify the customer).

### Email verification

New accounts start with `Email_Verified = 0` and get a verification link
by email. Unverified users can still log in and use the site (a banner
prompts them to verify, with a resend link) rather than being locked out
entirely — safer default in case email delivery has issues. Accounts
that existed before this migration are automatically marked verified.

### HTTPS enforcement

Set `APP_FORCE_HTTPS=true` in `.env` once you have a real domain and TLS
certificate — every HTTP request then 301-redirects to HTTPS, and an
HSTS header is sent. **Leave this `false` for local dev** — `php -S`
doesn't serve HTTPS at all, so turning this on locally breaks every page
load. Also checks the `X-Forwarded-Proto` header, so it works correctly
behind a reverse proxy/load balancer that terminates TLS itself.

### CI

`.github/workflows/ci.yml` runs on every push/PR: lints every PHP file
for syntax errors (`php -l`) and fails the build if `.env` is ever
accidentally committed. No secrets or setup needed — it just runs once
this is pushed to GitHub.

### Legal pages

`Privacy.php` and `Terms.php`, linked in the footer. **These are
templates, not legal advice** — have an actual lawyer review and adapt
them (especially data retention, liability, and dispute sections) before
this handles real customers.

### Monitoring

`health.php` returns JSON (`{"status":"ok","database":"connected",...}`)
and a 503 if the database is unreachable — point any uptime monitor
(UptimeRobot, Better Uptime, or your own cron+curl) at it. For real error
tracking (stack traces, alerting), consider Sentry — needs a DSN from
your own Sentry account, not wired up here.

### Deliberately not done this round: PDO migration & automated tests

Both came up as recommendations, but neither is something to bolt on
alongside everything above without real risk. The app currently uses
`mysqli` consistently across ~45 files — migrating to PDO is a large,
mechanical, error-prone rewrite that deserves its own dedicated pass
with careful testing, not a rushed pass squeezed in with unrelated
features. Same for automated tests: writing them well means testing
against the *actual* rewritten code, so it makes sense to sequence
tests after (or alongside) that migration rather than before it. Ask
for either as a focused next task whenever you're ready.
