# Survey Export TXT

Exports survey results to a TXT file.

This plugin adds a TXT export action to the survey list for teachers when the
plugin is installed and enabled.

## Setup

- Install the plugin.
- Enable it in the plugin configuration.
- No manual `configuration.php` edit is required in Chamilo 2: the survey list
  now auto-detects this official export plugin and adds the action only when the
  plugin is enabled.


## Activation

Plugin activation is controlled from the Chamilo plugins list. The configuration form only controls export-specific options.

## Current behavior

The export output is readable for teachers. Non-anonymous surveys include user identity columns. Anonymous surveys intentionally anonymize user identity.
