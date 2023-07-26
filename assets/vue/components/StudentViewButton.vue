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
import axios from "axios"
import { storeToRefs } from "pinia"
import { useCidReqStore } from "../store/cidReq"

const emit = defineEmits(["change"])

const store = useStore()
const { t } = useI18n()
const platformConfigStore = usePlatformConfig()
const cidReqStore = useCidReqStore()

const isStudentView = computed({
  async set() {
    try {
      const { data } = await axios.get(`${window.location.origin}/toggle_student_view`)

      platformConfigStore.studentView = data

      emit("change", data)
    } catch (e) {
      console.log(e)
    }
  },
  get() {
    return platformConfigStore.isStudentViewActive()
  },
})

const isAuthenticated = computed(() => store.getters["security/isAuthenticated"])
const isCourseAdmin = computed(() => store.getters["security/isCourseAdmin"])
const isAdmin = computed(() => store.getters["security/isAdmin"])
const { course, userIsCoach } = storeToRefs(cidReqStore)

const user = computed(() => store.getters["security/getUser"])

const showButton = computed(() => {
  return isAuthenticated.value &&
    course.value &&
    (isCourseAdmin.value || isAdmin.value || userIsCoach.value(user.value.id, 0, false)) &&
    "true" === platformConfigStore.getSetting("course.student_view_enabled");
})
</script>
