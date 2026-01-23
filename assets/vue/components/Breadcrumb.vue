<template>
  <div
    v-if="items.length > 0"
    class="app-breadcrumb"
  >
    <Breadcrumb
      :model="items"
      :home="home"
    >
      <template #item="{ item, props }">
        <BaseIcon
          v-if="item.icon"
          :icon="item.icon"
          size="small"
        />
        <BaseAppLink
          v-if="(item.route || item.url) && item !== items[items.length - 1]"
          :to="item.route"
          :url="item.url"
          v-bind="props.action"
          @click="handleBreadcrumbClick(item, $event)"
        >
          {{ stripHtml(item.label) }}
        </BaseAppLink>
        <span
          v-else
          v-text="stripHtml(item.label)"
          v-bind="props.action"
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
import { computed, ref, watch, watchEffect, onMounted } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import Breadcrumb from "primevue/breadcrumb"
import { useCidReqStore } from "../store/cidReq"
import { storeToRefs } from "pinia"
import { useStore } from "vuex"
import BaseIcon from "./basecomponents/BaseIcon.vue"

const legacyItems = ref([])

const cidReqStore = useCidReqStore()
const route = useRoute()
const router = useRouter()
const { t, te } = useI18n()

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
  if (calculatedList.value.length) {
    return {
      ...calculatedList.value[0],
      icon: "compass",
    }
  }

  return undefined
})

const items = computed(() => {
  if (!calculatedList.value.length) {
    return []
  }

  return calculatedList.value.slice(1)
})

/**
 * Group breadcrumb support (no API calls)
 * - Detect gid in query and insert a group crumb after the course crumb.
 * - Label uses "Group 0001" style to be user-friendly even without fetching the real name.
 */
const gid = computed(() => getQueryInt("gid", 0))

/**
 * Course/session context from query.
 * Why:
 * - Some pages are legacy and do not populate cidReq store immediately.
 * - We still want consistent breadcrumbs if cid/sid are present.
 */
const cid = computed(() => getQueryInt("cid", 0))
const sid = computed(() => getQueryInt("sid", 0))

onMounted(() => {
  const wb = (window && window.breadcrumb) || []
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
 * - Prefer explicit cid/sid query.
 * - Fallback to store data for Vue-only routes like CourseHome.
 */
function isInCourseOrSessionContext() {
  // Explicit legacy context (most reliable)
  if (cid.value > 0 || sid.value > 0) return true

  // Vue-only / SPA context fallback
  const routeName = String(route.name || "")
  if (routeName === "CourseHome") return true

  // If store still has a course, we assume context unless this is a top-level route.
  // This keeps behavior stable for tool pages that might not carry cid in query.
  if (course.value?.id && !specialRouteNames.includes(routeName)) return true

  return false
}

/**
 * Determine whether root crumb should be "My sessions" or "My courses".
 * We consider session context if sid is present OR store session exists.
 */
function isSessionContext() {
  if (sid.value > 0) return true
  const sidStore = Number(session.value?.id || 0)
  return sidStore > 0
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
  if (!isInCourseOrSessionContext()) return

  const rootRouteName = isSessionContext() ? "MySessions" : "MyCourses"
  const rootLabel = t(isSessionContext() ? "My sessions" : "My courses")

  // Avoid duplicates by route name or by label.
  const exists = calculatedList.value.some((it) => {
    const sameRoute = it?.route?.name && it.route.name === rootRouteName
    const sameLabel = stripHtml(it?.label || "") === stripHtml(rootLabel)
    return sameRoute || sameLabel
  })
  if (exists) return

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

  if (route.name === listRouteName) return

  if (resourceNode.value?.title) {
    const resourceLabel = resourceNode.value.title
    const idParam = cleanIdParam(route.params.id)

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

function watchResourceNodeLoader() {
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
}

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

  const labelBase = translateOrFallback("Group", "Group")
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
  const cidRaw = route.query?.cid ?? course.value?.id ?? 0
  const sidRaw = route.query?.sid ?? session.value?.id ?? 0

  const cidStr = String(cidRaw ?? "0")
  const sidStr = String(sidRaw ?? "0")
  const gidStr = String(currentGid)

  const qs = new URLSearchParams()
  qs.set("cid", cidStr)
  qs.set("sid", sidStr)
  qs.set("gid", gidStr)

  return `/main/group/group_space.php?${qs.toString()}`
}

/**
 * Resolve translated label safely.
 */
function translateOrFallback(key, fallback) {
  try {
    if (typeof te === "function" && te(key)) {
      return t(key)
    }
  } catch (e) {}
  return fallback
}

/**
 * Resolve translated label for /admin/settings/:namespace
 */
function resolveSettingsSectionLabel(nsRaw) {
  const ns = String(nsRaw || "").trim()
  // Safer because it's already translated server-side.
  try {
    const current = document.querySelector(".list-group a.bg-gray-25")
    const domText = current?.textContent?.trim()
    if (domText) {
      return domText
    }
  } catch (e) {}

  // i18n candidates
  const candidates = [
    `settings_section.${ns}`,
    `settings_section.${ns.replace(/-/g, "_")}`,
    ns,
    ns.replace(/[-_]/g, " "),
  ]
  for (const key of candidates) {
    const has = typeof te === "function" && te(key)
    if (has) return t(key)
  }

  return ns.replace(/[-_]/g, " ").replace(/\b\w/g, (c) => c.toUpperCase())
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
    } catch (e) {
      return "#"
    }
  }

  // Relative URL like "lp_controller.php?action=..." -> resolve against current page.
  try {
    const resolved = new URL(input, window.location.href)
    const full = resolved.pathname + resolved.search + resolved.hash
    const idx = full.indexOf("main/")
    return idx >= 0 ? "/" + full.substring(idx) : full || "#"
  } catch (e) {
    return "#"
  }
}

// Watch route changes to dynamically rebuild the breadcrumb trail
watchEffect(() => {
  if ("/" === route.fullPath) return
  calculatedList.value = []
  // special-case accessurl routes
  if (/^\/resources\/accessurl\/[^\/]+\/delete(?:\/|$)/.test(route.path)) {
    calculatedList.value = []

    calculatedList.value.push({
      label: t("Administration"),
      url: "/main/admin/index.php",
    })

    calculatedList.value.push({
      label: t("Multiple access URL / Branding"),
      url: "/main/admin/access_urls.php",
    })

    calculatedList.value.push({ label: t("Delete access") })

    return
  }

  if (buildManualBreadcrumbIfNeeded()) return

  // Static route categories (must use "route" or "url" for our slot)
  if (route.name?.includes("Page")) {
    calculatedList.value.push({ label: t("Pages"), route: { path: "/resources/pages" } })
  }
  if (route.name?.includes("Message")) {
    calculatedList.value.push({ label: t("Messages"), route: { path: "/resources/messages" } })
  }

  // Do not build breadcrumb for top-level routes
  if (specialRouteNames.includes(route.name)) return

  // NEW: Always add a consistent root crumb in course/session context.
  // This fixes "My courses/My sessions appears sometimes" inconsistency.
  addCourseContextRootBreadcrumbIfNeeded()

  // Legacy breadcrumb fallback (Twig pages injecting window.breadcrumb)
  if (legacyItems.value.length > 0) {
    legacyItems.value.forEach((item) => {
      const newUrl = normalizeLegacyUrl(item?.url)
      calculatedList.value.push({ label: item.name, url: newUrl || undefined })
    })
    legacyItems.value = []
    return
  }

  // Standard: add course title crumb
  if (course.value && route.name !== "CourseHome") {
    calculatedList.value.push({
      label: course.value.title,
      route: { name: "CourseHome", params: { id: course.value.id }, query: route.query },
    })
  }

  // NEW: Group crumb if gid is present
  addGroupBreadcrumbIfNeeded()

  const mainToolName = route.matched?.[0]?.name
  const currentRouteName = route.name || ""
  const nodeId = route.params.node || route.query.node
  const isAssignmentRoute = currentRouteName.startsWith("Assignment") && resourceNode.value && nodeId
  const isAttendanceRoute = currentRouteName.startsWith("Attendance") && resourceNode.value && nodeId

  // Documents breadcrumb (based on resourceNode hierarchy)
  if (mainToolName === "documents" && resourceNode.value) {
    addDocumentBreadcrumb()
    return
  }

  // Assignments
  if (isAssignmentRoute) {
    addToolWithResourceBreadcrumb("Assignments", "AssignmentsList", "AssignmentDetail")
    return
  }

  // Attendance
  if (isAttendanceRoute) {
    addToolWithResourceBreadcrumb("Attendance", "AttendanceList", "AttendanceSheetList")
    return
  }

  // Generic tool fallback
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
      const alreadyShown = calculatedList.value.some((item) => item.label === finalLabel)
      if (!alreadyShown) {
        calculatedList.value.push({
          label: t(finalLabel),
          route: { name: currentMatched.name, params: route.params, query: route.query },
        })
      }
    }
    return
  }

  // Fallback to route hierarchy
  addRemainingMatchedBreadcrumbs()
})

// Load resourceNode if not already available
watchResourceNodeLoader()

// Extracts numeric ID from route param (e.g., "/api/resource_nodes/123" → 123)
function cleanIdParam(id) {
  if (!id) return undefined
  const match = id.toString().match(/(\d+)$/)
  return match ? id.toString().match(/(\d+)$/)[1] : id
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
    const ns = pathSegments[2] || route.params?.namespace || route.query?.namespace || ""
    const adminLabel = t("Admin")
    calculatedList.value.push({
      label: adminLabel,
      route: { name: overrides.admin, params: route.params, query: route.query },
    })
    calculatedList.value.push({
      label: t("Settings"),
      route: { path: "/admin/settings" },
    })
    const section = resolveSettingsSectionLabel(ns)
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
  } catch (e) {
    // Avoid throwing in console when a route is not registered.
    // console.debug("[Breadcrumb] route resolve failed", e)
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
