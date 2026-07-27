# Chamilo LMS 2.0 — Docker development & test stack

Everything runs in containers; nothing but Docker is needed on the host.

## Quick start

```bash
docker/setup.sh          # build, start, install the portal, prepare the test DB
```

Then open <http://localhost:8380> and log in as **admin / admin**.

```bash
docker/setup.sh --fresh    # wipe the DB + .env and reinstall from scratch
docker/setup.sh --assets   # also run yarn install + encore production
```

## What's in the stack

| Service   | Image                              | Host address            | Purpose |
|-----------|------------------------------------|-------------------------|---------|
| `web`     | built from `docker/php/Dockerfile` | <http://localhost:8380> | PHP 8.2 + Apache; also the CLI container for console/phpunit/behat |
| `db`      | `mariadb:10.11`                    | `127.0.0.1:3307`        | `chamilo` and `chamilo_test` schemas |
| `redis`   | `redis:7-alpine`                   | —                       | Symfony lock store (`LOCK_DSN`) |
| `mailpit` | `axllent/mailpit`                  | <http://localhost:8325> | Catches all outgoing mail |
| `selenium`| `seleniarm/standalone-chromium:4.1.4` | <http://localhost:7900> | Behat's browser; `behat` compose profile |

All images are arm64-native, so this runs without emulation on Apple Silicon.

## Running tests

```bash
docker/test.sh phpunit                              # resets chamilo_test, then runs
docker/test.sh phpunit --no-reset --filter UserTest
docker/test.sh behat                                # the full CI feature list
docker/test.sh behat features/actionUserLogin.feature
docker/test.sh phpstan
docker/test.sh psalm
docker/test.sh ecs
docker/test.sh all                                  # phpunit + phpstan + ecs
```

Watch the browser tests run live at <http://localhost:7900> (no password).

Behat is slow — roughly 50s per scenario through a real browser, so
`course.feature` (30 scenarios) takes ~25 minutes on its own and the full CI list
is a multi-hour run. Each feature gets a fresh grid and a 40-minute cap;
override with `BEHAT_TIMEOUT=<seconds>`.

Coverage uses pcov, which is installed but left off (loading it slows every CLI
call). Enable it per-command:

```bash
docker compose exec web php -d extension=pcov.so -d pcov.enabled=1 \
    bin/phpunit --coverage-text
```

## Everyday commands

```bash
docker compose exec web bash                        # shell
docker compose exec web php bin/console cache:clear
docker compose exec web php bin/console debug:router
docker compose exec db mariadb -uchamilo -pchamilo chamilo
docker compose logs -f web
docker compose down                                 # stop
docker compose down -v                              # stop and delete all data
```

## Design notes

Five things here are less obvious than they look.

**Apache listens on 8380 inside the container, not 80.** Chamilo persists
`root_web` in the database at install time, and every redirect is built from it.
Publishing `8380:8380` rather than `8380:80` means the URL is
`http://localhost:8380` from the host browser, from Behat, and from the Chrome
container alike — no mismatch, no broken redirects in the browser tests.

The consequence is that the port appears in four files which must agree:
`docker/php/Dockerfile` (`ARG HTTP_PORT`), `docker-compose.yml` (`ports:`),
`docker/lib.sh` (`CHAMILO_PORT`) and `tests/behat/behat.docker.yml` (`base_url`).
Use `docker/set-port.sh <port>` rather than editing them by hand.

**The `selenium` container uses `network_mode: service:web`.** Sharing the web
container's network namespace means Chrome resolves `http://localhost:8380` to
Apache, while Behat — running inside `web` — reaches the grid at
`127.0.0.1:4444`, which is exactly what `tests/behat/behat.yml` already
specifies. Selenium's ports are published through `web`'s port mappings.

**Behat runs against `tests/behat/behat.docker.yml`, not `BEHAT_PARAMS`.** Only
`base_url` differs from the upstream `behat.yml` (it needs the `:8380` port), but
the override cannot be done with the `BEHAT_PARAMS` environment variable:
Behat 3.29's `ConfigurationLoader::loadConfiguration()` appends the *file* config
after the env-var config, and later entries win the merge — so `behat.yml`'s
`base_url: http://localhost` silently beat it and Chrome reported
`ERR_CONNECTION_REFUSED` against port 80. The extra config sits next to
`behat.yml` so `%paths.base%` still resolves for the suite path and for
`FeatureContext` autoloading.

**`setup.sh` rewrites `access_url` after installing.** The wizard stores
`http://localhost/` — without the port — and Chamilo builds absolute URLs
(assets, mail links, learning-path and document paths) from that row, so the
portal would otherwise emit links to port 80.

**The DB wait-loop in `entrypoint.sh` uses `WAIT_DB_*`, not `DATABASE_*`.** Real
environment variables take precedence over `.env` files in Symfony's Dotenv, so
exporting `DATABASE_NAME` into the container would override `.env.test`'s
`chamilo_test` and point the PHPUnit suite at the live database.

### Environment files

Symfony loads `.env` → `.env.local` → `.env.$APP_ENV` → `.env.$APP_ENV.local`,
and **skips `.env.local` when `APP_ENV=test`**. That gives clean separation:

- `.env` — generated by the install wizard. Do not hand-edit.
- `.env.local` — stack overrides for dev/prod (DB host `db`, Redis lock, `APP_ENV=dev`).
- `.env.test.local` — points PHPUnit at `chamilo_test` on the `db` service.

Both `.local` files are gitignored.

> **Never create an empty `.env`.** Symfony's Dotenv falls back to `.env.dist`
> only when `.env` is *absent*; an empty file shadows that fallback and every
> console command then dies with `Environment variable not found: "APP_LOCALE"`.
> The wizard writes the real `.env`; before that, `.env.dist` is what supplies
> the defaults. `setup.sh --fresh` therefore removes `.env` rather than
> truncating it.

### The test database is `chamilo_test_test`

`config/packages/doctrine.yaml` appends `dbname_suffix: '_test'` under
`when@test`, so `DATABASE_NAME=chamilo_test` resolves to the physical database
`chamilo_test_test`. Setting `DATABASE_NAME=chamilo` would give a tidier
`chamilo_test`, but if that suffix ever stopped being applied,
`doctrine:schema:drop --full-database` would hit the live portal. The extra
`_test` is the safer trade. `docker/db/init/01-databases.sql` grants the
`chamilo` user a `chamilo\_%` wildcard so ParaTest's per-worker databases work
too.

> Note: `docker compose` also reads `./.env` for its own variable substitution.
> The compose file deliberately contains no `${...}` references, so Chamilo's
> `.env` cannot affect it.

### Volumes

`node_modules` and `var/cache` are named volumes rather than part of the bind
mount. `node_modules` holds linux/arm64 native binaries (sass, esbuild) that
would collide with a host `yarn install` built for darwin/arm64; `var/cache`
avoids thousands of small-file writes crossing the virtiofs boundary.

`public/build/` **is** bind-mounted, so compiled frontend assets are shared with
the host. `setup.sh` skips the webpack build when `public/build/entrypoints.json`
already exists — pass `--assets` to force a rebuild.

### Mail

`config/packages/mailer.yaml` hardcodes `null://null`, including in its
`when@dev` block. `config/packages/dev/zz_docker_mailer.yaml` overrides it to
point at Mailpit (files under `config/packages/dev/` load after
`config/packages/*`, and the `zz_` prefix sorts it last).

### Switching to prod

CI runs Behat against `APP_ENV=prod` because the dev toolbar injects markup that
can confuse selector-based assertions. If a browser test behaves oddly, try:

```bash
sed -i '' "s/APP_ENV='dev'/APP_ENV='prod'/; s/APP_DEBUG='1'/APP_DEBUG='0'/" .env.local
docker compose exec web php bin/console cache:clear
```

## Troubleshooting

**`Environment variable not found: "APP_LOCALE"` / `"TRUSTED_PROXIES"`**
An empty `.env` exists. Delete it — see the warning under *Environment files*.

**`Bind for 0.0.0.0:XXXX failed: port is already allocated`**
Another stack on this machine owns the port. `docker ps --format '{{.Names}}
{{.Ports}}'` shows who. The stack already avoids the common ones (8080, 8025,
3306) for that reason; change the `ports:` entry in `docker-compose.yml`, and if
it is the Chamilo port, change `HTTP_PORT` in `docker/php/Dockerfile` to match.

**`No nodes support the capabilities in the request: []`**
The Selenium image drifted to a release that no longer speaks the legacy JSON
Wire protocol. Keep the pin — see the comment above the `selenium` service.

**Behat: `net::ERR_CONNECTION_REFUSED`**
Behat is using a `base_url` without the `:8380` port. Check that the run passes
`--config behat.docker.yml`; a plain `behat` picks up `behat.yml` and its
port-80 URL. Confirm the port is reachable from the browser's namespace with
`docker compose exec selenium curl -sI http://localhost:8380/`.

**Behat can't reach the site after recreating `web`**
`selenium` shares the web container's network namespace, so recreating `web`
detaches it. Bring it back with
`docker compose --profile behat up -d --force-recreate selenium`.

**`Access denied for user 'chamilo'@'%' to database 'chamilo_test_test'`**
The DB volume predates the current `docker/db/init/01-databases.sql`. Init
scripts only run on a fresh volume; replay it by hand with
`docker compose exec -T db mariadb -uroot -proot < docker/db/init/01-databases.sql`.

## Installing the portal

Chamilo 2.0 has **no CLI installer** — the wizard at `/main/install/index.php`
is the only supported path. `setup.sh` drives it with a Docker-specific variant
of the project's own install feature (`docker/behat/installDocker.feature`),
copied into `tests/behat/features/` for the run and removed afterwards so a
full-suite `behat` run cannot reinstall the portal mid-suite.

To install by hand instead, browse to <http://localhost:8380> and use:

| Field    | Value     |
|----------|-----------|
| Host     | `db`      |
| Port     | `3306`    |
| Database | `chamilo` |
| User     | `chamilo` |
| Password | `chamilo` |
