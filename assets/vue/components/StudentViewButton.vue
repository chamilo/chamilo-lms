<template>
  <BaseToggleButton
    v-if="isCurrentTeacher && 'true' === platformConfigStore.getSetting('course.student_view_enabled')"
    v-model="isStudentView"
    :off-label="t('Switch to student view')"
    :on-label="t('Switch to teacher view')"
    off-icon="eye-off"
    on-icon="eye-on"
  />
</template>

<script setup>
import BaseToggleButton from "./basecomponents/BaseToggleButton.vue";
import { computed, ref, watch } from "vue"
import { useI18n } from "vue-i18n";
import { useStore } from "vuex";
import { usePlatformConfig } from "../store/platformConfig"
import axios from "axios"

const store = useStore();
const { t } = useI18n();
const platformConfigStore = usePlatformConfig()

const isStudentView = ref(platformConfigStore.isStudentViewActive)

const isLoading = ref(false);

watch(isStudentView, async () => {
  isLoading.value = true

  try {
    const { data } = await axios.get(`${window.location.origin}/toggle_student_view`)

    platformConfigStore.isStudentViewActive = 'studentview' === data
  } catch (e) {
    console.log(e)
  } finally {
    isLoading.value = false
  }
})

const isCurrentTeacher = computed(() => store.getters["security/isCurrentTeacher"]);
</script>
