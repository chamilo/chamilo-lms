<script setup>
import { computed, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseSelect from "../basecomponents/BaseSelect.vue"
import { useSecurityStore } from "../../store/securityStore"
import { useCalendarCourseRecipients } from "../../composables/calendar/calendarCourseRecipients"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const securityStore = useSecurityStore()

const { options, isLoading, loadOptions } = useCalendarCourseRecipients()

// Teachers list the events sent to one user or group of the course; the agenda of a group already is one.
const isVisible = computed(
  () => Number(route.query.cid ?? 0) > 0 && !Number(route.query.gid ?? 0) && securityStore.isCurrentTeacher,
)

const selected = computed({
  get: () => route.query.recipient ?? null,
  set: (value) => {
    const query = { ...route.query, recipient: value || undefined }

    router.replace({ query })
  },
})

// The course roles can arrive after the mount.
watch(
  isVisible,
  (visible) => {
    if (visible && !options.value.length) {
      loadOptions()
    }
  },
  { immediate: true },
)
</script>

<template>
  <div
    v-if="isVisible"
    class="md:w-96"
  >
    <BaseSelect
      id="recipient_filter"
      v-model="selected"
      :is-loading="isLoading"
      :label="t('Filter')"
      :options="options"
      :placeholder="t('All')"
      allow-clear
      name="recipient_filter"
      option-label="label"
      option-value="value"
    />
  </div>
</template>
