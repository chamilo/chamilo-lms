# Learning Path creation fix

## Problem

The Learning Path configuration POST operations disable both reading and deserialization because the processor reads JSON or multipart payloads directly from the request.

With the default API Platform placeholder controller, no `$data` argument is prepared in that combination, so Symfony fails before `LearningPathConfigurationProcessor::process()` runs.

## Change

- Adds a minimal API Platform action that returns an empty `LearningPathConfiguration` DTO.
- Assigns that action to both create and edit configuration POST operations.
- Keeps validation, CSRF, permissions, context checks, persistence and file handling inside the existing Processor.
- Does not change the database, routes, payload contract or Vue code.

## Files

- `src/CoreBundle/ApiResource/LearningPath/LearningPathConfiguration.php`
- `src/CoreBundle/Controller/Api/LearningPathConfigurationAction.php`

## Apply

```bash
cd /var/www/chamilo2 || exit 1
cp -a /path/to/chamilo2-learnpath-batch2-create-fix-final/. .

composer dump-autoload
php bin/console cache:clear
```

No Vue rebuild is required for this backend-only fix. Rebuild only if you also have pending Vue changes:

```bash
rm -rf public/build/*
NODE_OPTIONS="--max-old-space-size=4096 --experimental-global-webcrypto" yarn dev
```

## Validate

```bash
php -l src/CoreBundle/ApiResource/LearningPath/LearningPathConfiguration.php
php -l src/CoreBundle/Controller/Api/LearningPathConfigurationAction.php

php bin/console debug:router | grep -E 'learning_paths.*configuration'
git diff --check
```

Optional project checks:

```bash
vendor/bin/ecs check \
  src/CoreBundle/ApiResource/LearningPath/LearningPathConfiguration.php \
  src/CoreBundle/Controller/Api/LearningPathConfigurationAction.php

vendor/bin/psalm --show-info=false \
  src/CoreBundle/ApiResource/LearningPath/LearningPathConfiguration.php \
  src/CoreBundle/Controller/Api/LearningPathConfigurationAction.php
```

## Functional test

1. Open the Vue Learning Path creation form.
2. Fill the title and any available advanced options.
3. Click **Continue**.
4. Confirm the request no longer fails in `api_platform.action.placeholder::__invoke()`.
5. Confirm the Learning Path is created once and opens the builder while preserving `cid`, `sid`, `gid`, `origin`, `node` and related context.
6. Open Settings for an existing Learning Path and save it to verify the edit POST operation too.
7. Repeat with a course in a valid session.
8. Change `cid`, `sid`, `gid` or `lpId` manually and confirm the backend rejects cross-context access.

## Rollback

```bash
git restore src/CoreBundle/ApiResource/LearningPath/LearningPathConfiguration.php
rm -f src/CoreBundle/Controller/Api/LearningPathConfigurationAction.php
php bin/console cache:clear
```

## Commit title

```text
Learning path: Fix configuration POST handling
```
