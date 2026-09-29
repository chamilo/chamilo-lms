# Chamilo 2.x tests directory

This directory is being used for all kinds of tests and scripts and is removed from
public releases as it may represent a risk for production systems.

## Playwright

Browser-driven tests live in `playwright/`. They use
[playwright-bdd](https://vitalets.github.io/playwright-bdd/), so the scenarios stay
plain Gherkin (`playwright/features/*.feature`) and only the step definitions are
TypeScript (`playwright/steps/common.steps.ts`). The base URL and browser options are
in `playwright/playwright.config.ts`.

Before the first run, create the fixtures most scenarios assume exist, in this order:
```
yarn test:playwright:seed                 # the fixed test users
yarn test:playwright:seed-course          # the TEMP course
yarn test:playwright:seed-private-course  # the TEMPPRIVATE course
yarn test:playwright:seed-settings        # settings some scenarios need enabled
```

Then run the suite (the seeds and the installer scenario are excluded from it):
```
yarn test:playwright                                              # everything
yarn test:playwright tests/playwright/features/toolForum.feature  # a single file
yarn test:playwright:ui                                           # interactive runner
```

`yarn test:playwright:install` covers the web installer itself. It recreates the
database, so it is CI-only — never run it against an installation you care about.

After editing a `.feature` file or anything under `playwright/steps/`, regenerate the
compiled specs before trusting a run:
```
node_modules/.bin/bddgen --config=tests/playwright/playwright.config.ts
```

The old Behat suite has been removed. Its scenarios remain in git history and are worth
consulting when adding coverage for an area it once tested:

```
git ls-tree -r --name-only 98c77757ea6 tests/behat        # the 84 files it had
git show 98c77757ea6:tests/behat/features/<name>.feature  # read one
```

Treat those as a hint of which flows were considered worth testing — never as a source of
truth for selectors, which have rotted since. Verify against the live app.

## PHPUnit

We use the default Symfony PHPUnit settings:

https://symfony.com/doc/current/testing.html

### Setup a test database

Create a new env file called **.env.test.local** with your MySQL credentials for your new test database.

```
DATABASE_HOST='127.0.0.1'
DATABASE_PORT='3306'
DATABASE_NAME='chamilo_test'
DATABASE_USER='root'
DATABASE_PASSWORD='root'
```

After creating the .env.test.local file execute:

```
php bin/console --env=test cache:clear
php bin/console --env=test doctrine:database:create
php bin/console --env=test doctrine:schema:create
php bin/console --env=test doctrine:fixtures:load --no-interaction
```

If there are DB changes you can migrate your test installation with:

`php bin/console --env=test doctrine:schema:update --force --complete`

Those commands will install Chamilo in the chamilo_test database.

In order to delete the test database and restart the process use:

`php bin/console --env=test doctrine:database:drop --force --complete`

### Use
Execute the tests with:

`php bin/phpunit`


## Folders

Although many scripts here are deprecated, the current structure can be
 described as follows

### datafiller

Set of scripts to fill your test installation of Chamilo with demo content.

### history

Attempt at keeping a track of what Chamilo looked like over time.

### migrations

Combination of unofficial scripts to execute migrations from other systems

### procedures

xls spreadsheets to be used as base for manual quality review of features in
Chamilo.

### scripts

A collection of scripts used to fix or improve some things globally in Chamilo
portals. Mostly for old versions.
