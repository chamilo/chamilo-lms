<script setup>
import { onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { storeToRefs } from "pinia"
import BaseMultiSelect from "../basecomponents/BaseMultiSelect.vue"
import { useCidReqStore } from "../../store/cidReq"
import { useCalendarCourseRecipients } from "../../composables/calendar/calendarCourseRecipients"

const model = defineModel({ type: Object })

const { t } = useI18n()
const { course, session } = storeToRefs(useCidReqStore())

const { options, isLoading, loadOptions } = useCalendarCourseRecipients()
const selected = ref([])

/**
 * Reads the numeric id of an entity serialized as an object or as an IRI.
 *
 * @param {Object|string|null} value
 * @returns {number}
 */
function idOf(value) {
  if (!value) {
    return 0
  }

  if (typeof value === "object") {
    return Number(value.id ?? value.iid ?? idOf(value["@id"]))
  }

  const match = String(value).match(/(\d+)$/)

  return match ? Number(match[1]) : 0
}

/**
 * Selects the users and groups the edited event is sent to in the current course.
 */
function selectFromLinks() {
  const courseId = Number(course.value?.id ?? 0)
  const sessionId = Number(session.value?.id ?? 0)

  selected.value = (model.value?.resourceLinkListFromEntity ?? [])
    .filter((link) => idOf(link.course) === courseId && idOf(link.session) === sessionId)
    .map((link) =>
      idOf(link.user) ? `USER:${idOf(link.user)}` : idOf(link.group) ? `GROUP:${idOf(link.group)}` : null,
    )
    .filter(Boolean)
}

watch(selected, (values) => {
  model.value.courseRecipients = values.map((value) => {
    const [kind, id] = value.split(":")

    return kind === "GROUP" ? { gid: Number(id) } : { uid: Number(id) }
  })
})

onMounted(() => {
  selectFromLinks()
  loadOptions()
})
</script>

<template>
  <BaseMultiSelect
    v-model="selected"
    :is-loading="isLoading"
    :label="t('Send to')"
    :options="options"
    :placeholder="t('Everyone')"
    filter
    input-id="course_recipients"
    option-label="label"
    option-value="value"
  />
</template>
