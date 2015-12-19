Composer Schema of Asset
========================

### Properties

##### requires

Lists packages required by this package. The package will not be installed unless those requirements
can be met.

##### requires-dev (root-only)

Lists packages required for developing this package, or running tests, etc. The dev requirements
of the root package are installed by default. Both `install` or `update` support the `--no-dev`
option that prevents dev dependencies from being installed.

##### extra.asset-repositories (root-only)

Because the plugin is installed after the analysis of type repositories, the custom types must
be included in a special property in `extra` composer.

Custom package repositories to use.

By default composer just uses the packagist repository. By specifying
repositories you can get packages from elsewhere.

Repositories are not resolved recursively. You only can add them to your
main `composer.json`. Repository declarations of dependencies' composer.jsons are ignored.

The following repository types are supported:

- **npm-vcs**: The version control system repository can fetch packages from git with `package.json`
               file dedicated to NPM. The `url` property of git source code is required.
- **bower-vcs**: The version control system repository can fetch packages from git with `bower.json`
                 file dedicated to Bower. The `url` property of git source code is required.

##### extra.asset-registry-options (root-only)

Options available for the asset registers:

- **npm-searchable** (bool): The search in the NPM registry may be disabled with this option
                             for the search command.
- **bower-searchable** (bool): The search in the Bower registry may be disabled with this option
                               for the search command.

##### extra.asset-main-files (root-only)

The plugin can override the main file definitions of the Bower packages. To override the file
definitions specify the packages and their main file array as name/value pairs. For an example
see the [usage informations](index.md#override-the-main-files-for-bower).

### Mapping asset file to composer package

##### NPM mapping

The `package.json` of asset repository is automatically converted to a Complete Package instance with:

| NPM Package          | Composer Package                      |
|----------------------|---------------------------------------|
| name                 | name (`npm-asset/{name}`)             |
| `npm-asset-library`  | type                                  |
| description          | description                           |
| version              | version                               |
| keywords             | keywords                              |
| homepage             | homepage                              |
| license              | license                               |
| author               | authors [0]                           |
| contributors         | authors [n], merging with `author`    |
| dependencies         | require                               |
| devDependencies      | require-dev                           |
| bin                  | bin                                   |
| bugs                 | extra.npm-asset-bugs                  |
| files                | extra.npm-asset-files                 |
| main                 | extra.npm-asset-main                  |
| man                  | extra.npm-asset-man                   |
| directories          | extra.npm-asset-directories           |
| repository           | extra.npm-asset-repository            |
| scripts              | extra.npm-asset-scripts               |
| config               | extra.npm-asset-config                |
| bundledDependencies  | extra.npm-asset-bundled-dependencies  |
| optionalDependencies | extra.npm-asset-optional-dependencies |
| engines              | extra.npm-asset-engines               |
| engineStrict         | extra.npm-asset-engine-strict         |
| os                   | extra.npm-asset-os                    |
| cpu                  | extra.npm-asset-cpu                   |
| preferGlobal         | extra.npm-asset-prefer-global         |
| private              | extra.npm-asset-private               |
| publishConfig        | extra.npm-asset-publish-config        |
| `not used`           | time                                  |
| `not used`           | support                               |
| `not used`           | conflict                              |
| `not used`           | replace                               |
| `not used`           | provide                               |
| `not used`           | suggest                               |
| `not used`           | autoload                              |
| `not used`           | autoload-dev                          |
| `not used`           | include-path                          |
| `not used`           | target-dir                            |
| `not used`           | extra                                 |
| `not used`           | archive                               |

##### Bower mapping

The `bower.json` of asset repository is automatically converted to a Complete Package instance with:

| Bower Package        | Composer Package                      |
|----------------------|---------------------------------------|
| name                 | name (`bower-asset/{name}`)           |
| `bower-asset-library`| type                                  |
| description          | description                           |
| version              | version                               |
| keywords             | keywords                              |
| license              | license                               |
| dependencies         | require                               |
| devDependencies      | require-dev                           |
| bin                  | bin                                   |
| main                 | extra.bower-asset-main                |
| ignore               | extra.bower-asset-ignore              |
| private              | extra.bower-asset-private             |
| `not used`           | homepage                              |
| `not used`           | time                                  |
| `not used`           | authors                               |
| `not used`           | support                               |
| `not used`           | conflict                              |
| `not used`           | replace                               |
| `not used`           | provide                               |
| `not used`           | suggest                               |
| `not used`           | autoload                              |
| `not used`           | autoload-dev                          |
| `not used`           | include-path                          |
| `not used`           | target-dir                            |
| `not used`           | extra                                 |
| `not used`           | archive                               |

##### Verison conversion

NPM and Bower use [Semver](http://semver.org) for formatting the versions, which is not
the case for Composer. It is therefore necessary to perform a conversion, but it's not
perfect because of the differences in operation between Semver and Composer.

Here are the matches currently validated:

| Semver version   | Composer version |
| ---------------- | ---------------- |
| 1.2.3            | 1.2.3            |
| 1.2.3alpha       | 1.2.3-alpha1     |
| 1.2.3-alpha      | 1.2.3-alpha1     |
| 1.2.3a           | 1.2.3-alpha1     |
| 1.2.3a1          | 1.2.3-alpha1     |
| 1.2.3-a          | 1.2.3-alpha1     |
| 1.2.3-a1         | 1.2.3-alpha1     |
| 1.2.3b           | 1.2.3-beta1      |
| 1.2.3b1          | 1.2.3-beta1      |
| 1.2.3-b          | 1.2.3-beta1      |
| 1.2.3-b1         | 1.2.3-beta1      |
| 1.2.3beta        | 1.2.3-beta1      |
| 1.2.3-beta       | 1.2.3-beta1      |
| 1.2.3beta1       | 1.2.3-beta1      |
| 1.2.3-beta1      | 1.2.3-beta1      |
| 1.2.3rc1         | 1.2.3-RC1        |
| 1.2.3-rc1        | 1.2.3-RC1        |
| 1.2.3rc2         | 1.2.3-RC2        |
| 1.2.3-rc2        | 1.2.3-RC2        |
| 1.2.3rc.2        | 1.2.3-RC.2       |
| 1.2.3-rc.2       | 1.2.3-RC.2       |
| 1.2.3+0          | 1.2.3-patch0     |
| 1.2.3-0          | 1.2.3-patch0     |
| 1.2.3pre         | 1.2.3-beta1      |
| 1.2.3-pre        | 1.2.3-beta1      |
| 1.2.3dev         | 1.2.3-dev        |
| 1.2.3-dev        | 1.2.3-dev        |
| 1.2.3+build2012  | 1.2.3-patch2012  |
| 1.2.3-build2012  | 1.2.3-patch2012  |
| 1.2.3+build.2012 | 1.2.3-patch.2012 |
| 1.2.3-build.2012 | 1.2.3-patch.2012 |
| latest           | default          |

##### Range verison conversion

NPM and Bower use [Semver](http://semver.org) for formatting the range versions, which is not
the case for Composer. It is therefore necessary to perform a conversion, but it's not
perfect because of the differences in operation between Semver and Composer.

Here are the matches currently validated:

| Semver range version | Composer range version |
| -------------------- | ---------------------- |
| >1.2.3               | >1.2.3                 |
| > 1.2.3              | >1.2.3                 |
| <1.2.3               | <1.2.3                 |
| < 1.2.3              | <1.2.3                 |
| >=1.2.3              | >=1.2.3                |
| >= 1.2.3             | >=1.2.3                |
| <=1.2.3              | <=1.2.3                |
| <= 1.2.3             | <=1.2.3                |
| ~1.2.3               | ~1.2.3                 |
| ~ 1.2.3              | ~1.2.3                 |
| ~1                   | ~1                     |
| ~ 1                  | ~1.2.3                 |
| ^1.2.3               | >=1.2.3,<2.0           |
| ^ 1.2.3              | >=1.2.3,<2.0           |
| >1.2.3 <2.0          | >1.2.3,<2.0            |
| &gt;=1.0 &lt;1.1 `¦¦` &gt;=1.2 | &gt;=1.0,&lt;1.1`¦`&gt;=1.2 |
| 1.2.3 - 2.3.4        | >=1.2.3,<=2.3.4        |


##### URL Range verison conversion

NPM and Bower can use a URL directly as the version of the dependency, which is not the
case for Composer. It is therefore necessary to perform a conversion, but it's not perfect
because of the differences in operation between NPM/Bower and Composer.

| Asset URL version | Composer version                                    |
| ----------------- | --------------------------------------------------- |
| {URL}             | dev-default                                         |
| {URL}#1.2.3       | dev-1.2.3 <code>&#124;</code> 1.2.3 (branch or tag) |
| {URL}#{branch}    | dev-{branch}                                        |
| {URL}#{sha}       | dev-default#{sha}                                   |

##### Multiple versions of a depdendency in the same project

NPM and Bower can add multiple versions of the same dependency, which is not the case for Composer.
To overcome this limitation, the plugin adds a VCS repository for each required version, with the
name including the version number after the character `-`
(`{ASSET-TYPE}-asset/{PACKAGE-NAME}-X.Y.Z`).

A Vcs repository will be created for each version, and the number of requests is proportional to
the number of versions required. However, given that each version of the dependency uses the same
URL of the VCS repository, subsequent requests will get the package information directly in the cache.
However, a cache of files is created for each version (included in require section) of a same dependency.

## Asset Repository

The plugin creates `Composer Repositories` to find and create
the VCS repository of the asset defined in the `require` and `require-dev` automatically.

### NPM Composer Repository

[NPM Package](https://www.npmjs.org) is the main NPM repository. A NPM Composer repository
is basically a package source, i.e. a place where you can get packages from. NPM Package aims to
be the central repository that everybody uses. This means that you can automatically `require`
any package that is available there.

If you go to the [NPM website](https://www.npmjs.org), you can browse and search for packages.

All package names are automatically prefixed with `npm-asset/`.

### Bower Composer Repository

[Bower Package](http://bower.io) is the main Bower repository. A Bower Composer repository
is basically a package source, i.e. a place where you can get packages from. Bower Package aims to
be the central repository that everybody uses. This means that you can automatically `require`
any package that is available there.

If you go to the [Bower website](http://bower.io/search/), you can browse and search for packages.

All package names are automatically prefixed with `bower-asset/`.
