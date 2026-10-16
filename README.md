# Pop Shop storefront

## Local setup with XAMPP

1. Start Apache and MySQL in the XAMPP Control Panel.
2. Open phpMyAdmin at `http://localhost/phpmyadmin/` and import `database/schema.sql`. It creates the `noir_form` database with an empty catalog, plus the admin and cash-on-delivery order tables.
3. Open `http://localhost/newproject/`. MySQL defaults to `127.0.0.1`, database `noir_form`, user `root`, and a blank password. Override these with the `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD` environment variables if needed.
4. On the same computer as XAMPP, open `http://localhost/newproject/admin/setup.php` and create an admin account with a username (or email) and a password of at least 12 characters. The setup page is only available to local connections and closes after the first admin account is created; no separate setup key is needed.
5. Sign in at `http://localhost/newproject/admin/login.php`. Use `localhost` or `127.0.0.1` for first-time setup; the setup page intentionally blocks connections from other devices.

The storefront starts with an empty catalog and a single **Keychains** category. Sign in to `/admin/` and use **Products** to add, edit, and hide/restore keychains; the category is fixed to **Keychains**. Use **Add a color** in a product form to give one product multiple color choices, each with its own swatch color and photo. Shoppers can switch colors in that single product card; the selected color and photo are carried into the bag, checkout, and order details. You can add up to 8 colors per product. To add a product photo, use the photo fields to take a picture with your device camera or choose an existing JPEG, PNG, or WebP image (up to 8 MB per image). Existing products keep their photos when editing unless you choose replacements. Photos are saved in `uploads/products/`. Use **Orders** to review customer contact and shipping details, payment status, order contents, selected colors, and totals. On delivery, use an order's details page to record the cash collection and update fulfillment status.

For an existing database, run `database/migrate-product-colors.sql` once in phpMyAdmin before using color options. New databases receive the color-variant tables and order color snapshot field from `database/schema.sql`.

For an existing installation with the old sample catalog, run `database/migrate-keychains-catalog.sql` once before adding new keychains. This hides the old products without deleting product records or order history.

## Cash-on-delivery checkout

Prices are entered and displayed in Pakistani rupees (PKR), shown as Rs. Customers enter delivery details and place an order without a Stripe account, payment key, or online payment. Product prices and shipping are checked on the server. Shipping is Rs 12 below Rs 150 and complimentary on orders of Rs 150 or more. The order is saved as awaiting cash collection in the admin dashboard. After collecting cash, open the order in **Orders** and select **Mark cash as collected**.

For a database created with an earlier Stripe-enabled version, run `database/migrate-cash-on-delivery.sql` once in phpMyAdmin. The migration preserves existing orders and their payment status, labels historical card orders, and removes the old Stripe webhook/payment-session data. Back up the `noir_form` database before migrating. The fresh install schema already uses cash-on-delivery orders. Taxes and email notifications are not configured. Use HTTPS when deploying the site publicly.
