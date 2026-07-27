# How to run Chamilo 2.0 on your computer

This guide gets Chamilo running on any computer using Docker.

You do **not** need to install PHP, MySQL, Node.js, or Apache. Docker handles
all of it. You only install Docker.

Works on **macOS**, **Windows**, and **Linux**. Works on both Intel and Apple
Silicon (M1/M2/M3/M4) chips.

---

## Step 1 — Install Docker

Pick your system:

| System | What to install |
|---|---|
| macOS | [Docker Desktop for Mac](https://docs.docker.com/desktop/install/mac-install/) |
| Windows | [Docker Desktop for Windows](https://docs.docker.com/desktop/install/windows-install/) |
| Linux | [Docker Engine](https://docs.docker.com/engine/install/) + [Compose plugin](https://docs.docker.com/compose/install/linux/) |

**Windows users:** run all the commands in this guide from **Git Bash** or
**WSL2**, not from PowerShell. The scripts are bash scripts.

Then start Docker Desktop and check it works:

```bash
docker --version
docker compose version
```

If both print a version number, you're good.

**You also need about 10 GB of free disk space.** Docker downloads several
images and the project installs its own dependencies.

---

## Step 2 — Get the code

```bash
git clone https://github.com/chamilo/chamilo-lms.git chamilo
cd chamilo
git checkout 2.0
```

Already have the folder? Just `cd` into it.

---

## Step 3 — Run one command

```bash
docker/setup.sh
```

Now wait. **The first run takes a long time — plan for 30 to 60 minutes.**
It is downloading and building a lot. This is normal.

Here's roughly what it's doing, so you know it isn't stuck:

| Stage | Roughly how long |
|---|---|
| Building the PHP image | 3–5 min |
| Downloading MariaDB, Redis, Mailpit, Selenium | 5–40 min (depends on your internet) |
| Installing PHP libraries (`composer install`) | 5–10 min |
| Building the frontend (`yarn` + webpack) | 10–20 min |
| Installing Chamilo itself | 1–2 min |
| Setting up the test database | 1–2 min |

When it finishes, it prints a summary with all your addresses.

> The Selenium download is the slowest part and it is only needed for browser
> tests. If it feels stuck, it's almost certainly still downloading.

---

## Step 4 — Open it

Go to:

```
http://localhost:8380
```

Log in:

- **Username:** `admin`
- **Password:** `admin`

That's it. You have a working Chamilo.

---

## Your addresses

| What it is | Address |
|---|---|
| Chamilo | http://localhost:8380 |
| API documentation | http://localhost:8380/api |
| Emails the app sends | http://localhost:8325 |
| Watch browser tests live | http://localhost:7900 |
| Database | `localhost:3307` — user `chamilo`, password `chamilo` |

Chamilo never really sends email here. Everything it tries to send is caught by
**Mailpit** at http://localhost:8325 so you can read it. Handy for testing
"forgot my password" and sign-up emails.

---

## Everyday commands

Run all of these from the project folder.

```bash
docker/setup.sh          # start everything (safe to run any time)
docker compose down      # stop everything (your data is kept)
docker compose ps        # see what's running
docker compose logs -f web   # watch the error log
```

To get back to work after stopping, just run `docker/setup.sh` again. It will be
fast the second time — it skips everything already done.

Need a terminal inside the container?

```bash
docker compose exec web bash
```

Need the Symfony console?

```bash
docker compose exec web php bin/console cache:clear
docker compose exec web php bin/console debug:router
```

---

## Running the tests

```bash
docker/test.sh phpunit     # code tests, about 4 minutes
docker/test.sh behat       # browser tests, several HOURS
docker/test.sh phpstan     # finds possible bugs
docker/test.sh ecs         # checks code style
docker/test.sh psalm       # another bug finder
```

### Read this before you panic

**Some tests already fail. That is not your fault and not a broken setup.**

Chamilo 2.0 is unfinished — its own README says it's "in its validation phase".
These are the numbers you should expect on a clean install:

| Test | Expected result |
|---|---|
| PHPUnit | **299 pass, 54 fail** (out of 353) |
| PHPStan | **603 problems found** |
| ECS | **12 style problems** |
| Behat | some features fail |

**How to use these numbers:** run the tests *before* you change anything and
write down what you get. That's your baseline. If you change code and the number
of failures goes *up*, you broke something. If it stays the same, you're fine.

Most of the PHPUnit failures come from one single cause — a missing
`cascade: ['persist']` setting on a database relationship. One small fix would
clear about 24 of the 54.

Browser tests are slow because a real Chrome browser clicks through the site.
Roughly **50 seconds per scenario**. You usually want to run just one file:

```bash
docker/test.sh behat features/actionUserLogin.feature
```

You can watch it happen live at http://localhost:7900.

---

## If a port is already taken

This is the most common problem on a new computer. You'll see:

```
Bind for 0.0.0.0:8380 failed: port is already allocated
```

It means another program is already using that port. Find out who:

```bash
docker ps --format '{{.Names}} {{.Ports}}'
```

To change the Chamilo port, use the script — don't edit files by hand. The port
appears in four files that must all agree, and the script keeps them in step:

```bash
docker/set-port.sh            # show the current port
docker/set-port.sh 9000       # change it to 9000
```

It refuses if the port is already used by another container, and tells you which
one. Then apply the change:

```bash
docker compose down
docker/setup.sh --fresh
```

`--fresh` is required here, not optional: Chamilo saves its own web address in
the database when it installs, so the old port would linger in every link.

The other ports are easier — change only `docker-compose.yml`:

| Port | Line to change | Used for |
|---|---|---|
| 8325 | `- "8325:8025"` | Mailpit |
| 3307 | `- "3307:3306"` | Database |
| 4444 / 7900 | under `web` | Selenium |

---

## Common problems

**"Docker is not running"**
Start Docker Desktop and wait for its icon to stop animating.

**The page has no styling, or looks broken**
The frontend wasn't built. Run:
```bash
docker/setup.sh --assets
```

**`Environment variable not found: "APP_LOCALE"`**
There's an empty `.env` file. Delete it and run setup again:
```bash
rm .env && docker/setup.sh
```
(Never create an empty `.env`. Chamilo reads its defaults from `.env.dist`, and
it only does that when `.env` does not exist at all.)

**Browser tests say `ERR_CONNECTION_REFUSED`**
Selenium lost its network link. Restart it:
```bash
docker compose --profile behat up -d --force-recreate selenium
```

**Browser tests hang forever**
The Selenium browser died. Same fix as above.

**Everything is broken and I want to start over**
```bash
docker/setup.sh --fresh
```
This deletes the database and reinstalls Chamilo from scratch. Takes a few
minutes. Your code is not touched.

**I want to delete absolutely everything, including Docker images**
```bash
docker compose down -v
```
Warning: this deletes your database. The next `docker/setup.sh` starts over.

---

## What's your login again?

| | |
|---|---|
| Chamilo admin | `admin` / `admin` |
| Database user | `chamilo` / `chamilo` |
| Database root | `root` / `root` |
| Database name | `chamilo` |

These are throwaway passwords for local development only. **Never use this
setup on a real server.** It runs in debug mode with weak passwords.

---

## Want the technical details?

This guide covers getting it running. If you need to understand *why* the setup
is built the way it is — why Apache uses port 8380 inside the container, why
Selenium is pinned to an old version, how the environment files layer — read:

**[docker/README.md](docker/README.md)**

Every unusual choice in there is explained, because each one was a real bug that
had to be tracked down.
