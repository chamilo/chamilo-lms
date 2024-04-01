<template>
  <BaseToggleButton
    v-if="showButton"
    v-model="isStudentView"
    :off-label="t('Switch to student view')"
    :on-label="t('Switch to teacher view')"
    off-icon="eye-off"
    on-icon="eye-on"
  />
</template>

<script setup>
import BaseToggleButton from "./basecomponents/BaseToggleButton.vue"
import { computed } from "vue"
import { useI18n } from "vue-i18n"
import { usePlatformConfig } from "../store/platformConfig"
import { storeToRefs } from "pinia"
import { useCidReqStore } from "../store/cidReq"
import { useSecurityStore } from "../store/securityStore"
import permissionService from "../services/permissionService"

const emit = defineEmits(["change"])

const { t } = useI18n()
const platformConfigStore = usePlatformConfig()
const cidReqStore = useCidReqStore()
const securityStore = useSecurityStore()

const isStudentView = computed({
  async set() {
    const studentView = await permissionService.toogleStudentView()

    platformConfigStore.studentView = studentView

    emit("change", studentView)
  },
  get() {
    return platformConfigStore.isStudentViewActive
  },
})

const { course, userIsCoach } = storeToRefs(cidReqStore)

const showButton = computed(() => {
  return (
    securityStore.isAuthenticated &&
    course.value &&
    (securityStore.isCourseAdmin || securityStore.isAdmin || userIsCoach.value(securityStore.user.id, 0, false)) &&
    "true" === platformConfigStore.getSetting("course.student_view_enabled")
  )
})
</script>
