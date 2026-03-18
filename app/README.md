# Symfony Web Solution

This folder contains the assessment web solution built with Symfony 7.4.

## Author

- Name: Ahmad Dadoush
- email: ah.dadoush@gmail.com

## Requirements

- PHP 8.2+
- Composer 2+
- PostgreSQL 16+ (or compatible database)

## Setup

1. Install dependencies:

```bash
composer install
```

2. Configure environment:

- Set `APP_SECRET`.
- Set `DATABASE_URL` in `.env.local`.
- Set `MAILER_DSN`

Example:

```env
DATABASE_URL="postgresql://app:password@127.0.0.1:5432/app?serverVersion=16&charset=utf8"
```

3. Prepare database:

```bash
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate -n
```

### First Run Without Docker (Composer + Yarn)

If you run the project locally without Docker, continue with frontend setup:

1. Install frontend dependencies:

```bash
yarn install
```

2. Build frontend assets:

```bash
yarn build
```

3. (Optional while developing) rebuild assets on changes:

```bash
yarn watch
```

4. Run the application:

```bash
symfony server:start
```

If Symfony CLI is not available:

```bash
php -S 127.0.0.1:8000 -t public
```

Open `http://127.0.0.1:8000`.

## Application Performance Notes

These notes are specific to Symfony application performance (independent of container runtime):

1. Use `APP_ENV=prod` and `APP_DEBUG=0` for realistic runtime performance checks.
2. Warm caches before measuring: `php bin/console cache:clear --env=prod`.
3. Keep database indexes up to date for frequently filtered or sorted fields.
4. Avoid N+1 queries by eager-loading relations when needed.
5. Build frontend assets in optimized mode for production: `yarn build`.
6. Profile slow endpoints with Symfony profiler in `dev`, then validate improvements in `prod`.

Quick check:

- Compare first request and repeated request times after cache warmup.

## Useful Commands

Run tests:

```bash
php bin/phpunit
```

Or prepare the test database and run all tests in one command:

```bash
composer test
```

CI-oriented run with test database cleanup at the end:

```bash
composer test:ci
```

Drop only the test database manually:

```bash
composer test:clean
```

Clear cache:

```bash
php bin/console cache:clear
```

Send reminder emails manually:

```bash
php bin/console app:send-reminders
```

## Cron Job (Reminder Emails)

To send reminder emails automatically every day at 07:00, add this cron entry on the host machine that runs Docker:

```cron
0 7 * * * cd /path/to/microlab-assessment && docker compose exec -T php php bin/console app:send-reminders >> /var/log/microlab-reminders.log 2>&1
```

Notes:
- Replace `/path/to/microlab-assessment` with your real project path.
- `-T` disables TTY so cron can run the command non-interactively.
- If you already run `docker compose` from the project directory in another way, adjust the command accordingly.

### Non-Docker Cron Example

If you run the app directly on the host (no Docker), use:

```cron
0 7 * * * cd /path/to/microlab-assessment/app && /usr/bin/php bin/console app:send-reminders --env=prod >> /var/log/microlab-reminders.log 2>&1
```

Notes:
- Replace `/usr/bin/php` with the output of `which php` on your server.
- Ensure `APP_ENV=prod` and `DATABASE_URL` are correctly set for the cron environment.

## Notes

- This `app/` folder is the assessment deliverable.
- Local Docker workflow is documented in repository root `README.md`.
