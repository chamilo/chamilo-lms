<template>
  <Breadcrumb
    :home="home"
    :model="foo"
    class="app-breadcrumb"
  />
</template>

<script setup>
import { useStore } from "vuex"
import { computed } from "vue"
import { useRoute } from "vue-router"
import { useI18n } from "vue-i18n"
import Breadcrumb from "primevue/breadcrumb"
import { useCidReqStore } from "../store/cidReq"
import { storeToRefs } from "pinia"

// eslint-disable-next-line no-undef
const props = defineProps({
  layoutClass: {
    type: String,
    default: null,
  },
  legacy: {
    type: Array,
    default: () => [],
  },
})

const store = useStore()
const cidReqStore = useCidReqStore()
const route = useRoute()
const { t } = useI18n()

const { course, session } = storeToRefs(cidReqStore)

const resourceNode = computed(() => store.getters["resourcenode/getResourceNode"])

const home = {
  icon: "pi pi-home",
  command: () => {
    window.location.href = '/';
  },
}

const foo = computed(() => {
  const list = [
    "CourseHome",
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

  const items = []

  if (route.name && route.name.includes("Page")) {
    items.push({
      label: t("Pages"),
      to: "/resources/pages",
    })
  }

  if (route.name && route.name.includes("Message")) {
    items.push({
      label: t("Messages"),
      //disabled: route.path === path || lastItem.path === route.path,
      to: "/resources/messages",
    })
  }

  if (list.includes(route.name)) {
    return items
  }

  if (0 < props.legacy.length) {
    const mainUrl = window.location.href
    const mainPath = mainUrl.indexOf("main/")

    props.legacy.forEach((item) => {
      let url = item.url.toString()
      let newUrl = url

      if (url.indexOf("main/") > 0) {
        newUrl = "/" + url.substring(mainPath, url.length)
      }

      if (newUrl === "/") {
        newUrl = "#"
      }

      items.push({
        label: item["name"],
        href: newUrl,
      })
    })
  }

  let queryParams = ""

  Object.keys(route.query)
    .filter((key) => !!key)
    .forEach((key) => {
      if ("" !== queryParams) {
        queryParams += "&"
      }

      queryParams += key + "=" + encodeURIComponent(route.query[key].toString())
    })

  if (course.value) {
    let sessionTitle = ""

    if (session.value) {
      sessionTitle = " (" + session.value.name + ") "
    }

    items.push({
      label: course.value.title + sessionTitle,
      to: "/course/" + course.value.id + "/home?" + queryParams,
    })
  }

  const { path, matched } = route
  const lastItem = matched[matched.length - 1]

  matched.forEach((pathItem) => {
    if (pathItem.path) {
      items.push({
        label: pathItem.name,
        disabled: route.path === path || lastItem.path === route.path,
        href: pathItem.path,
      })
    }
  })

  return items
})
</script>
