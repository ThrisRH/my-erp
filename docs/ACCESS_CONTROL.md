# Account access and permissions

The login page is `/login`. The first administrator is the only account with `is_admin = 1`; the database enforces that limit. The server-side `userStorage` session holds its account ID, and the shared `Access` helper bypasses ACL checks only after that ID is verified against the database. Other accounts need exact permission strings in `accounts.permissions` (a JSON array). Missing or unknown permissions return HTTP 403 in English. Finance data remains scoped to the account's employee ID.

## Database migration

New MySQL volumes run `docker/mysql/init/010_account_acl.sql` automatically. On an existing volume, apply it once:

```sh
docker compose exec -T db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < docker/mysql/init/010_account_acl.sql
```

## First administrator

Set `ADMIN_USERNAME` and a unique `ADMIN_PASSWORD` of at least 12 characters. To retain access to an existing employee's finance data, also set `ADMIN_EMPLOYEE_ID` to that employee's ID. If there is no employee yet, set `ADMIN_EMAIL` instead and the provisioner creates one. Run the provisioner inside the app container; never put the password in Git:

```sh
docker compose exec -T -e ADMIN_USERNAME -e ADMIN_PASSWORD -e ADMIN_EMPLOYEE_ID app php bin/provision-admin.php
```

The command refuses to create a second administrator. If using `docker-compose.private.yml`, replace `docker compose` above with `docker compose -f docker-compose.private.yml`.

## Employee accounts

Create the employee first, then set `ACCOUNT_USERNAME`, `ACCOUNT_PASSWORD`, `ACCOUNT_EMPLOYEE_ID`, and `ACCOUNT_PERMISSIONS` (JSON array) for the provisioner:

```sh
docker compose exec -T -e ACCOUNT_USERNAME -e ACCOUNT_PASSWORD -e ACCOUNT_EMPLOYEE_ID -e ACCOUNT_PERMISSIONS app php bin/provision-account.php
```

Permission keys: `app.view`, `finance.view`, `finance.transaction.create`, `finance.transaction.update`, `finance.wallet.create`, `finance.category.create`, and `finance.budget.manage`. An empty array grants no page access. The administrator bypasses these checks. Changes to account status or permissions take effect on the next request.

This adds basic authentication and authorization, but the current PHP 8.0 runtime is unsupported. Keep the app behind Tailscale until PHP and dependencies are upgraded and the security posture is reviewed.

## Self-registration and password recovery

Employees may register at `/register` using their email address. Their account starts disabled, receives only permissions for their own finance data, and becomes active only after opening a verification link (24-hour expiry). `/forgot-password` sends a reset link to a verified active account (30-minute expiry). Links are single-use and only their SHA-256 hashes are stored in MySQL. A password reset invalidates existing login sessions.

Local development uses `MAIL_TRANSPORT=outbox`. Messages are stored only inside the PHP container at `/tmp/my-erp-mail-outbox` with restricted permissions; inspect them using `docker compose exec app ls -t /tmp/my-erp-mail-outbox`. This is not a production email service. On the private VM, set `APP_BASE_URL` to the Tailscale HTTPS URL and configure `MAIL_TRANSPORT=smtp`, `MAIL_FROM`, `SMTP_HOST`, `SMTP_PORT`, `SMTP_USERNAME`, `SMTP_PASSWORD`, and `SMTP_ENCRYPTION` (`starttls` or `smtps`) in the untracked `.env`. If SMTP is not configured, registration/recovery are unavailable and no reset link is exposed in the browser.

For an existing database, apply `docker/mysql/init/011_account_recovery.sql` once after `010_account_acl.sql`. New volumes run both scripts automatically.
