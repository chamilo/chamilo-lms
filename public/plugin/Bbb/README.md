# BigBlueButton plugin for Chamilo 2

This plugin integrates **BigBlueButton (BBB)** videoconferencing into **Chamilo 2**.

It allows you to create and join videoconference rooms from inside Chamilo courses, optionally manage recordings, support global conferences, group conferences, pre-upload presentation documents, and track meeting activity.

## Features

The current Chamilo 2 plugin includes support for:

- Course videoconference rooms
- Global conference rooms
- Global conference per user
- Conference rooms inside course groups
- Optional course-level BBB settings
- Recording support
- Recording regeneration (if enabled)
- Recording visibility management (publish / unpublish)
- Optional deletion of BBB recordings when a course or session is removed
- Shareable conference link
- Optional hiding of the conference link block
- Optional disabling of conference download links
- Maximum simultaneous users limit
- Meeting duration limit
- Pre-upload of presentation documents before entering the room
- BBB webhook integration
- Webhook-based activity dashboard for administrators
- Optional VM autoscaling hooks
- Calendar integration for meetings

## Requirements

- A working **Chamilo 2** installation
- A reachable **BigBlueButton server**
- The BBB server URL and shared secret (`salt`)
- Proper plugin activation from Chamilo administration

## Installation

1. Copy the plugin into the Chamilo plugin directory:

   ```bash
   public/plugin/Bbb
   ```

2. Go to:

   **Administration → Plugins**

3. Install and enable the **BigBlueButton** plugin.

4. Open the plugin configuration and define at least:

    - **host**: the URL of your BigBlueButton server
    - **salt**: the shared secret provided by your BBB server

> No manual SQL setup should be required for normal Chamilo 2 installations or upgrades handled through the platform.
> This README does **not** use Chamilo 1.x migration steps.

## Main configuration options

The plugin currently defines the following main settings:

### BBB connection

- **host**
  BigBlueButton server URL

- **salt**
  Shared secret used to sign BBB API requests

### Global conference options

- **enable_global_conference**
  Enables a platform-wide conference room

- **enable_global_conference_per_user**
  Enables a dedicated personal global conference per user

- **enable_global_conference_link**
  Shows the global conference link on the homepage

- **global_conference_allow_roles**
  Defines which user roles can see the global conference link

### Course and group options

- **enable_conference_in_course_groups**
  Allows conference rooms inside course groups

- **disable_course_settings**
  Disables per-course BBB configuration and enforces global plugin values

### Recording options

- **big_blue_button_record_and_store**
  Enables recording for meetings

- **allow_regenerate_recording**
  Allows regeneration of BBB recordings

- **bbb_force_record_generation**
  Forces recording generation at course level if enabled

- **delete_recordings_on_course_delete**
  Deletes BBB recordings when a course or session is removed

### Conference access and sharing

- **enable_global_conference_link**
  Exposes the global conference entry link

- **hide_conference_link**
  Hides the shareable conference link block near the join button

- **disable_download_conference_link**
  Disables conference download links

### Limits

- **max_users_limit**
  Sets a maximum number of simultaneous users in a conference room

- **meeting_duration**
  Sets the meeting duration in minutes

### Webhooks

- **webhooks_enabled**
  Enables BBB webhook integration

- **webhooks_scope**
  Defines whether hooks are managed per meeting or globally

- **webhooks_hash_algo**
  Selects the HMAC algorithm used to protect webhook callbacks

- **webhooks_event_filter**
  Optional comma-separated BBB event filter

## Course settings

When course settings are enabled, the plugin can expose the following course-level options:

- **big_blue_button_record_and_store**
- **bbb_enable_conference_in_groups**
- **bbb_force_record_generation**
- **big_blue_button_students_start_conference_in_groups**

If **disable_course_settings** is enabled globally, these values are enforced from the plugin configuration for all courses.

## User-facing behavior

### In courses

Teachers can manage the conference room from the course context and:

- start a meeting
- close a meeting
- manage recordings
- add the conference to the calendar
- pre-upload presentation files before entering the room

Depending on permissions and configuration, learners may be able to:

- join the room
- join group conferences
- start group conferences (if explicitly allowed)

### Pre-uploaded documents

Before entering a meeting, the manager can pre-load presentation documents.
The current interface is designed to select course documents and submit them together with the meeting start request.

Supported selection in the current UI is oriented to presentation/document formats such as:

- PDF
- PPT
- PPTX
- ODP

## Recordings

The plugin can list recordings associated with meetings and allows actions such as:

- publish
- unpublish
- delete
- regenerate (if enabled)
- copy recording links into Chamilo tools

Recording availability depends on BBB processing and the plugin configuration.

## Global conferences

The plugin supports:

- a shared global conference
- a personal global conference per user

Visibility of the global conference link can be restricted by role.

## Webhooks and activity dashboard

When webhooks are enabled, the plugin can receive BBB events and build activity information.

An administrator-only dashboard is available to visualize meeting activity grouped by meeting, including metrics such as:

- connected users
- joins
- leaves
- talk time
- camera time
- messages
- reactions
- hands raised

This dashboard is intended for platform administration and reporting.

## Cron tasks

### `cron_close_meeting.php`

This script is used to improve meeting activity tracking and room close handling.

It should be configured in the system cron if you want the tracking process to run automatically.

### `cron.php`

This script is used for optional VM-related automation if such infrastructure integration is configured.

## Optional VM autoscaling support

The plugin includes optional VM integration hooks.

A sample configuration file is provided:

```bash
config.vm.dist.php
```

If you want to use the **DigitalOcean** VM classes, an additional package is required:

```bash
composer require toin0u/digitalocean
```

Then adapt your VM configuration according to your infrastructure.

> VM support is optional and not required for standard BBB usage.

## Administration pages

The plugin includes administrative interfaces for:

- plugin settings
- meeting list / record list
- export of meeting data
- webhook activity dashboard

## Security notes

For production environments, make sure that:

- the BBB host URL is correct
- the shared secret is kept private
- cron endpoints are not exposed carelessly
- webhook callbacks are protected
- only authorized users can manage meetings and recordings
- BBB and Chamilo are both kept updated

## Notes for Chamilo 2

This README describes the **current Chamilo 2 plugin behavior**.

It intentionally does **not** include:

- Chamilo 1.x migration instructions
- legacy SQL upgrade snippets
- manual table creation steps from older plugin generations

If you are maintaining older historical branches, document them separately.

## License

See Chamilo's global license information in the platform source tree.
