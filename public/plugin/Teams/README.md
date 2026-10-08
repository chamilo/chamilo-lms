# Microsoft Teams plugin

This plugin provides Microsoft Teams conference integration for Chamilo through Microsoft Graph.

## Lifecycle

Install, enable, disable and configure it from **Administration > Plugins**.

The plugin is a course plugin. Enabling it propagates the Teams course-tool link through Chamilo's
standard plugin lifecycle; disabling or uninstalling it removes those course-tool links.

Historical `ConferenceMeeting` records are intentionally kept when the plugin is uninstalled so a
later reinstall does not destroy conference history.

## Configuration

The plugin settings page contains:

- Tenant ID
- Client ID
- Client secret
- Enable personal conferences
- Enable global conferences
- Default visibility in course homepage

The Microsoft credentials are read server-side through the plugin configuration and are not added
to `platform-config`.

## Microsoft Entra / Graph requirements

The Microsoft Entra application is used in two different ways:

1. **Application credentials** create, update and cancel Teams online meetings on behalf of the
   organizer.
2. **Delegated sign-in for meeting organizers at Join time** verifies the organizer's Microsoft
   identity before redirecting to the Microsoft Teams join URL. Other Chamilo users who are
   authorized to join are redirected directly to the validated Teams join URL, where Microsoft
   Teams applies the tenant's guest, anonymous-user and lobby policies. No delegated access or
   refresh token is stored by Chamilo.

Required Microsoft Graph permissions:

- `OnlineMeetings.ReadWrite.All` — Application
- `User.Read.All` — Application
- `User.Read` — Delegated

Grant administrator consent for the application permissions. `OnlineMeetings.ReadWrite.All` also
requires a Microsoft Teams Application Access Policy for the users that Chamilo may use as meeting
organizers.

The application must contain this **Web** redirect URI for every Chamilo host using the plugin:

```text
https://YOUR-CHAMILO-HOST/conference/teams-auth/callback
```

For meeting organizers, the Join flow uses OAuth authorization code + PKCE and a one-time session
state. The access token is used only to call Microsoft Graph `/me` for the current join attempt and
is not persisted. Other authorized Chamilo participants do not need a Microsoft identity in the
organizer's tenant; Chamilo authorizes the meeting access first and then redirects them to the
validated Teams join URL. Whether they can enter anonymously, as guests, or must sign in is decided
by the Microsoft Teams tenant and meeting policies.

`Calendars.ReadWrite` is not required. The optional course Agenda synchronization is implemented
inside Chamilo and does not write to the organizer's Outlook calendar.

## Chamilo meeting scopes

Available meeting scopes are:

- **Course** — course teachers manage meetings; authorized learners can view and join them.
- **Personal** — an authenticated user manages only their own personal meetings, when enabled.
- **Global** — all authorized users can view/join them, while platform administrators manage them,
  when enabled.

Course/session/group and AccessUrl boundaries are re-checked server-side when a user opens the Join
route.

## Course integration

Course meetings support:

- immediate or scheduled creation;
- editing and cancellation while the meeting is still active/future;
- upcoming and past/cancelled history;
- a protected Chamilo Join route for teachers and learners;
- optional course announcements with the protected Join link;
- optional synchronization with the Chamilo course Agenda.

When **Add to calendar** is selected while creating a course meeting, Chamilo creates a
`CCalendarEvent` in the same course/session/group context and stores its identifier in
`ConferenceMeeting::calendarId`. Editing the Teams meeting updates the linked Agenda event. Cancelling
the meeting keeps the historical Agenda entry but marks it as cancelled and removes the Join action
from its content.

No Microsoft calendar permission is used for this feature.

## Protected invitations and calendar files

The Vue UI never needs to expose the raw Microsoft `joinUrl`. Join, Copy meeting link, course
announcements and generated `.ics` files use this protected Chamilo URL instead:

```text
/conference/teams-auth/join/{meetingId}
```

That endpoint always re-checks Chamilo access before redirecting to Microsoft Teams. Meeting
organizers complete the Microsoft identity check; other authorized participants are sent directly
to the validated Teams join URL.

Meeting lists link to a details page for each conference. Upcoming meetings provide Join, Copy link,
Add to calendar (`.ics`), Edit and Cancel actions when allowed. Past/cancelled meetings remain in
history and can still be inspected or exported to a calendar file.
