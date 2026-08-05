# Top Links

Top Links lets a platform administrator create links that are replicated as course tools in every course. These tools are shown at the top of the course tools list.

## Chamilo 2 behavior

- The plugin is managed from the plugin list.
- The administration page is available through the plugin `Open` action.
- New links are replicated to existing courses.
- New courses receive the configured links through the plugin event subscriber.
- Links can point to an internal Chamilo path starting with `/` or to an external `http://` / `https://` URL.
- The plugin keeps its data in the existing `toplinks_link` and `toplinks_link_rel_tool` tables.

## Technical notes

The plugin remains self-contained under `public/plugin/TopLinks/`.

The event subscriber is located in:

`public/plugin/TopLinks/src/EventSubscriber/TopLinksEventSubscriber.php`

The subscriber intentionally has no namespace, following the current working pattern used by plugin subscribers in this branch.

No core modification is required.
