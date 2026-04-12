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
import { computed, onMounted, ref, watch, watchEffect } from "vue"
import { useRoute, useRouter } from "vue-router"
import { storeToRefs } from "pinia"
import { useStore } from "vuex"
import { useI18n } from "vue-i18n"
import Breadcrumb from "primevue/breadcrumb"
import { useCidReqStore } from "../store/cidReq"
import BaseIcon from "./basecomponents/BaseIcon.vue"

const legacyItems = ref([])

const cidReqStore = useCidReqStore()
const route = useRoute()
const router = useRouter()
const { t } = useI18n()

const { course, session } = storeToRefs(cidReqStore)
const store = useStore()
const resourceNode = computed(() => store.getters["resourcenode/getResourceNode"])

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

const calculatedList = ref([])

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
 * Group breadcrumb support (no API calls)
 * - Detect gid in query and insert a group crumb after the course crumb.
 * - Label uses "Group 0001" style to be user-friendly even without fetching the real name.
 */
const gid = computed(() => getQueryInt("gid", 0))

onMounted(() => {
  const wb = window.breadcrumb
  if (Array.isArray(wb) && wb.length > 0) {
    legacyItems.value = wb
  }
})

const formatToolName = (name) => {
  if (!name) return ""
  return name
    .replace(/([a-z])([A-Z])/g, "$1 $2")
    .replace(/_/g, " ")
    .replace(/\b\w/g, (c) => c.toUpperCase())
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
 * Add the root crumb consistently.
 *
 * Goal:
 * - If we are inside a course/session context, always start breadcrumb with:
 *   "My sessions" (when in session context) or "My courses" (otherwise).
 * - Avoid duplicates when legacy breadcrumbs already include it.
 */
function addCourseContextRootBreadcrumbIfNeeded() {
  if (!isInCourseOrSessionContext()) {
    return
  }

  const rootRouteName = session.value?.id ? "MySessions" : "MyCourses"
  const rootLabel = t(session.value?.id ? "My sessions" : "My courses")

  // Avoid duplicates by route name or by label.
  const exists = calculatedList.value.some((it) => {
    const sameRoute = it?.route?.name && it.route.name === rootRouteName
    const sameLabel = stripHtml(it?.label || "") === stripHtml(rootLabel)

    return sameRoute || sameLabel
  })

  if (exists) {
    return
  }

  calculatedList.value.push({
    label: rootLabel,
    route: { name: rootRouteName },
  })
}

const addToolWithResourceBreadcrumb = (toolName, listRouteName, detailRouteName) => {
  calculatedList.value.push({
    label: t(formatToolName(toolName)),
    route: {
      name: listRouteName,
      params: {
        node: course.value.resourceNode.id,
      },
      query: route.query,
    },
  })

  if (route.name === listRouteName) {
    return
  }

  if (resourceNode.value?.title) {
    const resourceLabel = resourceNode.value.title
    const idParam = route.params.id?.toString().match(/(\d+)$/)?.[1]

    calculatedList.value.push({
      label: resourceLabel,
      route: idParam ? { name: detailRouteName, params: { id: idParam }, query: route.query } : undefined,
    })
  }

  const currentMatched = route.matched.find((r) => r.name === route.name)
  const label = currentMatched?.meta?.breadcrumb || formatToolName(route.name)

  if (route.name !== detailRouteName) {
    calculatedList.value.push({
      label: t(label),
      route: { name: route.name, params: route.params, query: route.query },
    })
  }
}

function addRemainingMatchedBreadcrumbs() {
  route.matched.slice(1).forEach((r) => {
    const label = r.meta?.breadcrumb || formatToolName(r.name)
    const alreadyHasResource =
      resourceNode.value?.title && calculatedList.value.some((item) => item.label === resourceNode.value.title)

    if (!alreadyHasResource) {
      calculatedList.value.push({
        label: t(label),
        route: {
          name: r.name,
          params: route.params,
          query: route.query,
        },
      })
    }
  })
}

watch(
  () => route.fullPath,
  async () => {
    const currentRouteName = route.name || ""
    const isAssignmentRoute = currentRouteName.startsWith("Assignment")
    const isAttendanceRoute = currentRouteName.startsWith("Attendance")
    const isDocumentRoute = currentRouteName.startsWith("Documents")
    const nodeId = route.params.node || route.query.node

    if ((isAssignmentRoute || isAttendanceRoute || isDocumentRoute) && nodeId) {
      try {
        store.commit("resourcenode/setResourceNode", null)
        const resourceApiId = nodeId.startsWith("/api/") ? nodeId : `/api/resource_nodes/${nodeId}`

        await store.dispatch("resourcenode/findResourceNode", {
          id: resourceApiId,
          cid: course.value?.id,
          sid: session.value?.id,
        })
      } catch (e) {
        console.error("[Breadcrumb] failed to load resourceNode", e)
      }
    }
  },
  { immediate: true },
)

function addDocumentBreadcrumb() {
  const folderTrail = []
  let current = resourceNode.value
  while (current?.parent && current.parent.title !== "courses") {
    folderTrail.unshift({ label: current.title, nodeId: current.id })
    current = current.parent
  }
  const first = folderTrail.shift()
  calculatedList.value.push({
    label: t("Documents"),
    route: {
      name: "DocumentsList",
      params: first ? { node: first.nodeId } : route.params,
      query: route.query,
    },
  })
  folderTrail.forEach((folder) => {
    calculatedList.value.push({
      label: folder.label,
      route: { name: "DocumentsList", params: { node: folder.nodeId }, query: route.query },
    })
  })

  const currentMatched = route.matched.find((r) => r.name === route.name)
  const label = currentMatched?.meta?.breadcrumb
  if (label !== "") {
    const finalLabel = label || formatToolName(currentMatched?.name)
    const alreadyShown = calculatedList.value.some((item) => item.label === finalLabel)
    if (!alreadyShown) {
      calculatedList.value.push({
        label: t(finalLabel),
        route: { name: currentMatched.name, params: route.params, query: route.query },
      })
    }
  }
}

/**
 * Insert a Group crumb when we are inside a group context (gid=...).
 * This appears after the course title crumb and before the tool crumb.
 * No API calls: we show "Group 0001" style label.
 */
function addGroupBreadcrumbIfNeeded() {
  const currentGid = gid.value
  if (!currentGid || currentGid <= 0) return

  const labelBase = t("Group")
  const padded = String(currentGid).padStart(4, "0")
  const label = `${labelBase} ${padded}`

  // Avoid duplicates (e.g. if some legacy breadcrumb already includes it)
  const alreadyExists = calculatedList.value.some((it) => stripHtml(it.label) === stripHtml(label))
  if (alreadyExists) return

  // Legacy group space URL, works even if there is no Vue route for group space
  const url = buildGroupSpaceUrl(currentGid)

  calculatedList.value.push({
    label,
    url: url || undefined,
  })
}

/**
 * Build a legacy group space URL. Adjust this path if your installation uses a different entry point.
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
 * Resolve translated label for /admin/settings/:namespace
 */
function resolveSettingsSectionLabel() {
  // Safer because it's already translated server-side.
  const current = document.querySelector(".admin-settings__list a.admin-settings__item--active")
  const domText = current?.textContent?.trim()

  if (domText) {
    return domText
  }
}

/**
 * Normalize a legacy URL coming from window.breadcrumb.
 *
 * Why:
 * - Some legacy crumbs provide absolute URLs, some provide "/main/..." paths,
 *   and some provide relative URLs like "lp_controller.php?...".
 * - Previous implementation incorrectly used "main/" index from window.location.href
 *   to slice item.url, which can truncate URLs into "/php?..." or "/on=...".
 *
 * Strategy:
 * 1) If we can find "main/" inside the same string, slice using its own index.
 * 2) If it's a relative path (no leading "/" and no scheme), resolve against current location.
 * 3) If everything fails, return "#" to avoid broken navigation.
 */
function normalizeLegacyUrl(rawUrl) {
  const input = (rawUrl || "").toString().trim()
  if (!input) return "#"

  // Keep anchors and javascript pseudo-links safe.
  if (input === "#" || input.startsWith("javascript:")) return "#"

  // If this is already a site-absolute path, normalize to start at "/main/..." when possible.
  if (input.startsWith("/")) {
    const idx = input.indexOf("main/")
    if (idx >= 0) return "/" + input.substring(idx)
    return input
  }

  // If this is an absolute URL, normalize to "/main/..." when possible.
  if (/^https?:\/\//i.test(input)) {
    try {
      const u = new URL(input)
      const full = u.pathname + u.search + u.hash
      const idx = full.indexOf("main/")
      return idx >= 0 ? "/" + full.substring(idx) : full || "#"
    } catch {
      return "#"
    }
  }

  // Relative URL like "lp_controller.php?action=..." -> resolve against current page.
  try {
    const resolved = new URL(input, window.location.href)
    const full = resolved.pathname + resolved.search + resolved.hash
    const idx = full.indexOf("main/")
    return idx >= 0 ? "/" + full.substring(idx) : full || "#"
  } catch {
    return "#"
  }
}

// Watch route changes to dynamically rebuild the breadcrumb trail
watchEffect(() => {
  if ("/" === route.fullPath) {
    return
  }

  calculatedList.value = []

  if (buildAccessUrlDeleteBreadcrumb()) {
    return
  }

  if (buildManualBreadcrumbIfNeeded()) {
    return
  }

  addStaticCategoryPrefixes()

  if (specialRouteNames.includes(route.name)) {
    return
  }

  addCourseContextRootBreadcrumbIfNeeded()
  addCourseTitleCrumb()
  addGroupBreadcrumbIfNeeded()

  if (buildToolBreadcrumb()) {
    return
  }

  addRemainingMatchedBreadcrumbs()
})

function buildAccessUrlDeleteBreadcrumb() {
  if (!/^\/resources\/accessurl\/[^/]+\/delete(?:\/|$)/u.test(route.path)) {
    return false
  }

  calculatedList.value.push({
    label: t("Administration"),
    url: "/main/admin/index.php",
  })
  calculatedList.value.push({
    label: t("Multiple access URL / Branding"),
    url: "/main/admin/access_urls.php",
  })
  calculatedList.value.push({ label: t("Delete access") })

  return true
}

function addStaticCategoryPrefixes() {
  if (route.name?.includes("Page")) {
    calculatedList.value.push({ label: t("Pages"), route: { path: "/resources/pages" } })
  }
  if (route.name?.includes("Message")) {
    calculatedList.value.push({ label: t("Messages"), route: { path: "/resources/messages" } })
  }
}

function addCourseTitleCrumb() {
  if (course.value && route.name !== "CourseHome") {
    calculatedList.value.push({
      label: course.value.title,
      route: { name: "CourseHome", params: { id: course.value.id }, query: route.query },
    })
  }
}

function buildToolBreadcrumb() {
  const mainToolName = route.matched?.[0]?.name
  const currentRouteName = route.name || ""
  const nodeId = route.params.node || route.query.node
  const isAssignmentRoute = currentRouteName.startsWith("Assignment") && resourceNode.value && nodeId
  const isAttendanceRoute = currentRouteName.startsWith("Attendance") && resourceNode.value && nodeId

  if (mainToolName === "documents" && resourceNode.value) {
    addDocumentBreadcrumb()

    return true
  }

  if (isAssignmentRoute) {
    addToolWithResourceBreadcrumb("Assignments", "AssignmentsList", "AssignmentDetail")

    return true
  }

  if (isAttendanceRoute) {
    addToolWithResourceBreadcrumb("Attendance", "AttendanceList", "AttendanceSheetList")

    return true
  }

  const adminResourceRoutes = ["rooms", "branches"]

  if (adminResourceRoutes.includes(mainToolName)) {
    calculatedList.value.push({
      label: t("Administration"),
      route: { name: "AdminIndex" },
    })
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

    calculatedList.value.push({
      label: t(toolLabel),
      route: { name: toolBase.name, params: route.params, query: route.query },
    })

    const label = currentMatched.meta?.breadcrumb

    if (label !== "") {
      const finalLabel = label || formatToolName(currentMatched.name)
      const alreadyShown = calculatedList.value.some((item) => item.label === t(finalLabel))

      if (!alreadyShown) {
        calculatedList.value.push({
          label: t(finalLabel),
          route: { name: currentMatched.name, params: route.params, query: route.query },
        })
      }
    }
    return true
  }

  return false
}

function buildManualBreadcrumbIfNeeded() {
  // If server already injected legacy breadcrumbs, use them.
  // We still inject the root crumb first (consistency).
  if (Array.isArray(legacyItems.value) && legacyItems.value.length > 0) {
    addCourseContextRootBreadcrumbIfNeeded()

    legacyItems.value.forEach((item) => {
      const newUrl = normalizeLegacyUrl(item?.url)
      calculatedList.value.push({ label: item.name, url: newUrl || undefined })
    })
    legacyItems.value = []
    return true
  }

  const whitelist = ["admin"]
  const overrides = {
    admin: "AdminIndex",
    gdpr: null,
  }
  const pathSegments = route.path.split("/").filter(Boolean)
  const baseSegment = pathSegments[0]

  if (!whitelist.includes(baseSegment)) {
    return false
  }

  // /admin/settings/<namespace>
  const isAdminSettings = pathSegments[1] === "settings"
  if (isAdminSettings) {
    const adminLabel = t("Admin")
    calculatedList.value.push({
      label: adminLabel,
      route: { name: overrides.admin, params: route.params, query: route.query },
    })
    calculatedList.value.push({
      label: t("Settings"),
      route: { path: "/admin/settings" },
    })
    const section = resolveSettingsSectionLabel()
    calculatedList.value.push({ label: section })
    return true
  }

  const fullPath = "/" + pathSegments.join("/")
  const hasMatchedRoute = router.getRoutes().some((r) => r.path === fullPath)

  if (hasMatchedRoute) {
    return false
  }

  pathSegments.forEach((segment, index) => {
    const label = t(segment.charAt(0).toUpperCase() + segment.slice(1))
    const override = overrides[segment]
    if (override === null) {
      calculatedList.value.push({ label })
    } else if (override) {
      calculatedList.value.push({
        label,
        route: { name: override, params: route.params, query: route.query },
      })
    } else {
      const partialPath = "/" + pathSegments.slice(0, index + 1).join("/")
      calculatedList.value.push({
        label,
        route: { path: partialPath },
      })
    }
  })

  return true
}

function handleBreadcrumbClick(item, event) {
  // Hard navigation for legacy links (outside Vue Router).
  if (item?.url) {
    event?.preventDefault?.()
    event?.stopImmediatePropagation?.()
    window.location.href = item.url
    return
  }

  // If it is not a legacy link, do nothing here and let BaseAppLink / Router handle it.
  if (!item?.route) return

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

function stripHtml(value) {
  if (!value || typeof value !== "string") return ""
  return value.replace(/<[^>]*>?/gm, "").trim()
}

/**
 * Safe integer query getter (keeps "0" as valid).
 */
function getQueryInt(key, fallback = 0) {
  const raw = route.query?.[key]
  if (raw === undefined || raw === null || raw === "") return fallback
  const n = Number(Array.isArray(raw) ? raw[0] : raw)
  return Number.isFinite(n) ? n : fallback
}
</script>
