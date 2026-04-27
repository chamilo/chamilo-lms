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
 * Build a hardcoded breadcrumb trail for the access-URL delete confirmation page.
 * Matches paths of the form `/resources/accessurl/<id>/delete`.
 *
 * @returns {Array|null} Array of crumb items if the path matched; `null` otherwise.
 */
function buildAccessUrlDeleteCrumbs() {
  if (!/^\/resources\/accessurl\/[^/]+\/delete(?:\/|$)/u.test(route.path)) {
    return null
  }

  return [
    { label: t("Administration"), url: "/main/admin/index.php" },
    { label: t("Multiple access URL / Branding"), url: "/main/admin/access_urls.php" },
    { label: t("Delete access") },
  ]
}

/**
 * Build the breadcrumb trail from server-injected legacy items or from whitelisted path segments.
 *
 * Two cases are handled:
 * 1. `window.breadcrumb` was set by the PHP layer — consume those items directly.
 * 2. The URL starts with a whitelisted segment (e.g. `/admin`) that has no matching Vue route —
 *    synthesize crumbs from path segments using the `overrides` map.
 *
 * @returns {Array|null} Array of crumb items if a manual trail was built; `null` to fall through.
 */
function buildManualCrumbs() {
  if (legacyItems.length > 0) {
    const items = []

    if (isInCourseOrSessionContext()) {
      const rootRouteName = session.value?.id ? "MySessions" : "MyCourses"

      items.push({ label: t(session.value?.id ? "My sessions" : "My courses"), route: { name: rootRouteName } })
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
  const rootLabel = t(session.value?.id ? "My sessions" : "My courses")

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
 * Build the Documents section breadcrumb trail by walking the resource-node parent chain.
 *
 * Traverses upward from the current `resourceNode` until it reaches the "courses" root,
 * collecting intermediate folder crumbs along the way. The first folder becomes the
 * Documents list entry point; remaining folders are appended as individual crumbs.
 * Appends the current sub-page label last, unless `route.meta.breadcrumb` is explicitly `""`.
 *
 * @returns {Array} Array of crumb items for the document trail.
 */
function buildDocumentCrumbs() {
  const folderTrail = []

  let current = resourceNode.value

  while (current?.parent && current.parent.title !== "courses") {
    folderTrail.unshift({ label: current.title, nodeId: current.id })
    current = current.parent
  }

  const first = folderTrail.shift()

  const items = [
    {
      label: t("Documents"),
      route: {
        name: "DocumentsList",
        params: first ? { node: first.nodeId } : route.params,
        query: route.query,
      },
    },
  ]

  folderTrail.forEach((folder) => {
    items.push({
      label: folder.label,
      route: { name: "DocumentsList", params: { node: folder.nodeId }, query: route.query },
    })
  })

  const currentMatched = route.matched.find((r) => r.name === route.name)
  const label = currentMatched?.meta?.breadcrumb

  if (label !== "") {
    const finalLabel = label || formatToolName(currentMatched?.name)

    if (!items.some((item) => item.label === finalLabel)) {
      items.push({
        label: t(finalLabel),
        route: { name: currentMatched.name, params: route.params, query: route.query },
      })
    }
  }

  return items
}

/**
 * Build breadcrumbs for a tool that follows the list → detail navigation pattern
 * (e.g. Assignments, Attendance).
 *
 * Returns up to three crumbs in order:
 * 1. The tool list page (always included).
 * 2. The resource node title linking to the detail page (when a resource is loaded).
 * 3. The current sub-page label (when not already on the detail route).
 *
 * @param {string} toolName - Raw tool name used to derive the list label via `formatToolName`.
 * @param {string} listRouteName - Vue Router name for the tool's list page.
 * @param {string} detailRouteName - Vue Router name for the tool's detail page.
 * @returns {Array} Array of crumb items.
 */
function buildToolWithResourceCrumbs(toolName, listRouteName, detailRouteName) {
  const items = [
    {
      label: t(formatToolName(toolName)),
      route: { name: listRouteName, params: { node: course.value.resourceNode.id }, query: route.query },
    },
  ]

  if (route.name === listRouteName) {
    return items
  }

  if (resourceNode.value?.title) {
    const idParam = route.params.id?.toString().match(/(\d+)$/)?.[1]
    items.push({
      label: resourceNode.value.title,
      route: idParam ? { name: detailRouteName, params: { id: idParam }, query: route.query } : undefined,
    })
  }

  const currentMatched = route.matched.find((r) => r.name === route.name)
  const label = currentMatched?.meta?.breadcrumb || formatToolName(route.name)

  if (route.name !== detailRouteName) {
    items.push({ label: t(label), route: { name: route.name, params: route.params, query: route.query } })
  }

  return items
}

/**
 * Dispatch to the appropriate tool-specific crumb builder based on the current route.
 *
 * Handles documents, assignments, attendance, and generic tool routes.
 * For admin resource routes ("rooms", "branches"), prepends an Administration crumb.
 * For `ccalendarevent`, resolves the label dynamically from the `cid`/`gid` query params.
 *
 * @returns {Array|null} Array of crumb items if a builder handled the route; `null` otherwise.
 */
function buildToolCrumbs() {
  const mainToolName = route.matched?.[0]?.name
  const currentRouteName = route.name || ""
  const nodeId = route.params.node || route.query.node
  const isAssignmentRoute = currentRouteName.startsWith("Assignment") && resourceNode.value && nodeId
  const isAttendanceRoute = currentRouteName.startsWith("Attendance") && resourceNode.value && nodeId

  if (mainToolName === "documents" && resourceNode.value) {
    return buildDocumentCrumbs()
  }

  if (isAssignmentRoute) {
    return buildToolWithResourceCrumbs("Assignments", "AssignmentsList", "AssignmentDetail")
  }

  if (isAttendanceRoute) {
    return buildToolWithResourceCrumbs("Attendance", "AttendanceList", "AttendanceSheetList")
  }

  const adminResourceRoutes = ["rooms", "branches"]
  const items = []

  if (adminResourceRoutes.includes(mainToolName)) {
    items.push({ label: t("Administration"), route: { name: "AdminIndex" } })
  }

  if (mainToolName && !["documents", "assignments", "attendance"].includes(mainToolName)) {
    const matchedRoutes = route.matched
    const toolBase = matchedRoutes[0]
    const currentMatched = matchedRoutes[matchedRoutes.length - 1]

    let toolLabel = toolBase.meta?.breadcrumb || formatToolName(mainToolName)

    if (mainToolName === "ccalendarevent") {
      const cidVal = Number(route.query?.cid || 0)
      const gidVal = Number(route.query?.gid || 0)

      toolLabel = gidVal > 0 ? "Group agenda" : cidVal > 0 ? "Agenda" : "Personal agenda"
    }

    items.push({ label: t(toolLabel), route: { name: toolBase.name, params: route.params, query: route.query } })

    const label = currentMatched.meta?.breadcrumb

    if (label !== "") {
      const finalLabel = label || formatToolName(currentMatched.name)

      if (!items.some((item) => item.label === t(finalLabel))) {
        items.push({
          label: t(finalLabel),
          route: { name: currentMatched.name, params: route.params, query: route.query },
        })
      }
    }

    return items
  }

  return items.length > 0 ? items : null
}

/**
 * Build crumbs for every matched route segment beyond the root (index 0).
 * Used as the generic fallback when no tool-specific builder claims the current route.
 *
 * @returns {Array} Array of crumb items.
 */
function buildRemainingMatchedCrumbs() {
  return route.matched.slice(1).reduce((items, r) => {
    const label = r.meta?.breadcrumb || formatToolName(r.name)
    const alreadyHasResource =
      resourceNode.value?.title && items.some((item) => item.label === resourceNode.value.title)

    if (!alreadyHasResource) {
      items.push({ label: t(label), route: { name: r.name, params: route.params, query: route.query } })
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
 * Load the resource node for routes that require it (Documents, Assignments, Attendance).
 * Clears the store for all other routes to prevent stale data from bleeding into the trail.
 */
async function loadResourceNodeIfNeeded() {
  const currentRouteName = route.name || ""
  const nodeId = route.params.node || route.query.node
  const needsNode =
    (currentRouteName.startsWith("Assignment") ||
      currentRouteName.startsWith("Attendance") ||
      currentRouteName.startsWith("Documents")) &&
    nodeId

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
  const accessUrlCrumbs = buildAccessUrlDeleteCrumbs()

  if (accessUrlCrumbs !== null) {
    calculatedList.value = accessUrlCrumbs
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

  if (itemSegment === currentSegment && allowedSegments.includes(itemSegment)) {
    event?.preventDefault?.()
    event?.stopImmediatePropagation?.()
    window.location.href = resolved.href
  }
}
</script>
