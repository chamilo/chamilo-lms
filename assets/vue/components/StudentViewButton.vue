<template>
  <BaseToggleButton
    v-if="showButton"
    v-model="studentViewState"
    :off-label="t('Switch to student view')"
    :on-label="t('Switch to teacher view')"
    class="studentview-button"
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
import { useNotification } from "../composables/notification"

const { t } = useI18n()
const platformConfigStore = usePlatformConfig()
const cidReqStore = useCidReqStore()
const securityStore = useSecurityStore()
const { isCoach } = useUserSessionSubscription()
const { showErrorNotification } = useNotification()

const studentViewState = computed({
  async set() {
    let isEnabled

    try {
      const response = await permissionService.toogleStudentView()

      isEnabled = response.toLowerCase() === "studentview"
    } catch (e) {
      showErrorNotification(e)

      isEnabled = !platformConfigStore.isStudentViewActive
    }

    platformConfigStore.setStudentViewEnabled(isEnabled)
  },
  get() {
    return platformConfigStore.isStudentViewActive
  },
})

const showButton = computed(
  () =>
    securityStore.isAuthenticated &&
    cidReqStore.course &&
    (securityStore.isCourseAdmin || securityStore.isAdmin || isCoach.value) &&
    platformConfigStore.getSetting("course.student_view_enabled") === "true",
)
</script>
