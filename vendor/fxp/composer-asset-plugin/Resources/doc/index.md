Basic Usage
===========

1. [Installation](index.md)
2. [Composer Schema of Asset](schema.md)
3. [FAQs](faqs.md)

## Installation

See the [Release Notes](https://github.com/francoispluchino/composer-asset-plugin/releases)
to know the Composer version required.

### Global scope (per user) installation

```shell
$ composer global require "fxp/composer-asset-plugin:~1.0"
```

### Project scope installation

```shell
$ composer require "fxp/composer-asset-plugin:~1.0"
```

## Usage

### Usage with asset repository

Adding a dependency on an asset, you must add the asset to the property
`require` of the `composer.json` of your project.

It must be prefixed with `{asset-type}-asset/`.

**Example for twitter bootstrap:**

```json
{
    "require": {
        "npm-asset/bootstrap": "dev-master"
    }
}
```

**or:**

```json
{
    "require": {
        "bower-asset/bootstrap": "dev-master"
    }
}
```

### Usage with VCS repository

If your asset is not listed on the NPM- or Bower-Repository, or it is a private package, you can
create a VCS repository for it. The repository must have an asset package file for NPM (`package.json`)
or Bower (`bower.json`).

**Example:**

Add the following to your `composer.json`:

```json
{
    "extra": {
        "asset-repositories": [
            {
                "type": "bower-vcs",
                "url": "https://github.com/vendor/exemple-asset-name.git"
            }
        ]
    }
}
```

**Availables drivers:**

| Drivers             | NPM                 | Bower                 |
|---------------------|---------------------|-----------------------|
| **auto**            | `npm-vcs`           | `bower-vcs`           |
| Git                 | `npm-git`           | `bower-git`           |
| GitHub              | `npm-github`        | `bower-github`        |
| Git Bitbucket       | `npm-git-bitbucket` | `bower-git-bitbucket` |
| Mercurial           | `npm-hg`            | `bower-hg`            |
| Mercurial Bitbucket | `npm-hg-bitbucket`  | `bower-hg-bitbucket`  |
| SVN                 | `npm-svn`           | `bower-svn`           |
| Perforce            | `npm-perforce`      | `bower-perforce`      |

### Overriding the config of a VCS Repository

If you must use a repository other than that indicated by the registry of NPM or Bower,
you must specify the name of the package with the asset prefix in the config of the VCS
Repository.

**Example:**

```json
{
    "extra": {
        "asset-repositories": [
            {
                "type": "bower-vcs",
                "url": "https://github.com/vendor/exemple-asset-name.git",
                "name": "bower-asset/exemple-asset-name"
            }
        ]
    }
}
```

You can also use the standard format of Composer for naming your VCS Repository:

```json
{
    "extra": {
        "asset-repositories": {
            "bower-asset/exemple-asset-name": {
                "type": "bower-vcs",
                "url": "https://github.com/vendor/exemple-asset-name.git"
            }
        }
    }
}
```

### Usage with multiple versions of the same dependency

If you need to use multiple versions of the same asset, you can do this by
simply adding a version number after the package name, separated with the "-"
character.

**Example with Jquery:**

```json
{
    "require": {
        "bower-asset/jquery": "1.11.*",
        "bower-asset/jquery-2.0.x": "2.0.x",
        "bower-asset/jquery-2.1.0": "2.1.0"
    }
}
```

The dependencies will then be placed in the following directories:

- `vendor/bower-asset/jquery` for `1.11.*`
- `vendor/bower-asset/jquery-2.0.x` for `2.0.x`
- `vendor/bower-asset/jquery-2.1.0` for `2.1.0`

### Reduce the number of requests for getting the package definitions

The root Composer package has a feature: all asset dependencies added will have automatically
a filter applied, before the importation of the branches and the tags.

In this way, all versions are not accepted by the constraint of version and they will be
skipped to the importation, and will not be injected in the `Pool`. Of course, all constraints
of versions are functional (exact version, range, wildcard, tilde operator).

**For example:**

The root `composer.json`:

```json
{
    "minimum-stability": "dev",
    "require": {
        "npm-asset/example-asset1": ">=1.0@stable",
        "npm-asset/example-asset2": ">=2.3@RC",
        "npm-asset/example-asset3": ">=1.3@beta",
        "npm-asset/example-asset4": "~0.9@alpha",
        "npm-asset/example-asset4": "2.1.*",
    }
}
```

In case you have an dependency that that requires a sub asset dependency, and given that this
optimization cannot be performed with the sub dependencies, you can add this asset dependency
directly to the root Composer package, in the same way that if you wanted to use a
well-defined version of this dependency.

### Disable the import filter using the installed packages

By default, and for dramatically optimize performance for the `update`, the plugin filters the
imports of definitions packages. In addition to filter with the dependencies in the root
Composer package, the plugin filters the imports of packages definitions with the previous
versions of the packages installed.

However it may happen that Composer throws an exception, indicating that it can not find a
compatible version. This happens if a dependency uses a new version lower than the installed
version.

Of course, several solutions can work around the problem (see the [FAQs]
(faqs.md#composer-throws-an-exception-stating-that-the-version-does-not-exist)), but the
solution below may be used in another use case.

You can disable the import filter using the versions of installed packages with the option
`extra.asset-optimize-with-installed-packages` in the root Composer package:

```json
{
    "extra": {
        "asset-optimize-with-installed-packages": false
    }
}
```

#### Change/Disable the skip of versions by pattern

By default, the plugin does not import the `patch` versions for increase dramatically
performance. However, it is possible to change the pattern or to disable this feature.

**Example for change the pattern:**

```json
{
    "extra": {
        "asset-pattern-skip-version": "(-build)"
    }
}
```

**Example for disable the pattern:**

```json
{
    "extra": {
        "asset-pattern-skip-version": false
    }
}
```

#### Disable the conjunctive option of the import filter

You can disable the `conjunctive` mode of the import filter with the option
`extra.asset-optimize-with-conjunctive` in the root Composer package:

```json
{
    "extra": {
        "asset-optimize-with-conjunctive": false
    }
}
```

> **Note:**
>
> This option is used only if the optimization with the installed packages is enabled

### Define a custom directory for the assets installation

By default, the plugin will install all the assets in the directory
`vendors/{asset-type}-asset` and packages will be installed in each folder with
their asset name.

But you can change the installation directory of the assets directly in the root
`composer.json`-file of your project:

```json
{
    "extra": {
        "asset-installer-paths": {
            "npm-asset-library": "web/assets/vendor",
            "bower-asset-library": "web/assets/vendor"
        }
    }
}
```

> **Note:**
>
> For Bower, all files defined in the section `ignore` will not be installed

### Disable or replace the deleting of the ignore files for Bower

For Bower, all files defined in the section `ignore` will be delete just after the
installation of each package. Of course, this behavior can be disabled or replaced.

**Example for disable the list of ignored files:**

```json
{
    "extra": {
        "asset-ignore-files": {
            "bower-asset/example-asset1": false
        }
    }
}
```

**Example for replace the list of ignored files:**

```json
{
    "extra": {
        "asset-ignore-files": {
            "bower-asset/example-asset1": [
                ".*",
                "*.md",
                "test"
            ]
        }
    }
}
```

### Enable manually the deleting of the ignore files for NPM

For NPM, there is no section `ignore`, but you can manually add the patterns for
delete the files:

```json
{
    "extra": {
        "asset-ignore-files": {
            "npm-asset/example-asset1": [
                ".*",
                "*.md",
                "test"
            ]
        }
    }
}
```

### Use the Ignore Files Manager in the Composer scripts

Sometimes you need to clean a package that is not considered an NPM/Bower Asset
Package. To do this, you can use the script helper
`Fxp\Composer\AssetPlugin\Composer\ScriptHandler::deleteIgnoredFiles` for the
`post-package-install` or `post-package-update` script events.

**Example:**

```json
{
    "scripts": {
        "post-package-install": [
            "Fxp\\Composer\\AssetPlugin\\Composer\\ScriptHandler::deleteIgnoredFiles"
        ],
        "post-package-update": [
            "Fxp\\Composer\\AssetPlugin\\Composer\\ScriptHandler::deleteIgnoredFiles"
        ]
    },
    "extra": {
        "asset-ignore-files": {
            "acme/other-asset": [
                ".*",
                "*.md",
                "test"
            ]
        }
    }
}
```

### Disable the search for an asset registry

If you want to disable the search for an asset registry, you can add an extra
option `extra.asset-registry-options.{type}-searchable` in the root project
`composer.json`-file.

**Example:**

```json
{
    "extra": {
        "asset-registry-options": {
            "npm-searchable": false,
            "bower-searchable": false
        }
    }
}
```
