# Local Development (Docker)

This file is for local development in this repository.
The assessment deliverable itself is the Symfony web solution in `app/`.

## Author

- Name: Ahmad Dadoush
- email: ah.dadoush@gmail.com

## Requirements

- Docker Desktop (or Docker Engine + Compose)

## Docker File Split

- Local development uses `docker-compose.yml` with `Dockerfile.dev`.
- Production/GHCR deployment uses `docker-compose.prod.yml` with prebuilt images.

## GHCR On Git Tags

- Every new git tag triggers image publishing via `.github/workflows/ghcr-on-tag.yml`.
- Workflow order: `tests` -> `publish images` -> `manual deploy approval` -> `deploy`.
- Images are built and deployed only when tests pass.
- Deploy is manual through GitHub Environment approval (`production`).
- Published images:
  - `ghcr.io/ahmad-dadoush/calendar-php:<tag>`
  - `ghcr.io/ahmad-dadoush/calendar-nginx:<tag>`
- After image publish, the workflow also deploys the same tag to your server over SSH.

Required GitHub repository secrets:

- `DEPLOY_HOST`: server hostname or IP.
- `DEPLOY_PORT`: SSH port (usually `22`).
- `DEPLOY_USER`: SSH username.
- `DEPLOY_SSH_KEY`: private SSH key used by GitHub Actions.
- `DEPLOY_PATH`: absolute path on server containing `docker-compose.prod.yml` and `.env`.
- `GHCR_USER`: GitHub username for registry login on server.
- `GHCR_TOKEN`: token with `read:packages` scope for server-side pull.

One-time GitHub setup:

- Create environment `production` in repository settings.
- Add required reviewers for that environment.
- The deploy job will wait for approval after tests and image publish succeed.

Security hardening for public repositories:

- Keep repository access private to your account only (no collaborators with write/admin unless needed).
- Protect release tags (for example `v*`) so only you can create/push them.
- In `Settings -> Actions -> General`, restrict allowed actions to GitHub-owned and verified creators only.
- Keep `Allow GitHub Actions to create and approve pull requests` disabled unless required.
- This workflow is additionally guarded to run only when actor and repository owner are `ahmad-dadoush`.

Create and push a tag:

```bash
git tag v1.0.0
git push origin v1.0.0
```

Deploy that exact tag on server (set in `.env`):

```env
IMAGE_TAG=v1.0.0
```

Server prerequisites:

- Docker + Docker Compose installed.
- Project directory on server contains `docker-compose.prod.yml` and a valid `.env`.
- `.env` contains runtime vars (`APP_SECRET`, `DATABASE_URL`, `MAILER_DSN`, DB credentials).

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
