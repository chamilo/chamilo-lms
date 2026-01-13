<template>
  <LayoutFormGeneric>
    <template #header>
      <BaseIcon icon="plus" />
      {{ t("Add attendance") }}
    </template>

    <AttendanceForm @back-pressed="goBack" />
  </LayoutFormGeneric>
</template>

<script setup>
import AttendanceForm from "../../components/attendance/AttendanceForm.vue"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import LayoutFormGeneric from "../../components/layout/LayoutFormGeneric.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"

const { t } = useI18n()
const router = useRouter()
const route = useRoute()

const goBack = (query = {}) => {
  router.push({
    name: "AttendanceList",
    params: { node: String(route.params.node) },
    query: {
      ...route.query,
      ...query,
    },
  })
}
</script>
