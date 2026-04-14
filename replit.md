# Chamilo 2.0 LMS

## Overview
Chamilo is an open-source Learning Management System (LMS) and e-learning collaboration platform. This is version 2.0, built with Symfony 6.4 (PHP) and Vue.js 3.

## Tech Stack
- **Backend**: PHP 8.2 + Symfony 6.4 + Doctrine ORM
- **Frontend**: Vue.js 3, Webpack Encore, Tailwind CSS, SCSS
- **API**: API Platform 3.0, JWT Authentication
- **Database**: MySQL 8.0 (running locally)
- **Package Managers**: Composer (PHP), Yarn 4 (JS)

## Architecture
- `src/` - Symfony PHP source (CoreBundle, CourseBundle, LtiBundle)
- `assets/` - Frontend source (Vue components, CSS/SCSS, JS)
- `public/` - Web root (entry point index.php, legacy PHP, build assets)
- `config/` - Symfony configuration
- `translations/` - i18n translation files
- `var/` - Cache and logs

## Running the App
The `start.sh` script handles everything:
1. Starts MySQL 8.0 server on port 3306
2. Creates the `chamilo` database if not present
3. Generates JWT keys if missing
4. Aligns MySQL timezone to America/Sao_Paulo (-03:00)
5. Builds frontend assets synchronously if not built (first run takes ~3 min; blocks until done before PHP server starts)
6. Starts PHP built-in server on port 5000

## Database Configuration
- Host: 127.0.0.1 (via socket: /home/runner/mysql_run/mysql.sock)
- Database: chamilo
- User: chamilo
- Password: chamilo_pass
- MySQL data dir: /home/runner/mysql_data

## Environment Variables (.env)
- `DATABASE_HOST`, `DATABASE_PORT`, `DATABASE_NAME`, `DATABASE_USER`, `DATABASE_PASSWORD`
- `APP_ENV=dev`, `APP_DEBUG=1`
- `APP_INSTALLED=0` (set to `1` after installation completes)
- `APP_SECRET` - Symfony security secret
- JWT keys in `config/jwt/`

## First-Time Setup
After starting the workflow, visit the app to use the Chamilo installation wizard:
1. Step 1: Language selection
2. Step 2: Requirements check
3. Step 3: License
4. Step 4: Database settings (use the values above)
5. Step 5: Admin configuration
6. Step 6: Installation overview
7. Step 7: Install

## Frontend Assets
Built with Webpack Encore. The first build takes ~3 minutes.
- Dev: `yarn dev`
- Production: `yarn build`
- Watch mode: `yarn watch`

## PHP Runtime Configuration (php -S flags)
The PHP built-in server is started with the following flags in `start.sh`:
- `memory_limit=256M`
- `upload_max_filesize=100M`
- `post_max_size=100M`
- `max_execution_time=300`
- `date.timezone=America/Sao_Paulo`
- `pdo_mysql.default_socket` / `mysqli.default_socket` pointing to the local MySQL socket

## Project Documentation
- `ARCHITECTURE.md` — Full architecture doc: stack, directory structure, extension points, risk zones, infrastructure
- `CUSTOMIZATIONS.md` — Complete inventory of all changes made since initial setup
- `ROADMAP.md` — Production gaps, planned extensions, safe update rules

## Notes
- MySQL 8.0 is installed as a Nix system dependency
- The `public/build/` directory is gitignored (built at runtime)
- Frontend assets must be built before the app works properly
- `public/main/install/` has been removed post-installation (security hardening)
- This project is **Chamilo 2.x** (Symfony 6.4 / PHP 8.2), NOT Chamilo 1.11.x — the architectures are completely different

## Security Hardening (applied post-install)
- **Installer removed**: `public/main/install/` was deleted after the initial installation to prevent the installer from being accessed again.
- **Config file permissions**: `start.sh` applies `chmod 0555` to core Symfony config files (`config/packages/`, `config/routes/`, `config/routes.yaml`, `config/services.yaml`, `config/bundles.php`, `config/preload.php`) at every startup.
- **Why not the full `config/` tree**: `config/jwt/` is intentionally left writable because `start.sh` regenerates JWT keys there on fresh containers. `config/settings_overrides.yaml` and `config/plugin.yaml` are also left writable as they may be updated by platform administrators at runtime.

## Deployment (Cloud Run)
- **Data persistence**: The deployment target is Cloud Run, which has an **ephemeral filesystem**. All MySQL data (including the initialized data directory) is wiped on every redeploy. This is acceptable for demos and development previews.
- For production with persistent data, migrate to an external MySQL or PostgreSQL service and update the `DATABASE_URL` environment variable/secret accordingly.
- `start.sh` automatically initializes the MySQL data directory (`mysqld --initialize-insecure`) on first run, so a fresh container will always be able to start MySQL from scratch.
