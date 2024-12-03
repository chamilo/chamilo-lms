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
import { useCidReqStore } from "../store/cidReq"
import { useSecurityStore } from "../store/securityStore"
import permissionService from "../services/permissionService"
import { useUserSessionSubscription } from "../composables/userPermissions"

const emit = defineEmits(["change"])

const { t } = useI18n()
const platformConfigStore = usePlatformConfig()
const cidReqStore = useCidReqStore()
const securityStore = useSecurityStore()
const { isCoach } = useUserSessionSubscription()

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

const showButton = computed(() => {
  return (
    securityStore.isAuthenticated &&
    cidReqStore.course &&
    (securityStore.isCourseAdmin || securityStore.isAdmin || isCoach.value) &&
    "true" === platformConfigStore.getSetting("course.student_view_enabled")
  )
})
</script>
