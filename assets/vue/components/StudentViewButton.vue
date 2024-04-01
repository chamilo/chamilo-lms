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
import { useStore } from "vuex"
import { usePlatformConfig } from "../store/platformConfig"
import { storeToRefs } from "pinia"
import { useCidReqStore } from "../store/cidReq"
import { useSecurityStore } from "../store/securityStore"
import permissionService from "../services/permissionService"

const emit = defineEmits(["change"])

const store = useStore()
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

const isCourseAdmin = computed(() => store.getters["security/isCourseAdmin"])
const isAdmin = computed(() => store.getters["security/isAdmin"])
const { course, userIsCoach } = storeToRefs(cidReqStore)

const user = computed(() => store.getters["security/getUser"])

const showButton = computed(() => {
  return (
    securityStore.isAuthenticated &&
    course.value &&
    (isCourseAdmin.value || isAdmin.value || userIsCoach.value(user.value.id, 0, false)) &&
    "true" === platformConfigStore.getSetting("course.student_view_enabled")
  )
})
</script>
