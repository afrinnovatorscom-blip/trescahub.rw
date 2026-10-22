# Marketplace deployment

This upgrade adds private-vendor marketplace tables and screens to the existing Laravel application. It does not replace the shop, service catalog, checkout-by-WhatsApp button, or customer accounts.

## Before you deploy

1. Take a full database backup and a copy of the application files.
2. Deploy the code to a staging copy first. Do not point this work at production until staging migration and a shop browse both succeed.
3. Leave the production `.env` file in place. Do not replace it from `.env.example`.
4. Confirm `APP_KEY` stays the same so existing sessions and encrypted values keep working.

## Deploy

1. Put the site in maintenance mode if you need a quiet migration window: `php artisan down`.
2. Upload the new code, including `database/migrations/2026_10_22_120000_add_marketplace_module.php`.
3. Install dependencies the same way you already do (`composer install --no-dev` on the server).
4. Run `php artisan migrate --force`.
   - The original shop products migration skips its MySQL full-text index when the driver is SQLite, so the test suite can build a fresh database. Production MySQL already applied that migration, so this file change does not rebuild the live index.
   - The migration only adds tables and columns. It does not delete shop products, users, services, or orders.
   - Products that already exist are marked approved and published so the current shop stays visible.
   - Products with no vendor stay vendor-less. Do not invent supplier records for them.
   - The oldest user account becomes Super Administrator if nobody already has that role. Sign in as that user and assign roles to other staff from Admin → Users.
5. Run `php artisan marketplace:sync-permissions` on later deploys so new permission names are inserted without wiping custom role grants.
6. Run `php artisan optimize:clear` after the migration.
7. Bring the site back: `php artisan up`.
8. Check `/`, `/shop`, `/services`, `/request-service`, an existing product page, and `/track-order`.

New products stay off the public shop until someone with approval and publish permissions deliberately approves and publishes them. Vendor accounts must be Active before their approved products appear.

## Rollback

1. Restore the database backup if the new tables have already collected live orders you need to discard. That is the safe rollback.
2. If you must roll back only this migration and you have a backup of the pre-migration database, restore that backup instead of dropping columns by hand.
3. `php artisan migrate:rollback --step=1` drops the marketplace tables and the columns added to `shop_products` and `users`. It does not drop `shop_products` itself. Take the backup first, because vendor, order, request, and stock history in those new tables will be removed.
4. Redeploy the previous application code after the schema rollback so the old code is not reading columns that no longer exist.

## Staff access

- Customers who register receive the Customer role and cannot open the old admin screens.
- Vendors only see records for their own vendor account.
- Acquisition cost and vendor contact details stay behind `finance.view` and `vendors.view_contacts`.
- Public order tracking asks for the order reference plus the phone number or email used at checkout. The reference alone is not enough.

## Mail

In-app notifications are stored immediately. Email is sent only when `MAIL_MAILER` is `smtp`, `ses`, `postmark`, `resend`, or `mailgun`. The default `log` mailer does not send customer email.
