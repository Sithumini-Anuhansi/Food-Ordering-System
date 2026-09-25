# 🍔 Food Ordering System

A full-stack **PHP and MySQL food ordering web application** with separate workflows for customers, kitchen staff, delivery staff, and administrators.
The system provides a complete restaurant ordering flow — from browsing food items and placing an order to kitchen preparation, delivery, order history, reviews, and administrative analytics.

> **Project type:** Web Application  
> **Backend:** PHP  
> **Database:** MySQL / MariaDB  
> **Frontend:** HTML, CSS, JavaScript, PHP-rendered pages  
> **Server:** Apache or PHP built-in development server

---

## ✨ Features

### 👤 Customer

* Register and log in
* Email verification flow
* Browse the food menu and categories
* View food details, prices, ratings, and availability
* Add food items to an order with quantities
* Checkout with:
  - Delivery address
  - ASAP or scheduled delivery
  - Special instructions
  - Payment method
* Cash on Delivery support
* Optional Stripe card-payment integration
* View order history and order status
* Cancel eligible pending orders
* Rate completed orders
* Manage profile information
* Change password
* Forgot/reset password

### 👨‍🍳 Kitchen

* View incoming orders
* Move orders through the preparation workflow
* Track stale orders
* Automatically refresh the kitchen queue
* Set estimated ready times

### 🚴 Delivery

* View orders ready for delivery
* Claim/handle delivery orders
* Mark orders as delivered
* Automatically refresh the delivery queue

### 🛠️ Admin

* Dashboard and operational views
* Manage users
* Manage food items
* Add/edit/delete menu items
* Upload food images
* Manage orders
* Edit order status
* Manage customer reviews
* View kitchen and delivery views
* View analytics including:
  - Total revenue
  - Order count
  - Average order value
  - Orders by status
  - Last 7 days of sales
  - Top-selling items

### 🔐 Security & Reliability

* Environment-based database configuration
* Password hashing
* CSRF protection
* Secure session configuration
* Session ID regeneration after login
* Login rate limiting
* Password reset tokens with expiry
* Email verification tokens with expiry
* File-upload validation
* Randomized uploaded filenames
* Protection for `.env`, SQL files, and sensitive PHP directories
* Production-friendly error handling
* GitHub Actions PHP syntax checks

---

## 🧰 Technologies Used

| **Technology** | **Purpose** |
| :--- | :---: |
| PHP	| Server-side application logic |
| MySQL / MariaDB |	Relational database |
| HTML5 |	Page structure |
| CSS3 | Styling and responsive UI |
| JavaScript | Client-side interactions and auto-refresh |
| MySQLi | PHP database connectivity |
| Apache / PHP Server |	Local or production web hosting |
| Stripe API | Optional card payments |
| SMTP | Optional email delivery |
| GitHub Actions | Automated PHP syntax validation |

---

## 📁 Project Structure

```text
Food-Ordering-System/
│
├── admin/                  # Admin management and analytics
├── api/                    # API endpoints
├── auth/                   # Login, registration, verification, password reset
├── account/                # Profile and password management
├── crud/                   # Food-item CRUD operations
├── customer/               # Customer menu, checkout, orders and reviews
├── data/                   # Database schema, migrations and demo data
├── image/                  # Food and website images
├── includes/               # Shared configuration, security and helper files
├── pages/                  # Role-based dashboard pages
│
├── Home.php                # Home page
├── Menu.php                # Public menu
├── About.php               # About page
├── Reviews.php             # Reviews page
├── Privacy.php             # Privacy policy
├── Terms.php               # Terms and conditions
├── configure.php           # Database connection
├── health.php              # Application health check
│
├── .env.example            # Environment configuration template
├── SETUP.md                # Detailed setup and deployment guide
└── .github/workflows/      # GitHub Actions CI configuration
```

---

## 🔄 Order Workflow

The main order lifecycle is:
```text
Customer places order
        │
        ▼
     Pending
        │
        ▼
    Preparing
        │
        ▼
 Ready for Delivery
        │
        ▼
    Delivered
```

A pending order can also be cancelled by the customer when cancellation is allowed.

---

## 🗄️ Database
The project uses MySQL/MariaDB with the following main tables:
`users`
`food_items`
`orders`
`order_items`
`reviews`
`team_members`

Database files are located in:
```text
data/
├── database.sql
├── migration_v2.sql
├── migration_v3.sql
└── migration_v4.sql
```

`database.sql` creates a fresh database and includes the initial application data.
For existing installations, use the appropriate migration script described in `SETUP.md`.

---

## 🚀 Installation & Setup

### 1. Requirements

Install:
* PHP 7.4 or later
* MySQL or MariaDB
* Apache, or PHP's built-in development server
* `mysqli` PHP extension

For optional features:
* Stripe account/API keys for card payments
* SMTP provider for real email delivery

---

### 2. Clone or download the project

```bash
git clone <your-repository-url>
cd Food-Ordering-System
```

---

### 3. Create the environment file

Copy:
```text
.env.example
```
to:
```text
.env
```

Example:
```env
APP_ENV=local

DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=food-ordering-system

APP_FORCE_SECURE_COOKIES=false
APP_BASE_URL=http://localhost:8000
APP_FORCE_HTTPS=false
```

**Never commit `.env` to Git.**

---

### 4. Create the database

Using MySQL:
```bash
mysql -u root -p < data/database.sql
```

Or import `data/database.sql` through phpMyAdmin/MySQL Workbench.

---

### 5. Create demo users

Run:
```bash
php data/seed_demo_users.php
```

This creates demo accounts for the customer, kitchen, and delivery roles.
The default admin account is created by `database.sql`.

**Demo accounts**
| Role | Email | Password |
| :--- | :---: | ---: |
| Admin	| `admin@example.com` | `admin123` |
| Customer | `customer@example.com` | `customer123` |
| Kitchen | `kitchen@example.com` | `kitchen123` |
| Delivery | `delivery@example.com` | `delivery123` |

> These are development/demo credentials. Change or remove them before deploying publicly.

---

### 6. Run the application

For quick local development:
```bash
php -S localhost:8000
```

Then open:
```text
http://localhost:8000/
```

For Apache, place the project inside the configured web root and ensure `.htaccess` support is enabled.

---

## 💳 Optional Stripe Integration

The application is structured to support Stripe card payments.

Add your test keys to `.env`:
```env
STRIPE_SECRET_KEY=
STRIPE_PUBLISHABLE_KEY=
STRIPE_WEBHOOK_SECRET=
```

If Stripe is not configured, Cash on Delivery remains available.
Do not commit real Stripe keys or other secrets to GitHub.

---

## 📧 Optional Email Configuration

The application can use SMTP for email verification and password-reset emails.

Example:
```env
SMTP_HOST=
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USER=
SMTP_PASS=
SMTP_FROM=no-reply@example.com
SMTP_FROM_NAME=Food Ordering System
```

For local development, the password-reset flow can still be tested without a fully configured mail server.

---

## 🔒 Security Notes

This project includes several security-focused measures:
* Credentials are loaded from environment variables.
* Passwords are stored using PHP password hashing.
* State-changing forms use CSRF tokens.
* Sessions use secure cookie settings.
* Login attempts are rate-limited.
* Password reset links are time-limited.
* Uploaded images are restricted by file type and size.
* Uploaded filenames are generated rather than trusting user-supplied filenames.
* Sensitive files are protected using server configuration.
* Production mode avoids exposing database errors to visitors.

For production deployment, always:
1. Use HTTPS.
2. Replace demo credentials.
3. Use strong database credentials.
4. Configure a real SMTP service.
5. Configure Stripe only with secure server-side secrets.
6. Review Apache/nginx access rules.
7. Keep PHP, MySQL/MariaDB, and dependencies updated.

---

## 🧪 Continuous Integration

GitHub Actions is configured under:
```text
.github/workflows/ci.yml
```

The workflow:
* Runs on pushes and pull requests
* Sets up PHP 8.2
* Checks every PHP file using `php -l`
* Verifies that `.env` is not committed

This helps catch PHP syntax errors before merging changes.

---

## 🧑‍💻 Main Application Areas

| Area | Location |
| :--- | :---: |
| Authentication | `auth/` |
| Customer functions | `customer/` |
| Admin functions	| `admin/` |
| Kitchen workflow	| `pages/kitchen.php` |
| Delivery workflow	| `pages/delivery.php` |
| Food CRUD |	`crud/` |
| API | `api/` |
| Database | `data/` |
| Account/profile |	`account/` |
| Shared configuration/security |	`includes/` |

---

## 📌 API

The project includes a menu API endpoint:
```text
GET /api/get_menu.php
```

It provides menu information for application functionality that consumes food-item data programmatically.

---

## 🎯 Project Objectives

This project demonstrates practical implementation of:
* Role-based authentication and authorization
* CRUD operations
* Relational database design
* Order and inventory management
* Checkout workflows
* Payment integration architecture
* Secure PHP development
* File uploads
* Email verification and password recovery
* Admin analytics
* REST-style API functionality
* Git/GitHub workflow and CI

---

## 📝 Documentation

For detailed installation, migration, deployment, and configuration instructions, see:
```text
SETUP.md
```

---

## 👩‍💻 Author

**Sithumini Anuhansi**

Software Engineering Undergraduate - NIBM

[![Email](https://img.shields.io/badge/Email-D14836?style=flat&logo=gmail&logoColor=white)](mailto:anuhansisithumini@gmail.com)
[![LinkedIn](https://img.shields.io/badge/LinkedIn-0077B5?style=flat&logo=linkedin&logoColor=white)](https://www.linkedin.com/in/sithumini-anuhansi-5b32a8334)

---

## 📄 License

This project is licensed under the [MIT License](LICENSE) - see the [LICENSE](LICENSE) file for details.

---

<div align="right">
<img src="https://visitor-badge.laobi.icu/badge?page_id=Sithumini-Anuhansi.Food-Ordering-System&left_text=Views"/>
</div>
