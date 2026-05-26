PENS plugin
===========

Overview
--------

This plugin provides a basic PENS receiver for Chamilo 2.

Current behavior:
- receives external PENS `collect` requests,
- downloads the remote package,
- stores the package in `var/plugins/pens`,
- saves package metadata in the `plugin_pens` table,
- sends the corresponding PENS receipt and alert callbacks.

This plugin currently does **not** import the received package into a Chamilo course automatically.

Preferred endpoint in Chamilo 2
-------------------------------

Use the Symfony controller endpoint:

- `POST /plugin/pens/collect`

This is the preferred public entry point for new integrations.

Administrative page
-------------------

When the plugin is enabled, the plugin manager can expose a **Configure** button that opens:

- `plugin/Pens/admin.php`

This page shows the history of received PENS packages stored in the database.

Storage
-------

Received packages are stored under:

- `var/plugins/pens`

Package metadata is stored in:

- `plugin_pens`

Installation in Chamilo 2
-------------------------

The plugin installation flow uses:

- `public/plugin/Pens/install.php`
- `public/plugin/Pens/uninstall.php`

These files call the plugin class installation logic and are required so the plugin can create or remove its database objects properly.

Current internal flow in Chamilo 2
----------------------------------

The current Chamilo 2 implementation does the following:

1. A remote system sends a `collect` request to `/plugin/pens/collect`.
2. Chamilo validates the incoming PENS request.
3. Chamilo downloads the package from `package-url`.
4. Chamilo stores the package in `var/plugins/pens`.
5. Chamilo stores metadata in `plugin_pens`.
6. Chamilo sends the PENS `receipt`.
7. Chamilo sends the PENS `alert`.

This reproduces the core receiver logic of the legacy plugin, adapted to a Symfony controller entry point in Chamilo 2.

What the plugin does not do yet
-------------------------------

At this stage, the plugin does **not**:
- create a course,
- create a learning path,
- import the package automatically into a course,
- expose the package to learners directly.

It currently behaves as a **PENS receiver endpoint** with package storage and metadata tracking.

How to test
-----------

A simple way to test the plugin in Chamilo 2 is to simulate an external PENS publisher.

1. Create a ZIP file that is accessible through HTTP from your Chamilo installation.
2. Send a `collect` request to the PENS endpoint.
3. Check that:
    - the package was stored in `var/plugins/pens`,
    - a new row was added to `plugin_pens`,
    - the package appears in the plugin administrative page (`Configure`).

Example test package
--------------------

Create a small ZIP file in a public test directory:

```bash
mkdir -p public/tests/pens
cd public/tests/pens
printf 'PENS test package\n' > README.txt
zip -r sample.zip README.txt
```

Then verify that the file is reachable:

```bash
curl -I http://<CHAMILO-URL>/tests/pens/sample.zip
```

Example collect request
-----------------------

You can simulate an external PENS publisher with a request like this:

```bash
curl -i -X POST http://<CHAMILO-URL>/plugin/pens/collect \
  -d 'command=collect' \
  -d 'pens-version=1.0.0' \
  -d 'package-type=scorm-pif' \
  -d 'package-type-version=2004' \
  -d 'package-format=zip' \
  -d 'package-id=http://example.com/packages/test-package-001' \
  -d 'package-url=http://<CHAMILO-URL>/tests/pens/sample.zip' \
  -d 'package-url-expiry=2099-12-31T23:59:59Z' \
  -d 'client=local-pens-test' \
  -d 'system-user-id=' \
  -d 'system-password=' \
  -d 'vendor-data=manual test from curl'
```

Expected result
---------------

If the request is accepted, Chamilo should:

- return a successful PENS response,
- download and store the ZIP package in `var/plugins/pens`,
- save the package metadata in `plugin_pens`,
- show the received package in the plugin administrative page.

At this stage, the plugin does not import the package automatically into a course. It only receives, stores and tracks the package.

Requirements
------------

You need the PHP cURL extension enabled on the server.

Notes
-----

- The plugin folder name must remain `Pens`.
- The preferred Chamilo 2 integration style is to expose the receiver through a Symfony controller.
- Legacy entry points inside the plugin folder are no longer required as the main public endpoint.
- The current administrative page is intended to inspect received packages, not to import them into courses yet.

License
-------

The chamilo-pens plugin is published under the GNU/GPLv3+ license (see COPYING).

Credits
-------

The original author of the plugin is Guillaume Viguier-Just <guillaume@viguierjust.com>.

This plugin was realized as part of his work in BeezNest and is maintained by BeezNest.
