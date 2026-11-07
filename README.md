# Pasar Gaib

Pasar Gaib is a folklore-inspired Indonesian e-commerce project for browsing and ordering traditional and mystical-themed collectibles. It includes a customer storefront, a PHP/MySQL backend, and an admin area for managing products and orders.

## Features

- Browse products and categories, search and filter the catalog, and view product details.
- Add products to a browser-based shopping cart and submit an order.
- Choose Cash on Delivery (COD), QRIS, or bank transfer at checkout.
- View order and payment status.
- Manage products and order statuses through an admin dashboard.
- Responsive storefront layout for desktop and mobile screens.

> **Payment notice:** QRIS and bank transfer confirmation are demo flows. The application does not connect to a payment provider or verify real transactions. Do not use this project to collect real payments without implementing and testing a payment gateway.

## Tech Stack

- HTML, CSS, and vanilla JavaScript modules
- PHP 8.1 or later
- MySQL or MariaDB, using PDO
- Apache (for example, through XAMPP)

No Node.js or Composer dependencies are required.

## Requirements

- PHP 8.1+ with the PDO MySQL extension enabled
- MySQL or MariaDB
- Apache or another web server configured to run PHP

## Local Setup with XAMPP

1. Copy or clone the project into XAMPP's `htdocs` directory. The instructions below assume the project folder is named `toko-dukun`.
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.
3. Create a dedicated database named `pasar_gaib` in phpMyAdmin or the MySQL command line.
4. Import [`sql query/pasar_gaib.sql`](sql%20query/pasar_gaib.sql) into that database. The dump creates the tables and includes sample records. Import it only into a dedicated database: it contains `DROP TABLE` statements and replaces tables with matching names.
5. Configure the local database connection in [`config/database.php`](config/database.php). Set the host, database name, username, and password for your local MySQL instance.
6. Open the storefront at [http://localhost/toko-dukun/](http://localhost/toko-dukun/).

The storefront calls PHP endpoints under `/api`, so it must be served through Apache (or another PHP-enabled web server); opening the HTML files directly with `file://` will not work correctly.

## Admin Area

Open [http://localhost/toko-dukun/admin/login.php](http://localhost/toko-dukun/admin/login.php) to sign in. The imported database contains an admin record with a password hash, but this repository does not provide its plaintext password. Create or update an admin account using a password hash generated with PHP's `password_hash()` function; do not store a plaintext password.

The admin area includes a dashboard, product management, and order detail/status management.

## Project Structure

```text
admin/       Admin login, dashboard, product and order management
api/         JSON endpoints for products, categories, orders, and payments
asset/       Product images, logo, and payment images
assets/css/  Storefront stylesheets
assets/js/   Storefront behavior and API client
config/      Database connection
sql query/   MySQL database schema and sample data
*.html       Storefront, catalog, cart, checkout, and order pages
```

## Main Pages

- `index.html` - storefront home page
- `products.html` - product catalog
- `product-detail.html` - product details
- `cart.html` - shopping cart
- `checkout.html` - customer and payment details
- `order-success.html` - order confirmation
- `qris.html` and `transfer.html` - demo payment instructions

## API Endpoints

The frontend uses PHP endpoints in the `api/` directory:

- `products.php` and `categories.php` - catalog data
- `orders.php` - create an order
- `order-detail.php` - retrieve order details
- `payment-status.php` and `payment-confirm.php` - demo payment status and confirmation

## Configuration and Deployment

- Keep database credentials private. Configure credentials for each environment without publishing secrets to source control.
- The checked-in SQL dump includes sample customers, orders, and product data. Review and replace that data before deploying.
- Configure the web server to serve the project from its intended base path, and ensure PHP and the PDO MySQL extension are enabled.
- Before production use, add real payment-provider integration and review security, privacy, and deployment settings.
