<template>
  <div
    v-if="itemList.length > 0"
    class="app-breadcrumb"
  >
    <Breadcrumb :model="itemList">
      <template #item="{ item, props }">
        <BaseAppLink
          v-if="(item.route || item.url) && item !== itemList[itemList.length - 1]"          :to="item.route"
          :url="item.url"
          v-bind="props.action"
          @click="handleBreadcrumbClick(item)"
        >
          {{ item.label }}
        </BaseAppLink>
        <span v-else>{{ item.label }}</span>
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
import { computed, ref, watch, watchEffect } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import Breadcrumb from "primevue/breadcrumb"
import { useCidReqStore } from "../store/cidReq"
import { storeToRefs } from "pinia"
import { useStore } from "vuex"

const legacyItems = ref(window.breadcrumb)

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

const itemList = ref([])

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
          console.error("[Breadcrumb WATCH] failed to load resourceNode", e)
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
}

// Watch route changes to dynamically rebuild the breadcrumb trail
watchEffect(() => {
  if ("/" === route.fullPath) return
  itemList.value = []

  if (buildManualBreadcrumbIfNeeded()) return

  // Static route categories
  if (route.name?.includes("Page")) {
    itemList.value.push({ label: t("Pages"), to: "/resources/pages" })
  }
  if (route.name?.includes("Message")) {
    itemList.value.push({ label: t("Messages"), to: "/resources/messages" })
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

  // Legacy breadcrumb fallback (main/legacy urls)
  if (legacyItems.value.length > 0) {
    const mainUrl = window.location.href
    const mainPath = mainUrl.indexOf("main/")
    legacyItems.value.forEach((item) => {
      let newUrl = item.url.toString()
      if (newUrl.indexOf("main/") > 0) newUrl = "/" + newUrl.substring(mainPath)
      if (newUrl === "/") newUrl = "#"
      itemList.value.push({ label: item.name, url: newUrl })
    })
    legacyItems.value = []
  } else if (course.value && route.name !== "CourseHome") {
    itemList.value.push({
      label: course.value.title,
      route: { name: "CourseHome", params: { id: course.value.id }, query: route.query },
    })
  }

  // Detect and render tool-specific breadcrumb
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

    let toolLabel = formatToolName(mainToolName)
    if (mainToolName === "ccalendarevent") {
      const cid = Number(route.query?.cid || 0)
      const gid = Number(route.query?.gid || 0)
      toolLabel = gid > 0 ? "Group agenda" : (cid > 0 ? "Agenda" : "Personal agenda")
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
  return match ? match[1] : id
}

function buildManualBreadcrumbIfNeeded() {
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

function handleBreadcrumbClick(item) {
  const allowedSegments = ["admin"]
  const currentSegment = route.path.split("/").filter(Boolean)[0]
  const itemSegment = router.resolve(item.route).path.split("/").filter(Boolean)[0]

  if (itemSegment === currentSegment && allowedSegments.includes(itemSegment)) {
    window.location.href = router.resolve(item.route).href
  }
}
</script>
