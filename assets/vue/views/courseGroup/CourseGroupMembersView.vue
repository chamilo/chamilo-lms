<template>
  <section class="space-y-6">
    <BaseToolbar>
      <template #start>
        <BaseButton
          :label="t('Back to group area')"
          :route="detailRoute"
          icon="back"
          only-icon
          type="plain"
        />
      </template>
    </BaseToolbar>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
    >
      {{ errorMessage }}
    </div>

    <article
      v-if="!loading"
      class="rounded-xl border border-gray-20 bg-white p-6 shadow-sm"
    >
      <h1 class="text-xl font-semibold text-gray-90">
        {{ isTutorMode ? t("Tutors") : t("Group members") }}
      </h1>
      <p class="mt-1 text-sm text-gray-50">{{ data.groupTitle }}</p>

      <div
        v-if="data.linkedToClass && !isTutorMode"
        class="mt-5 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800"
      >
        {{ t("Members are managed by the linked class") }}: <strong>{{ data.linkedClassTitle }}</strong>
      </div>

      <div class="mt-6">
        <BaseMultiSelect
          v-model="data.selectedIds"
          :input-id="isTutorMode ? 'group-tutors' : 'group-members'"
          :label="isTutorMode ? t('Tutors') : t('Group members')"
          :options="data.options"
          option-label="name"
          option-value="id"
        />
        <p
          v-if="!isTutorMode && data.maxStudent > 0"
          class="mt-2 text-xs text-gray-50"
        >
          {{ t("Maximum number of members") }}: {{ data.maxStudent }}
        </p>
      </div>

      <div class="mt-6 flex justify-end">
        <BaseButton
          :disabled="data.linkedToClass && !isTutorMode"
          :is-loading="saving"
          :label="t('Save settings')"
          icon="save"
          type="success"
          @click="save"
        />
      </div>
    </article>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseMultiSelect from "../../components/basecomponents/BaseMultiSelect.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import courseGroupService from "../../services/courseGroupService"
import { useRouteCourseContext } from "../../composables/useRouteCourseContext"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { cid, sid, contextQuery } = useRouteCourseContext()
const loading = ref(false)
const saving = ref(false)
const errorMessage = ref("")
const data = reactive({
  groupTitle: "",
  options: [],
  selectedIds: [],
  maxStudent: 0,
  linkedToClass: false,
  linkedClassTitle: "",
})

const groupId = computed(() => Number(route.params.groupId))
const isTutorMode = computed(() => route.name === "CourseGroupTutors")
const mode = computed(() => (isTutorMode.value ? "tutors" : "members"))
const requestParams = computed(() => ({ cid: cid.value, sid: sid.value, gid: groupId.value }))
const detailRoute = computed(() => ({
  name: "CourseGroupDetail",
  params: { ...route.params },
  query: { ...contextQuery.value, gid: groupId.value },
}))

function normalizeList(value) {
  if (Array.isArray(value)) {
    return value
  }

  if (value && typeof value === "object") {
    return Object.values(value)
  }

  return []
}

function normalizeSelectedIds(value) {
  return normalizeList(value)
    .map((item) => Number(item))
    .filter((item) => Number.isInteger(item) && item > 0)
}

async function load() {
  loading.value = true
  errorMessage.value = ""
  try {
    const response = await courseGroupService.getMembers(groupId.value, mode.value, requestParams.value)
    Object.assign(data, {
      ...response,
      options: normalizeList(response?.options),
      selectedIds: normalizeSelectedIds(response?.selectedIds),
    })
  } catch (error) {
    console.error("[CourseGroup] Failed to load group users", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    loading.value = false
  }
}

async function save() {
  if (saving.value) return
  saving.value = true
  errorMessage.value = ""
  try {
    await courseGroupService.saveMembers(
      groupId.value,
      mode.value,
      normalizeSelectedIds(data.selectedIds),
      requestParams.value,
    )
    await router.push(detailRoute.value)
  } catch (error) {
    console.error("[CourseGroup] Failed to save group users", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>
