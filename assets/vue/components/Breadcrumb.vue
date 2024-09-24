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
import { ref, watchEffect } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import Breadcrumb from "primevue/breadcrumb"
import { useCidReqStore } from "../store/cidReq"
import { storeToRefs } from "pinia"
import BaseAppLink from "./basecomponents/BaseAppLink.vue"

const legacyItems = ref(window.breadcrumb)

const cidReqStore = useCidReqStore()
const route = useRoute()
const router = useRouter()
const { t } = useI18n()

const { course, session } = storeToRefs(cidReqStore)

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

  if (route.fullPath.startsWith("/admin")) {
    const parts = route.path.split("/").filter(Boolean)
    parts.forEach((part, index) => {
      const path = `/${parts.slice(0, index + 1).join("/")}`
      const matchedRoute = router.getRoutes().find(r => r.path === path)
      if (matchedRoute) {
        const label = matchedRoute.meta?.breadcrumb || t(part.charAt(0).toUpperCase() + part.slice(1))
        itemList.value.push({
          label: t(label),
          route: { path },
        })
      }
    })
  }

  if (route.name && route.name.includes("Page")) {
    itemList.value.push({
      label: t("Pages"),
      to: "/resources/pages",
    })
  }

  if (route.name && route.name.includes("Message")) {
    itemList.value.push({
      label: t("Messages"),
      //disabled: route.path === path || lastItem.path === route.path,
      to: "/resources/messages",
    })
  }

  if (specialRouteNames.includes(route.name)) {
    return
  }
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
})
</script>
