<script setup>
import { onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { storeToRefs } from "pinia"
import BaseMultiSelect from "../basecomponents/BaseMultiSelect.vue"
import baseService from "../../services/baseService"
import { useCidReqStore } from "../../store/cidReq"

const model = defineModel({ type: Object })

const { t } = useI18n()
const { course, session } = storeToRefs(useCidReqStore())

const options = ref([])
const selected = ref([])
const isLoading = ref(false)

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

/**
 * Loads the users and groups of the current course the event can be sent to.
 */
async function loadOptions() {
  isLoading.value = true

  try {
    const params = { cid: course.value?.id }
    if (session.value?.id) {
      params.sid = session.value.id
    }

    const { users = [], groups = [] } = await baseService.get("/api/calendar/course-recipients", params)

    options.value = [
      ...groups.map((group) => ({ value: `GROUP:${group.id}`, label: `${t("Group")}: ${group.label}` })),
      ...users.map((user) => ({ value: `USER:${user.id}`, label: user.label })),
    ]
  } catch (error) {
    console.error("[Calendar] Failed to load the course recipients.", error)
  } finally {
    isLoading.value = false
  }
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
    filter
    input-id="course_recipients"
    option-label="label"
    option-value="value"
  />
  <small
    v-if="!selected.length"
    class="block -mt-2 mb-4 text-gray-50"
    v-text="t('Everyone')"
  />
</template>
