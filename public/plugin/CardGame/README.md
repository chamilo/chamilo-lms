Card reveal game Chamilo plugin
===============================

This plugin adds a small daily card reveal widget to encourage users to return
to the platform every day.

Installation
------------

1. Enable the plugin from the platform plugins administration.
2. Install it so the `plugin_card_game` table is created or migrated.
3. Go to **Administration > Configuration settings > Regions** and assign the
   plugin to the **`pre_footer`** region.
4. Open the authenticated user home/dashboard page and use the floating
   launcher shown at the bottom-right corner.

Recommended C2 behavior
-----------------------

- Treat CardGame as a global user widget, not as a course tool.
- In C2, the recommended integration is through the **`pre_footer`** plugin
  region.
- The launcher is intentionally layout-independent, so it does not rely on the
  old Chamilo 1 sidebar or avatar DOM.
- The preferred place to expose it is the authenticated user home/dashboard.

Current behavior
----------------

- The widget opens in a modal dialog.
- Users can reveal one image piece per day.
- Progress is stored per user.
- Completed panels are tracked and shown in the archive area.

Migration notes
---------------

This C2-oriented version adapts the original Chamilo 1 plugin behavior to a
floating launcher + modal approach, which is more stable in modern C2 layouts.
