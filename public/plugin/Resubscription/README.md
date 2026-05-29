# Resubscription for Chamilo 2

This plugin limits repeated subscriptions to sessions when the target session contains a course that the learner already followed recently.

## Behavior

The plugin listens to the `SESSION_RESUBSCRIPTION` event before the subscription is completed.

When a learner tries to subscribe to a session, the plugin checks previous learner subscriptions and compares the courses of those sessions with the courses of the target session.

If a matching course is found inside the configured period, the subscription is blocked with an informational message.

## Settings

- `resubscription_limit = calendar_year`: blocks resubscription until the next calendar year.
- `resubscription_limit = natural_year`: blocks resubscription for one year after the previous access end date.

## Chamilo 2 notes

The plugin uses the existing Chamilo event and legacy plugin system. It does not create tables and does not modify courses, sessions or users.

The event subscriber is namespaced under `Chamilo\PluginBundle\Resubscription\EventSubscriber` so it can be registered by the Chamilo 2 plugin event subscriber compiler pass.

## Admin session subscription screen

Chamilo 2 also dispatches the same pre-subscription check from `public/main/session/add_users_to_session.php`.
This keeps the legacy administrator workflow aligned with the catalog workflow: adding a learner to a session is blocked when the target session contains a course already followed inside the configured period.

The `SessionResubscriptionEvent` accepts an optional `user_id` so admin-driven subscriptions can validate the selected learner instead of the current administrator account.
