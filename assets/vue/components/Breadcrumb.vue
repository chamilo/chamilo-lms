<template>
  <div
    v-if="itemList.length > 0"
    class="app-breadcrumb"
  >
    <Breadcrumb :model="itemList">
      <template #item="{ item, props }">
        <BaseAppLink
          v-if="(item.route || item.url) && item !== itemList[itemList.length - 1]"
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
        ></span>
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

const itemList = ref([])

/**
 * Group breadcrumb support (no API calls)
 * - Detect gid in query and insert a group crumb after the course crumb.
 * - Label uses "Group 0001" style to be user-friendly even without fetching the real name.
 */
const gid = computed(() => getQueryInt("gid", 0))

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

const addToolWithResourceBreadcrumb = (toolName, listRouteName, detailRouteName) => {
  itemList.value.push({
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

    itemList.value.push({
      label: resourceLabel,
      route: idParam ? { name: detailRouteName, params: { id: idParam }, query: route.query } : undefined,
    })
  }

  const currentMatched = route.matched.find((r) => r.name === route.name)
  const label = currentMatched?.meta?.breadcrumb || formatToolName(route.name)

  if (route.name !== detailRouteName) {
    itemList.value.push({
      label: t(label),
      route: { name: route.name, params: route.params, query: route.query },
    })
  }
}

function addRemainingMatchedBreadcrumbs() {
  route.matched.slice(1).forEach((r) => {
    const label = r.meta?.breadcrumb || formatToolName(r.name)
    const alreadyHasResource =
      resourceNode.value?.title && itemList.value.some((item) => item.label === resourceNode.value.title)

    if (!alreadyHasResource) {
      itemList.value.push({
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
  itemList.value.push({
    label: t("Documents"),
    route: {
      name: "DocumentsList",
      params: first ? { node: first.nodeId } : route.params,
      query: route.query,
    },
  })
  folderTrail.forEach((folder) => {
    itemList.value.push({
      label: folder.label,
      route: { name: "DocumentsList", params: { node: folder.nodeId }, query: route.query },
    })
  })

  const currentMatched = route.matched.find((r) => r.name === route.name)
  const label = currentMatched?.meta?.breadcrumb
  if (label !== "") {
    const finalLabel = label || formatToolName(currentMatched?.name)
    const alreadyShown = itemList.value.some((item) => item.label === finalLabel)
    if (!alreadyShown) {
      itemList.value.push({
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
  const alreadyExists = itemList.value.some((it) => stripHtml(it.label) === stripHtml(label))
  if (alreadyExists) return

  // Legacy group space URL, works even if there is no Vue route for group space
  const url = buildGroupSpaceUrl(currentGid)

  itemList.value.push({
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

  const cid = String(cidRaw ?? "0")
  const sid = String(sidRaw ?? "0")
  const gidStr = String(currentGid)

  const qs = new URLSearchParams()
  qs.set("cid", cid)
  qs.set("sid", sid)
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

// Watch route changes to dynamically rebuild the breadcrumb trail
watchEffect(() => {
  if ("/" === route.fullPath) return
  itemList.value = []
// special-case accessurl routes
  if (/^\/resources\/accessurl\/[^\/]+\/delete(?:\/|$)/.test(route.path)) {
    itemList.value = []

    itemList.value.push({
      label: t("Administration"),
      url: "/main/admin/index.php",
    })

    itemList.value.push({
      label: t("Multiple access URL / Branding"),
      url: "/main/admin/access_urls.php",
    })

    itemList.value.push({ label: t("Delete access") })

    return
  }

  if (buildManualBreadcrumbIfNeeded()) return

  // Static route categories (must use "route" or "url" for our slot)
  if (route.name?.includes("Page")) {
    itemList.value.push({ label: t("Pages"), route: { path: "/resources/pages" } })
  }
  if (route.name?.includes("Message")) {
    itemList.value.push({ label: t("Messages"), route: { path: "/resources/messages" } })
  }

  // Do not build breadcrumb for top-level routes
  if (specialRouteNames.includes(route.name)) return

  // Add course or session link
  if (course.value) {
    itemList.value.push({
      label: t(session.value ? "My sessions" : "My courses"),
      route: { name: session.value ? "MySessions" : "MyCourses" },
    })
  }

  // Legacy breadcrumb fallback (Twig pages injecting window.breadcrumb)
  if (legacyItems.value.length > 0) {
    const mainUrl = window.location.href
    const mainPath = mainUrl.indexOf("main/")
    legacyItems.value.forEach((item) => {
      let newUrl = (item.url || "").toString()
      if (newUrl.indexOf("main/") > 0) newUrl = "/" + newUrl.substring(mainPath)
      if (newUrl === "/") newUrl = "#"
      itemList.value.push({ label: item.name, url: newUrl || undefined })
    })
    legacyItems.value = []
    return
  }

  // Standard: add course title crumb
  if (course.value && route.name !== "CourseHome") {
    itemList.value.push({
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
      const cid = Number(route.query?.cid || 0)
      const gidVal = Number(route.query?.gid || 0)
      toolLabel = gidVal > 0 ? "Group agenda" : cid > 0 ? "Agenda" : "Personal agenda"
    }
    itemList.value.push({
      label: t(toolLabel),
      route: { name: toolBase.name, params: route.params, query: route.query },
    })

    const label = currentMatched.meta?.breadcrumb
    if (label !== "") {
      const finalLabel = label || formatToolName(currentMatched.name)
      const alreadyShown = itemList.value.some((item) => item.label === finalLabel)
      if (!alreadyShown) {
        itemList.value.push({
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
  if (Array.isArray(legacyItems.value) && legacyItems.value.length > 0) {
    const mainUrl = window.location.href
    const mainPath = mainUrl.indexOf("main/")
    legacyItems.value.forEach((item) => {
      let newUrl = (item.url || "").toString()
      if (newUrl.indexOf("main/") > 0) newUrl = "/" + newUrl.substring(mainPath)
      if (newUrl === "/") newUrl = "#"
      itemList.value.push({ label: item.name, url: newUrl || undefined })
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
    itemList.value.push({
      label: adminLabel,
      route: { name: overrides.admin, params: route.params, query: route.query },
    })
    itemList.value.push({
      label: t("Settings"),
      route: { path: "/admin/settings" },
    })
    const section = resolveSettingsSectionLabel(ns)
    itemList.value.push({ label: section })
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
      itemList.value.push({ label })
    } else if (override) {
      itemList.value.push({
        label,
        route: { name: override, params: route.params, query: route.query },
      })
    } else {
      const partialPath = "/" + pathSegments.slice(0, index + 1).join("/")
      itemList.value.push({
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

function buildSkillBreadcrumbIfNeeded() {
  // Only for the skill module (and especially /skill/wheel)
  if (!route.path?.startsWith("/skill")) return false

  const origin = getSafeOriginPath()
  const isSocial = origin.includes("/social")

  const rootLabel = isSocial
    ? translateOrFallback("Social network", "Social network")
    : translateOrFallback("Admin", "Admin")

  const rootUrl = origin || (isSocial ? "/social" : "/admin")

  itemList.value.push({
    label: rootLabel,
    url: rootUrl,
  })

  // Final crumb label
  const lastLabel =
    route.name === "SkillWheel" ? translateOrFallback("Skills wheel", "Skills wheel") : formatToolName(route.name)

  itemList.value.push({ label: lastLabel })

  return true
}

function getSafeOriginPath() {
  const origin = route.query?.origin
  if (typeof origin !== "string" || !origin) return ""

  // Allow only same-site absolute paths (legacy PHP or Vue paths)
  if (!origin.startsWith("/")) return ""
  if (origin.startsWith("//")) return ""
  if (origin.includes("://")) return ""

  return origin
}
</script>
