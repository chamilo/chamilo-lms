<template>
  <div
    v-if="items.length > 0"
    class="app-breadcrumb"
  >
    <Breadcrumb
      :home="home"
      :model="items"
    >
      <template #item="{ item, props }">
        <BaseIcon
          v-if="item.icon"
          :icon="item.icon"
          size="small"
        />
        <BaseAppLink
          v-if="(item.route || item.url) && !item.isLast"
          :to="item.route"
          :url="item.url"
          v-bind="props.action"
          @click="handleBreadcrumbClick(item, $event)"
        >
          {{ item.label }}
        </BaseAppLink>
        <span
          v-else
          v-bind="props.action"
          v-text="item.label"
        />
      </template>

      <template #separator> /</template>
    </Breadcrumb>
    <div
      v-if="session"
      class="app-breadcrumb__session-title"
      v-text="session.title"
    />
  </div>
</template>

<script setup>
import { computed, ref, watch } from "vue"
import { useRoute, useRouter } from "vue-router"
import { storeToRefs } from "pinia"
import { useStore } from "vuex"
import { useI18n } from "vue-i18n"
import Breadcrumb from "primevue/breadcrumb"
import { useCidReqStore } from "../store/cidReq"
import { formatToolName, getQueryInt, normalizeLegacyUrl, stripHtml } from "../utils/breadcrumb"
import BaseIcon from "./basecomponents/BaseIcon.vue"

const cidReqStore = useCidReqStore()
const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const { course, session } = storeToRefs(cidReqStore)
const store = useStore()

const specialRouteNames = [
  "MyCourses",
  "MySessions",
  "MySessionsUpcoming",
  "MySessionsPast",
  "Home",
  "MessageList",
  "MessageNew",
  "MessageShow",
  "MessageCreate",
]

let legacyItems = []
const calculatedList = ref([])

const resourceNode = computed(() => store.getters["resourcenode/getResourceNode"])

/**
 * Group breadcrumb support (no API calls)
 * - Detect gid in query and insert a group crumb after the course crumb.
 * - Label uses "Group 0001" style to be user-friendly even without fetching the real name.
 */
const gid = computed(() => getQueryInt(route.query, "gid", 0))

const home = computed(() => {
  if (!calculatedList.value.length) {
    return undefined
  }

  const first = calculatedList.value[0]

  return { ...first, label: stripHtml(first.label), icon: "compass" }
})

const items = computed(() => {
  if (!calculatedList.value.length) {
    return []
  }

  const list = calculatedList.value.slice(1)

  return list.map((item, index) => ({
    ...item,
    label: stripHtml(item.label),
    isLast: index === list.length - 1,
  }))
})

/**
 * Build the legacy group-space URL for the given group ID.
 * Reads `cid` and `sid` from the current query string first, falling back to the store values.
 * Adjust the path if your installation uses a different entry point.
 *
 * @param {number} currentGid - The numeric group ID.
 * @returns {string} Absolute-path URL pointing to `/main/group/group_space.php`.
 */
function buildGroupSpaceUrl(currentGid) {
  // Keep 0 values if present (sid=0 is valid)
  const cid = route.query?.cid ?? course.value?.id ?? 0
  const sid = route.query?.sid ?? session.value?.id ?? 0

  const qs = new URLSearchParams()
  qs.set("cid", String(cid))
  qs.set("sid", String(sid))
  qs.set("gid", String(currentGid))

  return `/main/group/group_space.php?${qs.toString()}`
}

/**
 * Read the active admin-settings section label directly from the DOM.
 * The label is already translated server-side, so no i18n call is needed here.
 *
 * @returns {string} Trimmed text content of the active settings menu item, or an empty string.
 */
function resolveSettingsSectionLabel() {
  // Safer because it's already translated server-side.
  const current = document.querySelector(".admin-settings__list a.admin-settings__item--active")

  return current?.textContent?.trim() || ""
}

/**
 * Decide if current page is inside a course/session context.
 * The cidReq store is guaranteed to be populated by router.beforeResolve before render.
 */
function isInCourseOrSessionContext() {
  const routeName = route.name

  if (!routeName) {
    return false
  }

  if (routeName === "CourseHome") {
    return true
  }

  if (course.value?.id && !specialRouteNames.includes(routeName)) {
    return true
  }

  return false
}

/**
 * Resolve a crumb label from a route meta object.
 *
 * A route name is a technical identifier, not a translation key. When `meta.breadcrumb` is
 * absent, the label falls back to the formatted route name and stays untranslated. The
 * development warning exposes the missing declaration instead of hiding it in the interface.
 *
 * `meta.breadcrumb` can also be a function that receives the current route and returns a
 * translation key. Declare one when the label depends on the request context, as the agenda
 * does with `cid` and `gid`.
 *
 * @param {object|undefined} meta - Route meta that can hold a `breadcrumb` label or resolver.
 * @param {string} name - Route or tool name used to build the fallback label.
 * @returns {string} Translated label, or the formatted route name.
 */
function resolveCrumbLabel(meta, name) {
  const label = meta?.breadcrumb
  const key = "function" === typeof label ? label(route) : label

  if (key) {
    return t(key)
  }

  if ("production" !== process.env.NODE_ENV) {
    console.warn(`[Breadcrumb] Route "${name}" has no meta.breadcrumb. Declare it in the router.`)
  }

  return formatToolName(name)
}

/**
 * Build the trail of a route that declares its own fixed ancestors.
 *
 * `meta.breadcrumbParents` holds the crumbs that always precede the page, each one a
 * `{ label, route }` pair whose label is a translation key. The page's own crumb comes from
 * `meta.breadcrumb`, so the route owns its whole trail and this file names no page.
 *
 * @returns {Array|null} Array of crumb items if the route declared ancestors; `null` otherwise.
 */
function buildDeclaredParentCrumbs() {
  const parents = route.meta?.breadcrumbParents

  if (!Array.isArray(parents) || 0 === parents.length) {
    return null
  }

  const items = parents.map((parent) => ({ label: t(parent.label), route: parent.route }))

  items.push({ label: resolveCrumbLabel(route.meta, route.name) })

  return items
}

/**
 * Build the breadcrumb trail from server-injected legacy items or from whitelisted path segments.
 *
 * Three cases are handled:
 * 1. `window.breadcrumb` was set by the PHP layer — consume those items directly.
 * 2. `/admin/settings/<namespace>`, whose last crumb is read from the DOM because the server
 *    already translated it. This is the one trail a route cannot declare on its own.
 * 3. The URL starts with a whitelisted segment (e.g. `/admin`) that has no matching Vue route —
 *    synthesize crumbs from path segments using the `overrides` map.
 *
 * Every other fixed trail lives in the router as `meta.breadcrumbParents`.
 *
 * @returns {Array|null} Array of crumb items if a manual trail was built; `null` to fall through.
 */
function buildManualCrumbs() {
  if (legacyItems.length > 0) {
    const items = []

    if (isInCourseOrSessionContext()) {
      const rootRouteName = session.value?.id ? "MySessions" : "MyCourses"

      const rootLabel = session.value?.id ? t("My sessions") : t("My courses")

      items.push({ label: rootLabel, route: { name: rootRouteName } })
    }

    legacyItems.forEach((item) => {
      const newUrl = normalizeLegacyUrl(item?.url)

      items.push({ label: item.name, url: newUrl || undefined })
    })

    legacyItems = []

    return items
  }

  const whitelist = ["admin"]
  const overrides = {
    admin: "AdminIndex",
    gdpr: null,
  }
  const labelOverrides = {
    email_tester: "E-mail tester",
  }
  const pathSegments = route.path.split("/").filter(Boolean)
  const baseSegment = pathSegments[0]

  if (!whitelist.includes(baseSegment)) {
    return null
  }

  // /admin/settings/<namespace>
  if (pathSegments[1] === "settings") {
    return [
      { label: t("Admin"), route: { name: overrides.admin, params: route.params, query: route.query } },
      { label: t("Settings"), route: { path: "/admin/settings" } },
      { label: resolveSettingsSectionLabel() },
    ]
  }

  const fullPath = "/" + pathSegments.join("/")

  if (router.getRoutes().some((r) => r.path === fullPath)) {
    return null
  }

  return pathSegments.map((segment, index) => {
    const rawLabel = labelOverrides[segment] ?? segment.charAt(0).toUpperCase() + segment.slice(1)
    const label = t(rawLabel)
    const override = overrides[segment]

    if (override === null) {
      return { label }
    }

    if (override) {
      return { label, route: { name: override, params: route.params, query: route.query } }
    }

    const partialPath = "/" + pathSegments.slice(0, index + 1).join("/")
    return { label, route: { path: partialPath } }
  })
}

/**
 * Build the static category prefix crumbs ("Pages", "Messages") when the route name contains them.
 *
 * @returns {Array} Zero, one, or two crumb items.
 */
function buildStaticCategoryPrefixes() {
  const items = []

  if (route.name?.includes("Page")) {
    items.push({ label: t("Pages"), route: { path: "/resources/pages" } })
  }

  if (route.name?.includes("Message")) {
    items.push({ label: t("Messages"), route: { path: "/resources/messages" } })
  }

  return items
}

/**
 * Build the root crumb ("My sessions" or "My courses") when inside a course/session context.
 *
 * @returns {Array} One crumb item, or empty array if not in a course/session context.
 */
function buildCourseContextRootCrumb() {
  if (!isInCourseOrSessionContext()) {
    return []
  }

  const rootRouteName = session.value?.id ? "MySessions" : "MyCourses"
  const rootLabel = session.value?.id ? t("My sessions") : t("My courses")

  return [{ label: rootLabel, route: { name: rootRouteName } }]
}

/**
 * Build the course title crumb linking back to the course home page.
 *
 * @returns {Array} One crumb item, or empty array if not in a course or already on CourseHome.
 */
function buildCourseTitleCrumb() {
  if (!course.value || route.name === "CourseHome") {
    return []
  }

  return [
    { label: course.value.title, route: { name: "CourseHome", params: { id: course.value.id }, query: route.query } },
  ]
}

/**
 * Build the group crumb when inside a group context (gid > 0 in query params).
 *
 * @returns {Array} One crumb item, or empty array if no group context.
 */
function buildGroupCrumb() {
  const currentGid = gid.value

  if (currentGid <= 0) {
    return []
  }

  return [{ label: `${t("Group")} ${String(currentGid).padStart(4, "0")}`, url: buildGroupSpaceUrl(currentGid) }]
}

/**
 * Build the crumbs of a tool whose resource is a chain of folders, such as Documents.
 *
 * Walks up the resource-node parent chain until it reaches the "courses" root. The outermost
 * folder becomes the entry point of the tool's list route; the rest follow as their own crumbs.
 *
 * @param {object} toolBase - Root matched route of the tool.
 * @param {object} spec - The route's `meta.breadcrumbResource` declaration.
 * @returns {Array} Array of crumb items.
 */
function buildAncestorTrailCrumbs(toolBase, spec) {
  const folders = []

  let current = resourceNode.value

  while (current?.parent && current.parent.title !== "courses") {
    folders.unshift({ label: current.title, node: current.id })
    current = current.parent
  }

  const entry = folders.shift()

  const items = [
    {
      label: resolveCrumbLabel(toolBase.meta, toolBase.name),
      route: {
        name: spec.listRoute,
        params: entry ? { node: entry.node } : route.params,
        query: route.query,
      },
    },
  ]

  folders.forEach((folder) => {
    items.push({
      label: folder.label,
      route: { name: spec.listRoute, params: { node: folder.node }, query: route.query },
    })
  })

  return items
}

/**
 * Build the crumbs of a tool that opens one resource at a time, such as Assignments.
 *
 * On the list page the resource crumb is skipped: the loaded node is the tool itself, not an
 * item the user picked.
 *
 * @param {object} toolBase - Root matched route of the tool.
 * @param {object} spec - The route's `meta.breadcrumbResource` declaration.
 * @returns {Array} Array of crumb items.
 */
function buildSingleResourceCrumbs(toolBase, spec) {
  const items = [
    {
      label: resolveCrumbLabel(toolBase.meta, toolBase.name),
      route: { name: spec.listRoute, params: { node: course.value.resourceNode.id }, query: route.query },
    },
  ]

  if (route.name === spec.listRoute || !resourceNode.value?.title) {
    return items
  }

  const id = route.params.id?.toString().match(/(\d+)$/)?.[1]

  items.push({
    label: resourceNode.value.title,
    route: id ? { name: spec.detailRoute, params: { [spec.detailParam]: id }, query: route.query } : undefined,
  })

  return items
}

/**
 * Build the crumbs of a tool that navigates a resource: tool, resource, current sub-page.
 *
 * The shape comes from `meta.breadcrumbResource`, so this file names no tool. The sub-page crumb
 * is dropped when the route asks for it with an empty label, when the page is the detail page
 * itself, or when it would repeat a crumb already in the trail.
 *
 * @param {object} toolBase - Root matched route of the tool.
 * @returns {Array} Array of crumb items.
 */
function buildResourceToolCrumbs(toolBase) {
  const spec = toolBase.meta.breadcrumbResource
  const items =
    "ancestors" === spec.trail ? buildAncestorTrailCrumbs(toolBase, spec) : buildSingleResourceCrumbs(toolBase, spec)

  const currentMatched = route.matched.find((r) => r.name === route.name)

  if ("" === currentMatched?.meta?.breadcrumb || route.name === spec.detailRoute) {
    return items
  }

  const finalLabel = resolveCrumbLabel(currentMatched?.meta, currentMatched?.name)

  if (!items.some((item) => item.label === finalLabel)) {
    items.push({
      label: finalLabel,
      route: { name: currentMatched.name, params: route.params, query: route.query },
    })
  }

  return items
}

/**
 * Dispatch to the appropriate crumb builder based on the current route.
 *
 * A route that declares `meta.breadcrumbResource` and has its resource loaded gets the resource
 * trail; every other tool route gets the generic tool crumb plus its sub-page.
 *
 * @returns {Array|null} Array of crumb items if a builder handled the route; `null` otherwise.
 */
function buildToolCrumbs() {
  const toolBase = route.matched?.[0]
  const mainToolName = toolBase?.name

  if (toolBase?.meta?.breadcrumbResource && resourceNode.value) {
    return buildResourceToolCrumbs(toolBase)
  }

  if (mainToolName) {
    const matchedRoutes = route.matched
    const currentMatched = matchedRoutes[matchedRoutes.length - 1]

    const toolLabel = resolveCrumbLabel(toolBase.meta, mainToolName)
    const toolBaseRouteName = toolBase.name === "admin" ? "AdminIndex" : toolBase.name
    const items = [{ label: toolLabel, route: { name: toolBaseRouteName, params: route.params, query: route.query } }]

    if (currentMatched.meta?.breadcrumb !== "") {
      const finalLabel = resolveCrumbLabel(currentMatched.meta, currentMatched.name)

      if (!items.some((item) => item.label === finalLabel)) {
        items.push({
          label: finalLabel,
          route: { name: currentMatched.name, params: route.params, query: route.query },
        })
      }
    }

    return items
  }

  return null
}

/**
 * Build crumbs for every matched route segment beyond the root (index 0).
 * Used as the generic fallback when no tool-specific builder claims the current route.
 *
 * @returns {Array} Array of crumb items.
 */
function buildRemainingMatchedCrumbs() {
  return route.matched.slice(1).reduce((items, r) => {
    const alreadyHasResource =
      resourceNode.value?.title && items.some((item) => item.label === resourceNode.value.title)

    if (!alreadyHasResource) {
      items.push({
        label: resolveCrumbLabel(r.meta, r.name),
        route: { name: r.name, params: route.params, query: route.query },
      })
    }

    return items
  }, [])
}

// window.breadcrumb is injected by PHP before Vue boots — read synchronously so it is
// available on the first (immediate) watch run, before onMounted would fire.
const wb = window.breadcrumb

if (Array.isArray(wb) && wb.length > 0) {
  legacyItems = wb
}

/**
 * Load the resource node for the routes whose tool declares `meta.breadcrumbResource`.
 * Clears the store for all other routes to prevent stale data from bleeding into the trail.
 */
async function loadResourceNodeIfNeeded() {
  const nodeId = route.params.node || route.query.node
  const needsNode = Boolean(route.matched?.[0]?.meta?.breadcrumbResource) && nodeId

  if (needsNode) {
    try {
      store.commit("resourcenode/ADD_RESOURCE_NODE", null)
      const resourceApiId = nodeId.startsWith("/api/") ? nodeId : `/api/resource_nodes/${nodeId}`

      await store.dispatch("resourcenode/findResourceNode", {
        id: resourceApiId,
        cid: course.value?.id,
        sid: session.value?.id,
      })
    } catch (e) {
      console.error("[Breadcrumb] failed to load resourceNode", e)
    }
  } else {
    store.commit("resourcenode/ADD_RESOURCE_NODE", null)
  }
}

/**
 * Rebuild the breadcrumb trail for the current route.
 * Must be called only after async data (resource node) has been resolved.
 */
function buildBreadcrumb() {
  const declaredParentCrumbs = buildDeclaredParentCrumbs()

  if (declaredParentCrumbs !== null) {
    calculatedList.value = declaredParentCrumbs
    return
  }

  const manualCrumbs = buildManualCrumbs()

  if (manualCrumbs !== null) {
    calculatedList.value = manualCrumbs
    return
  }

  const prefix = buildStaticCategoryPrefixes()

  if (specialRouteNames.includes(route.name)) {
    calculatedList.value = prefix
    return
  }

  const toolCrumbs = buildToolCrumbs()

  calculatedList.value = [
    ...prefix,
    ...buildCourseContextRootCrumb(),
    ...buildCourseTitleCrumb(),
    ...buildGroupCrumb(),
    ...(toolCrumbs ?? buildRemainingMatchedCrumbs()),
  ]
}

watch(
  () => route.fullPath,
  async () => {
    if ("/" === route.fullPath) {
      return
    }

    await loadResourceNodeIfNeeded()
    buildBreadcrumb()
  },
  { immediate: true },
)

/**
 * Handle a breadcrumb item click.
 *
 * - Legacy URL items (`item.url`): stop the event and perform a hard page navigation.
 * - Vue Router items inside the same whitelisted segment (e.g. `/admin`): also hard-navigate
 *   to preserve full-page reloads where the legacy admin layout requires them.
 * - All other Vue Router items: do nothing and let `BaseAppLink` / Vue Router handle them.
 *
 * @param {{ url?: string, route?: import('vue-router').RouteLocationRaw }} item - The clicked breadcrumb item.
 * @param {MouseEvent} event - The native DOM click event.
 */
function handleBreadcrumbClick(item, event) {
  // Hard navigation for legacy links (outside Vue Router).
  if (item?.url) {
    event?.preventDefault?.()
    event?.stopImmediatePropagation?.()
    window.location.href = item.url

    return
  }

  // If it is not a legacy link, do nothing here and let BaseAppLink / Router handle it.
  if (!item?.route) {
    return
  }

  // Only force hard navigation for specific admin cases (existing behavior).
  const allowedSegments = ["admin"]
  const currentSegment = route.path.split("/").filter(Boolean)[0] || ""

  let resolved

  try {
    resolved = router.resolve(item.route)
  } catch {
    // Avoid throwing in console when a route is not registered.
    return
  }

  const itemSegment = resolved.path.split("/").filter(Boolean)[0] || ""

  if (itemSegment === currentSegment && allowedSegments.includes(itemSegment) && resolved.matched.length === 0) {
    event?.preventDefault?.()
    event?.stopImmediatePropagation?.()
    window.location.href = resolved.href
  }
}
</script>
