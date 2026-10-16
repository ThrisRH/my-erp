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
