---
name: vue-breadcrumb
description: >
  Give a Vue SPA route its breadcrumb, entirely from the router file. The breadcrumb
  component reads four declarations in `meta` — `showBreadcrumb`, `breadcrumb`,
  `breadcrumbParents` and `breadcrumbResource` — and knows no page by name.
  Auto-invoke when: the user adds a route to any file in `assets/vue/router/`, creates a
  new Vue page or view that needs a URL, migrates a legacy PHP page to Vue, reports a wrong
  or missing breadcrumb, sees the console warning "[Breadcrumb] Route ... has no
  meta.breadcrumb", or mentions breadcrumb, crumb, trail, `meta.breadcrumb`,
  `breadcrumbParents` or `breadcrumbResource`.
  Do NOT invoke for: the legacy PHP breadcrumb (`window.breadcrumb`, injected by
  `public/main/`), for `/admin/settings/<namespace>` (its last crumb is read from the DOM),
  or for styling work on `Breadcrumb.vue` itself.
---

# Breadcrumbs for a Vue route

**Never edit `assets/vue/components/Breadcrumb.vue` to give a page its trail.** The component
holds no page name and no path. Everything a trail needs is declared in the route.

## The three steps that always apply

### 1. Ask for a breadcrumb

Set `showBreadcrumb: true` in the meta of the parent route. Without it, `App.vue` does not mount
the component and nothing you declare below has any effect.

A route that only sometimes has a trail declares a function instead. It receives the route:

```js
// The personal agenda hangs from no course, so it has nothing to show.
showBreadcrumb: (route) => Number(route.query?.cid || 0) > 0,
```

### 2. Declare the label

Set `meta.breadcrumb` on the parent **and on every leaf**. The value is a **translation key**,
never translated text.

```js
export default {
  path: "/resources/checklist/:node/",
  meta: {
    requiresAuth: true,
    showBreadcrumb: true,
    tool: "checklist",
    breadcrumb: "Checklists",
  },
  name: "checklist",
  component: () => import("../components/layout/SimpleRouterViewLayout.vue"),
  redirect: { name: "ChecklistList" },
  children: [
    { name: "ChecklistList", path: "", meta: { breadcrumb: "" }, component: ... },
    { name: "ChecklistCreate", path: "create", meta: { breadcrumb: "Create" }, component: ... },
    { name: "ChecklistUpdate", path: "edit/:id", meta: { breadcrumb: "Edit" }, component: ... },
  ],
}
```

`breadcrumb: ""` means **omit this crumb**, and it is what a list page usually wants: the tool
crumb already names that page. An **absent** property is a different thing — it triggers the
development warning and shows the raw route name.

The label can also be a function returning a key, when it depends on the request:

```js
breadcrumb: (route) => {
  const gid = Number(route.query?.gid || 0)
  const cid = Number(route.query?.cid || 0)

  return gid > 0 ? "Group agenda" : cid > 0 ? "Agenda" : "Personal agenda"
},
```

### 3. Register every key

A key that is new to the platform goes by hand into three files:

- `assets/locales/en_US.json`
- `translations/messages.pot`
- `translations/messages.en_US.po`

`php bin/console chamilo:update_vue_translations` does **not** create keys. It only propagates to
the other locales the keys that `en_US.json` already holds. Never commit a mass regeneration of
`assets/locales/*.json`; a single new line is fine.

Check first whether the key exists — most tool names already do:

```bash
python3 -c "import json; d=json.load(open('assets/locales/en_US.json')); print('Checklists' in d)"
```

## The two extra declarations

### `breadcrumbParents` — a page that hangs from a list page

Give the leaf the fixed ancestors that always precede it. Each entry is `{ label, route }`, where
`label` is a translation key and `route` is a Vue Router location — prefer `{ name: ... }` over a
raw path.

```js
meta: {
  breadcrumb: "Import checklists",
  breadcrumbParents: [
    { label: "Administration", route: { name: "AdminIndex" } },
    { label: "Checklists", route: { name: "ChecklistAdminList" } },
  ],
}
```

When several routes share the same ancestors, declare the lists once at the top of the file.
`assets/vue/router/admin.js` is the reference, with `adminCrumb`, `classesCrumbs`,
`multiUrlCrumbs` and `accessUrlCrumbs`; `room.js` and `branch.js` show the smaller shape.

A route with `breadcrumbParents` owns its whole trail and takes the first branch of
`buildBreadcrumb`, so nothing else runs for it.

### `breadcrumbResource` — a tool that opens a resource

Declare it on the **parent**, when the trail has to name the thing the user opened.

```js
// One resource at a time: list -> resource -> sub-page.
breadcrumbResource: {
  trail: "self",
  listRoute: "AssignmentsList",
  detailRoute: "AssignmentDetail",
  detailParam: "id",
}

// A chain of folders, walked up the resource-node parents.
breadcrumbResource: {
  trail: "ancestors",
  listRoute: "DocumentsList",
  detailRoute: "DocumentsList",
}
```

This declaration also drives the fetch: `loadResourceNodeIfNeeded` loads the resource node only
for a route whose tool declares it. Nothing else has to be wired.

## Do not do these

- **Do not add a rule to `Breadcrumb.vue`.** If a trail seems to need one, the shape is probably
  already covered by one of the four declarations. Read the component's own builders first.
- **Do not match route names by substring.** A rule like `route.name.includes("Page")` once put a
  "Pages" crumb on every wiki page of every course. It was removed for that reason.
- **Do not pass translated text as a label.** The component calls `t()` on what you declare.
- **Do not expect a one-crumb trail to appear.** The component gives the first crumb to the
  PrimeVue `home` slot, and the container is hidden while the rest is empty. A page whose trail
  reduces to its tool shows nothing.

## Verify

```bash
# Fails when a route renders a breadcrumb without declaring its label.
yarn check:breadcrumb-routes

npx eslint assets/vue/router/
yarn dev
```

Then open the page and read the browser console. `[Breadcrumb] Route "X" has no meta.breadcrumb`
means a declaration is missing. The warning is stripped from the production bundle.

## Cover it

`tests/playwright/features/breadcrumb.feature` holds the breadcrumb scenarios. Add yours there.

Assert on CSS selectors, never on crumb text: this platform runs in many languages and the test
environment is not always English.

```gherkin
Then I should see the ".app-breadcrumb a[href='/admin/urls']" element
```

An `a[href]` proves both that the ancestor is there and that it points at the right place. Two
traps that cost real time:

- **The last crumb is a `<span>`, not a link.** Asserting `a[href]` on the current page's own
  crumb never matches. Assert on an ancestor.
- **A tool with `breadcrumbResource` paints late.** It waits for the resource-node fetch, well
  past Playwright's 15 s default. Use `I wait up to 45 seconds for the element ... to appear`.

Playwright needs `BASE_URL=http://localhost` inside the container, and the generated spec under
`tests/playwright/.features-gen/` is tracked, so run `bddgen` and commit it too.
