<template>
  <BaseToggleButton
    v-if="isCurrentTeacher && 'true' === platformConfigurationStore.getSetting('course.student_view_enabled')"
    v-model="isStudentView"
    :off-label="t('Switch to student view')"
    :on-label="t('Switch to teacher view')"
    off-icon="eye-off"
    on-icon="eye-on"
  />
</template>

<script setup>
import BaseToggleButton from "./basecomponents/BaseToggleButton.vue";
import { computed, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import { useRoute } from "vue-router";
import { useStore } from "vuex";
import { usePlatformConfig } from "../store/platformConfig";

const route = useRoute();
const store = useStore();
const { t } = useI18n();

const platformConfigurationStore = usePlatformConfig();

const isStudentView = ref('studentview' === platformConfigurationStore.studentView);

watch(isStudentView, (newValue) => {
  const params = new URLSearchParams(window.location.search);
  params.delete('isStudentView');
  params.append('isStudentView', newValue ? 'true' : 'false');

  window.location.href = route.path + '?' + params.toString();
});

const isCurrentTeacher = computed(() => store.getters["security/isCurrentTeacher"]);
</script>
