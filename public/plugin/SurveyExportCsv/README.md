# Survey Export CSV

Exports survey results to a CSV file with a very specific format.

This plugin adds a CSV export action to the survey list for teachers when the
plugin is installed and enabled.

The CSV format looks this way:

```
DATID;P01;P02;P03;P04;P05;P06;P07;P08;DATOBS
1;"1";"2";"26";"10";"2";"2";"2";"4";"2"
2;"1";"2";"32";"10";"6";"4";"4";"5";"2"
3;"2";"3";"27";"8";"5";"5";"2";"5";"1"
4;"1";"3";"33";"11";"1";"4";"1";"6";"1"
```

Where:
- `DATID` is a sequential participant number, not the internal user ID.
- `P01`, `P02`, ... represent the survey questions before the final open
  remarks field.
- `DATOBS` represents the final open/free answer.

## Setup

- Install the plugin.
- Enable it in the plugin configuration.
- No manual `configuration.php` edit is required in Chamilo 2: the survey list
  now auto-detects this official export plugin and adds the action only when the
  plugin is enabled.

If you have large surveys with many answers, ensure `c_survey_answer` is
properly indexed.


## Activation

Plugin activation is controlled from the Chamilo plugins list. The configuration form only controls export-specific options.

## Current behavior

The export output is readable for teachers. Non-anonymous surveys include user identity columns. Anonymous surveys intentionally anonymize user identity.
