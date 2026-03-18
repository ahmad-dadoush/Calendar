# Local Development (Docker)

This file is for local development in this repository.
The assessment deliverable itself is the Symfony web solution in `app/`.

## Author

- Name: Ahmad Dadoush
- email: ah.dadoush@gmail.com

## Requirements

- Docker Desktop (or Docker Engine + Compose)

## Start or Rebuild Containers

From repository root:

```bash
docker compose up -d --build
```

## Backend Setup (inside php container)

```bash
docker compose exec -w /var/www/html/app php composer install
docker compose exec -w /var/www/html/app php php bin/console doctrine:database:create --if-not-exists
docker compose exec -w /var/www/html/app php php bin/console doctrine:migrations:migrate -n
```

## Frontend Setup (TypeScript + SCSS via Encore)

```bash
docker compose exec -w /var/www/html/app php yarn install
docker compose exec -w /var/www/html/app php yarn dev
```

Note: `package.json` and `tsconfig.json` are already part of this repository,
so no extra `yarn add ...` or `yarn tsc --init` bootstrap commands are needed.

Watch mode:

```bash
docker compose exec -w /var/www/html/app php yarn watch
```

Production assets:

```bash
docker compose exec -w /var/www/html/app php yarn build
```

## Run App

Open `http://localhost:8000`.

## Docker Performance Notes (Windows)

These notes are for Docker runtime performance on Windows hosts:

1. Best improvement usually comes from keeping the project in WSL2 filesystem (for example under `/home/<user>/...`) instead of mounting from `C:` or `D:`.
2. Allocate enough CPU and RAM to Docker Desktop.
3. Avoid running `yarn watch` continuously unless actively editing frontend files.
4. Stop unused containers/services to reduce host and VM load.
5. Exclude heavy project paths from antivirus real-time scanning when possible.

Expectation:

- Performance is not guaranteed to be exactly 100 percent faster on every machine; gains depend on hardware and Docker/Desktop configuration.

## Useful Commands

```bash
docker compose exec -w /var/www/html/app php php bin/phpunit
docker compose exec -w /var/www/html/app php php bin/console cache:clear
```
