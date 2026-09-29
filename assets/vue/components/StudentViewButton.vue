<template>
  <BaseToggleButton
    v-if="showButton"
    :model-value="platformConfigStore.isStudentViewActive"
    :off-label="t('Switch to student view')"
    :on-label="t('Switch to teacher view')"
    class="studentview-button"
    off-icon="eye-off"
    on-icon="eye-on"
    @update:model-value="setStudentView"
  />
</template>

<script setup>
import BaseToggleButton from "./basecomponents/BaseToggleButton.vue"
import { computed, ref } from "vue"
import { useI18n } from "vue-i18n"
import { usePlatformConfig } from "../store/platformConfig"
import { useCidReqStore } from "../store/cidReq"
import { useSecurityStore } from "../store/securityStore"
import permissionService from "../services/permissionService"
import { useNotification } from "../composables/notification"

const { t } = useI18n()
const platformConfigStore = usePlatformConfig()
const cidReqStore = useCidReqStore()
const securityStore = useSecurityStore()
const { showErrorNotification } = useNotification()

const isToggling = ref(false)

/**
 * Sets the student view to the requested state.
 *
 * The target value is sent instead of letting the backend flip, so two fast
 * clicks cannot race each other into opposite states. On failure the store is
 * left untouched: the server session did not change, and mirroring a flip that
 * never happened would leave the UI in student view while the backend answers
 * as teacher.
 * @param {boolean} next
 * @returns {Promise<void>}
 */
async function setStudentView(next) {
  if (isToggling.value) {
    return
  }

  isToggling.value = true

  try {
    const response = await permissionService.toogleStudentView({
      cid: cidReqStore.course?.id,
      sid: cidReqStore.session?.id,
      isStudentView: next,
    })

    platformConfigStore.setStudentViewEnabled("studentview" === String(response).toLowerCase())
  } catch (e) {
    showErrorNotification(e)
  } finally {
    isToggling.value = false
  }
}

// isCourseAdmin is exactly the backend gate of /toggle_student_view
// (ROLE_ADMIN | ROLE_CURRENT_COURSE_TEACHER | ROLE_CURRENT_COURSE_SESSION_TEACHER). It stays
// true inside the student view on purpose, so the switch back remains reachable.
const showButton = computed(
  () =>
    securityStore.isAuthenticated &&
    cidReqStore.course &&
    securityStore.isCourseAdmin &&
    "true" === platformConfigStore.getSetting("course.student_view_enabled"),
)
</script>
