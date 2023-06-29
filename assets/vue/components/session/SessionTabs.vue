<template>
  <BaseRouteTabs
    :tabs="sessionTabs"
    :selected-tab="selected"
  />
</template>

<script setup>
import BaseRouteTabs from "../basecomponents/BaseRouteTabs.vue"
import {useI18n} from "vue-i18n"
import {useRoute} from "vue-router";
import {computed} from "vue";

const {t} = useI18n()
const route = useRoute()

const selected = computed(() => {
  switch (route.name) {
    case 'MySessions':
      return 0
    case 'MySessionsUpcoming':
      return 1
    case 'MySessionsPast':
      return 2
    default:
      return 0
  }
})

const sessionTabs = [
  {
    title: t('Current'),
    to: {name: 'MySessions'},
  },
  {
    title: t('Upcoming'),
    to: {name: 'MySessionsUpcoming'},
  },
  {
    title: t('Past'),
    to: {name: 'MySessionsPast'},
  },
]
</script>
