<script setup>
import BaseRouteTabs from "../basecomponents/BaseRouteTabs.vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import { computed } from "vue"

const { t } = useI18n()
const route = useRoute()

const selected = computed(() => {
  switch (route.name) {
    case "MySessions":
      return 0
    case "MySessionsUpcoming":
      return 1
    case "MySessionsPast":
      return 2
    default:
      return 0
  }
})

const sessionTabs = [
  {
    title: t("Current session tab"),
    to: { name: "MySessions" },
  },
  {
    title: t("Upcoming session tab"),
    to: { name: "MySessionsUpcoming" },
  },
  {
    title: t("Past session tab"),
    to: { name: "MySessionsPast" },
  },
]
</script>

<template>
  <BaseRouteTabs
    :tabs="sessionTabs"
    :selected-tab="selected"
  />
</template>
