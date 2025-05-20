<template>
  <div
    v-if="itemList.length > 0"
    class="app-breadcrumb"
  >
    <Breadcrumb :model="itemList">
      <template #item="{ item, props }">
        <BaseAppLink
          :to="item.route"
          :url="item.url"
          v-bind="props.action"
        >
          {{ item.label }}
        </BaseAppLink>
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
import { computed, ref, watchEffect } from "vue"
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

watchEffect(() => {
  if ("/" === route.fullPath) {
    return
  }

  itemList.value = []

  // Admin routes
  if (route.fullPath.startsWith("/admin")) {
    const parts = route.path.split("/").filter(Boolean)
    parts.forEach((part, index) => {
      const path = `/${parts.slice(0, index + 1).join("/")}`
      const matchedRoute = router.getRoutes().find((r) => r.path === path)
      if (matchedRoute) {
        const label = matchedRoute.meta?.breadcrumb || t(part.charAt(0).toUpperCase() + part.slice(1))
        itemList.value.push({
          label: t(label),
          route: { path },
        })
      }
    })
  }

  // Pages
  if (route.name && route.name.includes("Page")) {
    itemList.value.push({
      label: t("Pages"),
      to: "/resources/pages",
    })
  }

  // Messages
  if (route.name && route.name.includes("Message")) {
    itemList.value.push({
      label: t("Messages"),
      to: "/resources/messages",
    })
  }

  // Home and special
  if (specialRouteNames.includes(route.name)) {
    return
  }

  // My Courses or My Sessions
  if (course.value) {
    if (session.value) {
      itemList.value.push({
        label: t("My sessions"),
        route: { name: "MySessions" },
      })
    } else {
      itemList.value.push({
        label: t("My courses"),
        route: { name: "MyCourses" },
      })
    }
  }

  // Legacy breadcrumbs
  if (legacyItems.value.length > 0) {
    const mainUrl = window.location.href
    const mainPath = mainUrl.indexOf("main/")

    legacyItems.value.forEach((item) => {
      let url = item.url.toString()
      let newUrl = url

      if (url.indexOf("main/") > 0) {
        newUrl = "/" + url.substring(mainPath, url.length)
      }

      if (newUrl === "/") {
        newUrl = "#"
      }

      itemList.value.push({
        label: item["name"],
        url: newUrl,
      })
    })

    legacyItems.value = []
  } else {
    if (course.value && "CourseHome" !== route.name) {
      itemList.value.push({
        label: course.value.title,
        route: { name: "CourseHome", params: { id: course.value.id }, query: route.query },
      })
    }
  }

  const mainToolName = route.matched?.[0]?.name
  if (mainToolName && mainToolName !== "documents") {
    const formatToolName = (name) => {
      return name
        .replace(/([a-z])([A-Z])/g, "$1 $2")
        .replace(/_/g, " ")
        .replace(/\b\w/g, (c) => c.toUpperCase())
    }

    itemList.value.push({
      label: formatToolName(mainToolName),
      route: { name: mainToolName, params: route.params, query: route.query },
    })
  }
  if (mainToolName === "documents" && resourceNode.value) {
    const folderTrail = []

    let current = resourceNode.value
    while (current?.parent && current.parent.title !== "courses") {
      folderTrail.unshift({
        label: current.title,
        nodeId: current.id,
      })
      current = current.parent
    }

    if (folderTrail.length === 0) {
      itemList.value.push({
        label: t("Documents"),
        route: {
          name: "DocumentsList",
          params: route.params,
          query: route.query,
        },
      })
    } else {
      const first = folderTrail.shift()
      itemList.value.push({
        label: t("Documents"),
        route: {
          name: "DocumentsList",
          params: { node: first.nodeId },
          query: route.query,
        },
      })

      folderTrail.forEach((folder) => {
        itemList.value.push({
          label: folder.label,
          route: {
            name: "DocumentsList",
            params: { node: folder.nodeId },
            query: route.query,
          },
        })
      })
    }
  }
})
</script>
