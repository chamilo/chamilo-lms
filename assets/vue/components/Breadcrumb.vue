<template>
  <div
    v-show="itemList.length > 0"
    class="app-breadcrumb"
  >
    <Breadcrumb
      :model="itemList"
      unstyled
    >
      <template #item="{ item, props }">
        <router-link
          v-if="item.route"
          v-slot="{ href, navigate }"
          :to="item.route"
          custom
        >
          <a
            :href="href"
            v-bind="props.action"
            @click="navigate"
          >
            <span :class="[item.icon]" />
            <span
              v-if="item.label"
              v-text="item.label"
            />
          </a>
        </router-link>
        <a
          v-else
          :href="item.url !== '#' ? item.url : undefined"
          v-bind="props.action"
        >
          <span>{{ item.label }}</span>
        </a>
      </template>

      <template #separator> / </template>
    </Breadcrumb>
    <div
      v-if="session"
      class="app-breadcrumb__session-title"
      v-text="session.title"
    />
  </div>
</template>

<script setup>
import { ref, watch } from "vue"
import { useRoute } from "vue-router"
import { useI18n } from "vue-i18n"
import Breadcrumb from "primevue/breadcrumb"
import { useCidReqStore } from "../store/cidReq"
import { storeToRefs } from "pinia"

const legacyItems = ref(window.breadcrumb)

const cidReqStore = useCidReqStore()
const route = useRoute()
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

watch(
  route,
  () => {
    if ("/" === route.fullPath) {
      return
    }

    itemList.value = []

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
      if (course.value) {
        itemList.value.push({
          label: course.value.title,
          route: { name: "CourseHome", params: { id: course.value.id }, query: route.query },
        })
      }
    }
  },
  {
    immediate: true,
  },
)
</script>
