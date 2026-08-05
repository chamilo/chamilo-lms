---
name: use-base-components
description: >
  Replace native HTML form elements with Base* components in Vue files AND
  ensure every Base*/SectionHeader/Fieldset tag in any Vue file has a matching
  import in <script setup> (Vue 3 does not auto-register most local components
  in this project; see the global list below). Auto-invoke when: user creates
  ANY new Vue file or page (form, dialog, view), adds Base*/SectionHeader/Fieldset
  tags to an existing Vue file, edits a Vue file containing native <input>,
  <select>, <textarea>, or <checkbox> elements, asks to refactor form fields,
  reports "Failed to resolve component" runtime warnings, or mentions
  BaseInputText, BaseSelect, BaseTextArea, BaseCheckbox, BaseCalendar,
  BaseColorPicker, BaseRadioButtons, BaseMultiSelect, BaseAutocomplete,
  BaseDialog, BaseTable, BaseButton, BaseInputNumber, SectionHeader, or any
  Base* component.
  Do NOT invoke for: non-Vue files, React or Angular components, styling-only
  changes inside files whose Base* imports are already complete.
allowed-tools:
  - Read
  - Edit
  - Write
  - Glob
  - Grep
  - Bash
---

Review the Vue file(s) referenced in `$ARGUMENTS` (or the currently open file if no argument is given)
and replace every native HTML form element with the appropriate `Base*` component from
`assets/vue/components/basecomponents/`. Follow the mapping and rules below exactly.

---

## ⚠ Critical rule (read first): every component used in the template must be imported

In this project, Vue 3 with `<script setup>` does **NOT** auto-register most local components.
A small set of components **is** registered globally in `assets/vue/main.js:198-207` and must
**NOT** be imported:

- **PrimeVue (global):** `Dialog`, `ConfirmDialog`, `DataView`, `Dropdown` (alias of `Select`),
  `InputText`, `Button`, `Column`, `ColumnGroup`, `Toolbar`.
- **Base (global):** `BaseAppLink`.

**Everything else** — `BaseButton`, `BaseDialog`, `BaseSelect`, `BaseTable`, `BaseInputText`,
`BaseInputNumber`, `BaseCheckbox`, `BaseCalendar`, `BaseAutocomplete`, `BaseMultiSelect`,
`BaseRadioButtons`, `BaseColorPicker`, `BaseTextArea`, `BaseIcon`, `SectionHeader`, `Fieldset`,
etc. — **MUST be imported explicitly** in `<script setup>`.

> Note: prefer the `Base*` wrapper over the raw PrimeVue global where one exists. Use
> `BaseDialog` (not the global `Dialog`), `BaseSelect` (not the global `Dropdown`),
> `BaseInputText` (not the global `InputText`), `BaseButton` (not the global `Button`).
> The PrimeVue globals exist mostly for legacy/transitional code and inside `Base*` wrappers.
> `Column`, `ColumnGroup` and `Toolbar` are the normal exceptions you use directly (inside
> `BaseTable`).

**Why this rule exists**: when a non-global component is missing its import, Vue 3 does **NOT**
raise a build error. `yarn build` reports `webpack compiled successfully` even with missing
imports. The failure surfaces only at runtime as `[Vue warn]: Failed to resolve component:
BaseDialog`, and that warning does not show in CI. So you cannot rely on `yarn build` to catch
this — you must cross-check by hand (or via the verification command at the end of this skill).

When you **create** a new Vue file or **add** a component tag to an existing one, the imports
are part of the same change. Don't defer them. Don't rely on the file already having the
imports from a prior edit. Verify every time.

Standard import paths (note: from `assets/vue/views/<feature>/Foo.vue` the prefix is `../../`;
from `assets/vue/views/<feature>/<sub>/Foo.vue` it is `../../../`):

```js
// Most common Base* components
import BaseAutocomplete from "../../components/basecomponents/BaseAutocomplete.vue"
import BaseButton       from "../../components/basecomponents/BaseButton.vue"
import BaseCalendar     from "../../components/basecomponents/BaseCalendar.vue"
import BaseCheckbox     from "../../components/basecomponents/BaseCheckbox.vue"
import BaseColorPicker  from "../../components/basecomponents/BaseColorPicker.vue"
import BaseDialog       from "../../components/basecomponents/BaseDialog.vue"
import BaseIcon         from "../../components/basecomponents/BaseIcon.vue"
import BaseInputNumber  from "../../components/basecomponents/BaseInputNumber.vue"
import BaseInputText    from "../../components/basecomponents/BaseInputText.vue"
import BaseMultiSelect  from "../../components/basecomponents/BaseMultiSelect.vue"
import BaseRadioButtons from "../../components/basecomponents/BaseRadioButtons.vue"
import BaseSelect       from "../../components/basecomponents/BaseSelect.vue"
import BaseTable        from "../../components/basecomponents/BaseTable.vue"
import BaseTextArea     from "../../components/basecomponents/BaseTextArea.vue"

// Layout
import SectionHeader from "../../components/layout/SectionHeader.vue"

// PrimeVue Fieldset is NOT global — import it when used
import Fieldset from "primevue/fieldset"
```

**Exceptions (do NOT import):** the global components listed above —
`Dialog`, `ConfirmDialog`, `DataView`, `Dropdown`, `InputText`, `Button`, `Column`,
`ColumnGroup`, `Toolbar`, `BaseAppLink`. In practice the ones you'll legitimately use directly
are `Column` / `ColumnGroup` / `Toolbar` (inside `<BaseTable>`).

---

## Mapping: native element → Base* component

| Native element                     | Base* component    | Import path                                            |
|------------------------------------|--------------------|--------------------------------------------------------|
| `<input type="text">`              | `BaseInputText`    | `../../components/basecomponents/BaseInputText.vue`    |
| `<textarea>`                       | `BaseTextArea`     | `../../components/basecomponents/BaseTextArea.vue`     |
| `<input type="number">`            | `BaseInputNumber`  | `../../components/basecomponents/BaseInputNumber.vue`  |
| `<select>` + `<option>`            | `BaseSelect`       | `../../components/basecomponents/BaseSelect.vue`       |
| `<input type="date">`              | `BaseCalendar`     | `../../components/basecomponents/BaseCalendar.vue`     |
| `<input type="checkbox">` (binary) | `BaseCheckbox`     | `../../components/basecomponents/BaseCheckbox.vue`     |
| `<input type="radio">` group       | `BaseRadioButtons` | `../../components/basecomponents/BaseRadioButtons.vue` |
| Multi-select list                  | `BaseMultiSelect`  | `../../components/basecomponents/BaseMultiSelect.vue`  |
| Search-with-suggestions            | `BaseAutocomplete` | `../../components/basecomponents/BaseAutocomplete.vue` |
| `<input type="color">`             | `BaseColorPicker`  | `../../components/basecomponents/BaseColorPicker.vue`  |

Remove the surrounding `<div><label>…</label><input>…</div>` wrapper — every Base* component
renders its own label internally.

---

## Critical rule: label translation

Components differ in whether they call `t()` internally:

| Component      | Internal `t()`?            | How to pass label                              |
|----------------|----------------------------|------------------------------------------------|
| `BaseTextArea` | **Yes** (`{{ t(label) }}`) | `label="Raw key"` — never wrap in `t()`        |
| All others     | No                         | `:label="t('Raw key')"` — always wrap in `t()` |

Passing `t('Key')` to `BaseTextArea` will attempt to translate an already-translated string and
break non-English locales.

---

## Component APIs

### BaseInputText
```vue
<BaseInputText
  id="field-id"
  v-model="form.title"
  :label="t('Title')"
  name="title"
/>
```
**Props:** `id` (required), `label` (required), `modelValue` (String|null, required),
`name`, `errorText`, `isInvalid`, `required`, `helpText`, `formSubmitted`, `disabled`.
Has `inheritAttrs: false` with `v-bind="$attrs"` on the inner input → extra attrs and
events (`@input`, `@blur`, `autocomplete`, `placeholder`, etc.) are forwarded.

---

### BaseTextArea
```vue
<BaseTextArea
  id="field-id"
  v-model="form.description"
  label="Description"
  name="description"
  rows="3"
/>
```
**Props:** `id` (required), `label` (required, raw key), `modelValue` (String, required),
`errorText`, `isInvalid`.
Extra attrs (`rows`, `name`, etc.) forwarded via `v-bind="$attrs"`.

---

### BaseInputNumber
```vue
<BaseInputNumber
  id="field-id"
  v-model="form.score"
  :label="t('Score')"
  :min="0"
  :step="0.1"
  name="score"
/>
```
**Props:** `id` (required), `label` (required), `modelValue` (Number, required),
`step` (default 1), `min`, `max`, `isInvalid`, `errorText`, `disabled`, `helpText`.
Renders PrimeVue InputNumber with +/− spinner buttons. `name` is NOT forwarded — no
`v-bind="$attrs"` on this component.

---

### BaseSelect
```vue
<BaseSelect
  id="field-id"
  v-model="form.status"
  :label="t('Status')"
  :options="statusOptions"
  name="status"
/>
```
**Props:** `id`, `label` (required), `options` (Array, required), `optionLabel` (default `"label"`),
`optionValue` (default `"value"`), `name`, `placeholder`, `allowClear`, `hastEmptyValue`,
`isLoading`, `disabled`, `messageText`, `isInvalid`.
Uses `defineModel` — compatible with `v-model`.

**Options format** (default keys):
```js
const statusOptions = [
  { label: t('Active'), value: 'active' },
  { label: t('Inactive'), value: 'inactive' },
]
```
Or pass `:option-label="'title'"` `:option-value="'id'"` to use existing object keys directly.

Properties with `@` in the name (e.g. `@id`) are not safe as `optionValue` strings — map
them to plain keys in a computed property first:
```js
const compensationOptions = computed(() =>
  compensations.value.map((c) => ({ label: c.title, value: c['@id'] }))
)
```

Use `allow-cleared` for optional filters (adds a clear/× button). Use `:hast-empty-value="true"`
to prepend a `--` row when the field is required with a blank default.

**For large lists (dozens/hundreds of options) or nested lists**, use `BaseAutocomplete`
instead of `BaseSelect` — see the [BaseAutocomplete](#baseautocomplete) section and the
caching pattern with `useEntityCache`.

---

### BaseCalendar
```vue
<BaseCalendar
  id="field-id"
  v-model="form.startDate"
  :label="t('Start date')"
/>
```
**Props:** `id` (required), `label` (required), `type` (`"single"` | `"range"`, default `"single"`),
`showTime`, `isInvalid`, `errorText`, `showInline`.
Uses `defineModel` — type `Date | Array | String | null`.

**⚠ Important:**
- Initialise the model value as `null` or `new Date()`, never as `""`.
- The model holds a `Date` object after user interaction. Serialise with
  `new Date(value).toISOString()` before sending to the API.
- For string comparisons in computed filters, convert first:
  ```js
  const dateStr = value instanceof Date ? value.toISOString().slice(0, 10) : String(value).slice(0, 10)
  ```
- After switching from `<input type="date">`, update `resetFilters` / initial form values to use
  `null` instead of `""`.

**Date range — `type="range"`:**
When a form has a start field and an end field, use a single `BaseCalendar` with
`type="range"` instead of two separate calendars. The model is `[startDate, endDate | null]`:

```vue
<BaseCalendar
  id="date-range"
  v-model="dateRange"
  :label="t('Assignment period')"
  type="range"
/>
```

```js
const dateRange = ref([new Date(), null])   // initialize with start = today, end = null

// When saving, extract by index:
const payload = {
  startDate: dateRange.value?.[0] ? new Date(dateRange.value[0]).toISOString() : null,
  endDate:   dateRange.value?.[1] ? new Date(dateRange.value[1]).toISOString() : null,
}

// When resetting:
dateRange.value = null   // or [new Date(), null] if you want to pre-fill the start
```

The second element is `null` until the user selects the end date — check
`dateRange.value?.[1]` before serializing.

---

### BaseCheckbox
```vue
<BaseCheckbox
  id="field-id"
  v-model="form.isActive"
  :label="t('Active')"
  name="is_active"
/>
```
**Props:** `id` (required), `name` (required), `label` (required).
Uses `defineModel` (Boolean) — binary checkbox only. For multi-value checkbox arrays
(e.g. selecting multiple IRIs), keep native `<input type="checkbox">` with `v-model` array
binding.

---

### BaseRadioButtons
```vue
<BaseRadioButtons
  v-model="form.type"
  :options="typeOptions"
  :title="t('Type')"
  name="type"
/>
```
**Props:** `modelValue` (String|Number, required), `name` (required),
`options` (Array `{ label, value }`, required), `title` (optional heading).
Uses traditional emit pattern (not `defineModel`) — `v-model` works as expected.
Translate option labels in the computed/data, not inside the component:
```js
const typeOptions = computed(() => [
  { label: t('Internal'), value: 'internal' },
  { label: t('External'), value: 'external' },
])
```

---

### BaseMultiSelect
```vue
<BaseMultiSelect
  v-model="form.tags"
  :options="tagOptions"
  :label="t('Tags')"
  input-id="field-id"
  option-label="title"
  option-value="id"
/>
```
**Props:** `modelValue` (Array, required), `options` (Array), `inputId` (required — note: `inputId`
not `id`), `label` (required), `optionLabel` (default `"name"`), `optionValue` (default `"id"`),
`isInvalid`, `errorText`, `isLoading`.
Renders chips for selected values.

---

### BaseAutocomplete
```vue
<BaseAutocomplete
  id="field-id"
  v-model="form.user"
  :label="t('User')"
  :search="searchUsers"
  option-label="fullName"
/>
```
**Props:** `id` (required), `label` (required), `search` (Function, required),
`optionLabel` (default `"name"`), `isMultiple`, `disabled`, `helpText`, `isInvalid`.

**When to use `BaseAutocomplete` instead of `BaseSelect`:**

- The list has **dozens or hundreds** of items — a `<select>` becomes unwieldy.
- The source is a paginated endpoint or has no known upper bound (e.g. `/api/users`).
- The list is **nested** (e.g. skill tree) and the user needs to search by text.

For short and fixed lists (≤ ~20 items, e.g. statuses, periodicities, types), keep using `BaseSelect`.

The `search` prop receives the query and returns a Promise with the array of suggestions. There are two patterns depending on the dataset size:

**1) Server-side search** — large or paginated datasets:
```js
async function searchUsers(query) {
  const result = await baseService.getCollection('/api/users', { search: query, itemsPerPage: 10 })
  return result.items
}
```

**2) In-memory cached search — `useEntityCache`** (`assets/vue/composables/useEntityCache.js`):

Factory with a module-level `Map`. Caches the list by endpoint for the whole session — multiple views / multiple autocomplete instances share a single request.

```js
import { useEntityCache } from "../../composables/useEntityCache"

const skillsCache = useEntityCache("/api/skills", { pagination: false })

onMounted(async () => {
  await skillsCache.load()   // loads once per session
})
```

```vue
<BaseAutocomplete
  id="skill-ref"
  v-model="selectedSkill"
  :label="t('Skill')"
  :search="skillsCache.search"
  option-label="title"
/>
```

Cache API:
- `load()` — lazy-loads once; deduplicates concurrent calls; subsequent calls return the already cached items.
- `search(query)` — filters in-memory by the `labelField` (3rd argument of the factory, default `"title"`). Pass as the `search` prop.
- `findById(id)` — resolves a numeric id to the object. Useful in edit mode when the backend sends only the id.
- `invalidate()` — clears the cache. Call it after creating/editing/deleting the entity to force a reload.

**When the v-model must be sent as an id (not an IRI nor an object)** — `BaseAutocomplete` keeps the full object in the model; on save extract `.id`, and on load (edit) resolve id → object with `findById`:
```js
// addItem
form.items.push({ ref: null })

// load (edit mode)
items: backendItems.map((i) => ({ ref: skillsCache.findById(i.refId) }))

// save payload
items: form.items.filter((i) => i.ref?.id).map((i) => ({ refId: Number(i.ref.id) }))
```

**Backend — enable `pagination=false` when using the cache**: entities whose `ApiResource` does not declare `paginationClientEnabled: true` will silently ignore the client's `pagination: false` and return only 30 items. Verify the entity attribute before caching full lists.

**Always use `BaseAutocomplete` instead of the manual `<BaseInputText>` + `<ul>` pattern.**
When you see this pattern in a Vue file, replace it:

```vue
<!-- ❌ manual pattern to replace -->
<div class="relative">
  <BaseInputText v-model="userSearch" @input="onUserInput" ... />
  <ul v-if="results.length" class="absolute ...">
    <li v-for="u in results" @click="selectUser(u)">{{ u.fullName }}</li>
  </ul>
</div>
<p v-if="selectedUser">{{ selectedUser.fullName }}</p>
```

```vue
<!-- ✅ use BaseAutocomplete -->
<BaseAutocomplete
  id="field-id"
  v-model="selectedUser"
  :label="t('User')"
  :search="searchUsers"
  option-label="fullName"
/>
```

> Note: this project also ships `BaseUserFinder` and `BaseSearchSelect` for some user/entity
> pickers. If the file already uses one of them, keep it; for new generic autocompletes prefer
> `BaseAutocomplete`.

**When the v-model must be an IRI** (not the full object), keep a separate ref for the
autocomplete and extract the IRI when building the payload:
```js
const selectedUser = ref(null)

// in save():
const payload = { user: selectedUser.value?.["@id"] ?? null }
```

**When clearing or resetting**, simply assign `null`:
```js
function resetForm() {
  selectedUser.value = null
}
```

Also remove: the text refs (`userSearch`), the suggestion refs (`userSearchResults`),
the debounce timers, and the `onUserInput`, `selectUser`, `clearUser` functions.

---

### BaseColorPicker
```vue
<BaseColorPicker
  v-model="form.color"
  :label="t('Color')"
/>
```
**Props:** `modelValue` (Color instance from `colorjs.io`, required), `label` (no internal `t()` —
use `:label="t('...')"`), `error`.

**⚠ Important:** The model value must be a `Color` object from `colorjs.io`, never a plain string.
Three places in the script always need updating together:

```js
import Color from 'colorjs.io'

// 1. Initial state (ref declaration)
const form = ref({ color: new Color('#3B82F6') })

// 2. Loading existing data (e.g. openForm(item))
form.value.color = item ? new Color(item.color) : new Color('#3B82F6')

// 3. Serialising for the API (save payload)
const payload = {
  color: form.value.color.toString({ format: 'hex' }),
}
```

`colorjs.io` is already a project dependency — no installation needed.

---

### BaseDialog
```vue
<BaseDialog
  v-model:is-visible="myDialog"
  :title="editingItem ? t('Edit item') : t('Add item')"
  :style="{ width: '480px' }"
>
  <!-- form fields -->
  <template #footer>
    <BaseButton :label="t('Cancel')" icon="close" type="plain" @click="myDialog = false" />
    <BaseButton :label="t('Save')" icon="save" type="success" @click="save" />
  </template>
</BaseDialog>
```
**Props:** `title` (String, required), `headerIcon` (String, optional — MDI icon name).
**Model:** `isVisible` (Boolean) — bind with `v-model:is-visible`.
Always use `<BaseDialog>` instead of PrimeVue `<Dialog>` directly (even though `Dialog` is
globally registered). It wraps Dialog with `modal: true` and a consistent header layout.

- Extra attrs (e.g. `:style`, `:class`) fall through to the inner `<Dialog>` — use `:style="{ width: '...' }"` to control dialog width.
- Footer goes in the `#footer` named slot.
- Never import `Dialog` from `primevue/dialog` in a view — use `BaseDialog` instead.
- For simple confirm/cancel or delete dialogs, `BaseDialogConfirmCancel` and `BaseDialogDelete`
  already exist — prefer them over hand-rolling a `BaseDialog` with two buttons.

---

### BaseTable
```vue
<BaseTable :values="rows" :is-loading="isLoading">
  <Column field="firstname" :header="t('First name')" sortable />
</BaseTable>
```
`BaseTable` (`assets/vue/components/basecomponents/BaseTable.vue`) wraps PrimeVue `DataTable`.
`Column` is globally registered in `main.js` — no import needed.

#### Server-side pagination (lazy mode)

Use lazy mode when the underlying API supports native pagination and the dataset may exceed the
default page size (30 for API Platform). For short, bounded lists prefer **client-side** with
`{ pagination: false }` — see `assets/vue/views/lp/LpList.vue` or
`assets/vue/views/message/MessageList.vue` for that variant. For the lazy variant, see
`assets/vue/views/admin/UserList.vue` or `assets/vue/views/admin/CourseList.vue`.

**When to choose lazy** (server-side):
- Collection has no known upper bound or is expected to grow (users, sessions, applications).
- The API Platform resource exposes pagination (default behaviour unless overridden).

**When to choose `{ pagination: false }`** (client-side):
- Bounded list ≤ ~50 items (statuses, types, branches, periodicities used in selects).
- The entity declares `paginationClientEnabled: true` so the client can opt out.

**Lazy pagination template:**
```vue
<BaseTable
  v-model:rows="pageSize"
  :is-loading="loading"
  :lazy="true"
  :total-items="total"
  :values="items"
  data-key="id"
  @page="onPage"
>
  <Column :header="t('Title')" field="title" />
</BaseTable>
```

```js
import baseService from "../../services/baseService"

const items = ref([])
const total = ref(0)
const page = ref(1)
const pageSize = ref(20)
const loading = ref(false)

async function load() {
  loading.value = true
  try {
    const { items: rows, totalItems } = await baseService.getCollection("/api/periodicities", {
      page: page.value,
      itemsPerPage: pageSize.value,
    })
    items.value = rows
    total.value = totalItems
  } finally {
    loading.value = false
  }
}

function onPage(event) {
  page.value = event.page + 1   // PrimeVue uses 0-based pages; API Platform uses 1-based
  pageSize.value = event.rows
  load()
}

onMounted(load)
```

**Convention — where the request lives:**

- Call `baseService.getCollection(endpoint, params)` **directly from the view** when you need
  pagination metadata (`totalItems`). It already returns `{ items, totalItems, nextPageParams }`.
- The service wrapper's `getAll(params)` is the shortcut "give me the items array only".
  Do **not** add `getPage` / `getPaginated` / `getCollection` variants to service wrappers —
  the base service already exposes that entry point.
- Reserve the service wrapper for endpoint-specific shortcuts (`getAll`, `create`, `update`,
  `remove`, and any non-trivial composed queries).

**`sortable` columns + lazy = need backend support.**
With `:lazy="true"` PrimeVue stops sorting client-side. Marking a column `sortable` is then
only meaningful when the entity declares `#[ApiFilter(OrderFilter::class, properties: [...])]`.
If the filter is missing, remove `sortable` to avoid a misleading UI; re-enable once
`OrderFilter` is wired and you forward `order[field]=asc|desc` from a `@sort` handler.

---

### BaseButton
```vue
<BaseButton :label="t('Save')" icon="save" type="success" @click="save" />
```
Always use `<BaseButton>` instead of a plain `<button>` (or the global PrimeVue `Button`).
Import from `../../components/basecomponents/BaseButton.vue`.

**CRUD color convention (`type` prop):**
- Create / add / save / import → `type="success"` (green)
- Read / view / export / list → `type="primary"` (blue)
- Update / edit / configure / move → `type="secondary"` (orange)
- Delete / disable / remove → `type="danger"` (red)
- Cancel / dismiss → `type="plain"` (gray)
- Buttons are for actions only — never style a non-action link as a button.

**Table row action convention:**
- Edit: `type="secondary-text"`, `icon="pencil"`, `only-icon`, `size="small"`
- Delete: `type="danger-text"`, `icon="delete"`, `only-icon`, `size="small"`
- Never put `ch-tool-icon` on icons inside a button — the icon inherits the button's text colour automatically.

---

### Fieldset (field grouping)

When a group of fields has a visible title, use PrimeVue's `<Fieldset>` instead of
`<div class="field-group">` + `<label>`. `Fieldset` is **not** global — import it.

```vue
import Fieldset from "primevue/fieldset"

<Fieldset :legend="t('Tags')">
  <div class="flex flex-wrap gap-4">
    <BaseCheckbox ... />
  </div>
</Fieldset>
```

**When to apply it:** any `<div>` with a `<label>` whose only purpose is to title a
group of inputs (checkboxes, radios, related fields). Do not use it for a single field —
every `Base*` component already renders its own label.

---

### SectionHeader

Use `<SectionHeader>` for section or page headings instead of a manual `<h2>` / `<div>`.

```vue
import SectionHeader from "../../components/layout/SectionHeader.vue"

<SectionHeader :title="t('Benefit tags')">
  <BaseButton
    :label="t('Add tag')"
    icon="plus"
    type="success"
    @click="openForm()"
  />
</SectionHeader>
```

**Props:** `title` (String, required), `size` (String, default `"2"` → renders `<h2>`).
The default slot is for action buttons (rendered to the right of the title).
Automatically includes `StudentViewButton` when there is course context — do not add it manually.

---

### Notifications — useNotification

Never use PrimeVue's `useToast` directly. Always use the composable
`assets/vue/composables/notification.js`:

```js
import { useNotification } from "../../composables/notification"

const { showSuccessNotification, showErrorNotification } = useNotification()

// success
showSuccessNotification(t("Saved"))

// error from an Error object (Axios or native JS)
showErrorNotification(e)

// error with a fixed message (when the catch doesn't have the correct message)
showErrorNotification(t("Cannot delete a benefit that has active assignments."))
```

`showErrorNotification` accepts both an `Error` object / Axios response and a string.
It sanitizes the message, filters internal exception leakage, and prevents duplicate toasts.

**Available methods:** `showSuccessNotification`, `showInfoNotification`,
`showWarningNotification`, `showErrorNotification`.

---

## Checklist

Work through the target file(s) in this order:

1. **Read** the file before editing.
2. For each native form element found:
   a. Identify the correct Base* component from the mapping above.
   b. Note the existing `v-model`, `name`, and any event handlers (`@input`, `@change`, etc.).
   c. Apply the label translation rule (raw key for BaseTextArea, `t()` for all others).
   d. Preserve `name` attributes — Behat tests target inputs by name.
   e. For `BaseCalendar` replacements, check initialization values and filter comparisons.
   f. For `BaseSelect` replacements, prepare an options computed if the source data uses
   non-standard or unsafe property names (like `@id`).
3. Add all required imports in alphabetical order alongside existing base component imports.
4. Remove any `<label>` elements that were paired with the replaced inputs.
5. Remove wrapper `<div>` elements that existed only to group the label+input pair, unless they
   carry layout classes needed by the surrounding flex/grid container.
6. Do **not** replace:
    - `<input type="checkbox">` used in v-model array bindings (multi-value selection).
    - `<input type="color">` unless the form already imports `colorjs.io` or it is trivial to add.
    - Inputs inside third-party component slots that require a native element.

---

## Final verification — cross-check template tags vs `<script setup>` imports

Before declaring the file done, run this check on EVERY Vue file you created or modified.
`yarn build` will compile successfully even when imports are missing, so this is the only
reliable gate.

```bash
file=assets/vue/views/path/to/YourFile.vue

# Components used in the template (Base*, PrimeVue PascalCase, layout):
used=$(grep -oE '<(Base[A-Z][A-Za-z0-9]+|SectionHeader|Fieldset|Column)\b' "$file" \
  | sed 's/^<//' | sort -u)

# Components imported in <script setup>:
imported=$(grep -oE '^import [A-Z][A-Za-z0-9]+ from' "$file" \
  | awk '{print $2}' | sort -u)

# Globally-registered components (main.js:198-207) — never need an import.
# One per line and quoted below, so it works in both bash and zsh (zsh does NOT
# word-split unquoted variables, so a space-separated list would break here):
globals="BaseAppLink
Button
Column
ColumnGroup
ConfirmDialog
DataView
Dialog
Dropdown
InputText
Toolbar"

# Anything in `used` minus `imported` minus globals is a missing import:
echo "USED:"; echo "$used"
echo "IMPORTED:"; echo "$imported"
echo "MISSING:"; comm -23 <(echo "$used") <(printf '%s\n' "$imported" "$globals" | sort -u)
```

If `MISSING` is non-empty, **add the imports before returning**. Re-run `yarn build` after
adding them as a sanity check, but remember that build success alone does NOT prove the
imports are correct — only the diff above does.

**Common offenders observed in this project**: `BaseDialog`, `BaseSelect`, `BaseTable`,
`BaseAutocomplete`, `BaseInputNumber`, `SectionHeader`. These are easy to forget because
their tags read as if they were globally registered (`<BaseDialog>`, `<SectionHeader>`).