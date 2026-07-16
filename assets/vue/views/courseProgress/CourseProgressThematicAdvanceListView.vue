<template>
  <section class="space-y-6">
    <BaseToolbar class="mb-4 border-b border-gray-25 bg-white">
      <template #start>
        <div class="flex items-center gap-2">
          <BaseButton
            icon="back"
            :label="t('Back')"
            only-icon
            size="large"
            type="primary-text"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            :route="courseProgressRoute"
          />
          <BaseButton
            v-if="canEdit"
            icon="calendar-plus"
            :label="t('New thematic advance')"
            only-icon
            size="large"
            type="success-text"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            :route="addRoute"
          />
        </div>
      </template>
    </BaseToolbar>

    <div
      v-if="successMessage"
      class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700"
      role="status"
      aria-live="polite"
    >
      {{ successMessage }}
    </div>

    <div
      v-if="actionErrorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      role="alert"
      aria-live="assertive"
    >
      {{ actionErrorMessage }}
    </div>

    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600 shadow-sm"
      role="status"
    >
      {{ t("Loading...") }}
    </div>

    <div
      v-else-if="loadErrorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      role="alert"
    >
      {{ loadErrorMessage }}
    </div>

    <template v-else>
      <BaseCard>
        <template #title>
          <div class="flex items-center gap-2">
            <BaseIcon
              icon="agenda-plan"
              size="normal"
            />
            <div
              class="break-words"
              v-html="thematicTitle"
            ></div>
          </div>
        </template>

        <div
          v-if="thematicContent"
          class="prose max-w-none break-words text-gray-90"
          v-html="thematicContent"
        ></div>
      </BaseCard>

      <BaseTable
        :is-loading="isLoading"
        :text-for-empty="t('There is no thematic advance')"
        :total-items="advances.length"
        :values="advances"
        data-key="iid"
      >
        <Column
          :header="t('Start Date')"
          field="startDate"
          sortable
        >
          <template #body="slotProps">
            <div class="flex items-center gap-2">
              <span>{{ slotProps.data.formattedStartDate }}</span>
              <BaseIcon
                v-if="slotProps.data.attendanceId"
                :tooltip="slotProps.data.attendanceTitle"
                icon="agenda-event"
                size="small"
              />
            </div>
          </template>
        </Column>

        <Column
          :header="t('Duration in hours')"
          field="duration"
          sortable
        >
          <template #body="slotProps">
            <span>{{ slotProps.data.duration }}</span>
          </template>
        </Column>

        <Column
          :header="t('Content')"
          field="content"
        >
          <template #body="slotProps">
            <div
              v-if="slotProps.data.content"
              class="prose max-w-none break-words text-gray-90"
              v-html="slotProps.data.content"
            ></div>
          </template>
        </Column>

        <Column
          v-if="canEdit"
          :header="t('Detail')"
        >
          <template #body="slotProps">
            <div class="flex items-center justify-center gap-1">
              <BaseButton
                icon="pencil"
                :label="t('Edit')"
                only-icon
                size="small"
                type="secondary-text"
                :route="getEditRoute(slotProps.data)"
              />
              <BaseButton
                icon="delete"
                :is-loading="deletingId === slotProps.data.iid"
                :label="t('Delete')"
                only-icon
                size="small"
                type="danger-text"
                @click="confirmDelete(slotProps.data)"
              />
            </div>
          </template>
        </Column>
      </BaseTable>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import courseProgressService from "../../services/courseProgressService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { requireConfirmation } = useConfirmation()

const advances = ref([])
const thematicTitle = ref("")
const thematicContent = ref("")
const csrfToken = ref("")
const canEdit = ref(false)
const isLoading = ref(false)
const deletingId = ref(null)
const loadErrorMessage = ref("")
const actionErrorMessage = ref("")
const successMessage = ref("")

const thematicId = computed(() => Number(route.params.thematicId || 0))

const courseProgressRoute = computed(() => ({
  name: "CourseProgressList",
  params: { node: route.params.node },
  query: getContextParams(),
}))

const addRoute = computed(() => ({
  name: "CourseProgressThematicAdvanceAdd",
  params: {
    node: route.params.node,
    thematicId: thematicId.value,
  },
  query: getContextParams(),
}))

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function getContextParams() {
  const params = {
    cid: getQueryValue(route.query.cid),
  }
  const sid = Number(getQueryValue(route.query.sid) || 0)
  const gid = Number(getQueryValue(route.query.gid) || 0)

  if (sid > 0) {
    params.sid = sid
  }

  if (gid > 0) {
    params.gid = gid
  }

  if (Object.prototype.hasOwnProperty.call(route.query, "isStudentView")) {
    params.isStudentView = getQueryValue(route.query.isStudentView)
  }

  return params
}

function getEditRoute(advance) {
  return {
    name: "CourseProgressThematicAdvanceEdit",
    params: {
      node: route.params.node,
      thematicId: thematicId.value,
      advanceId: advance.iid,
    },
    query: getContextParams(),
  }
}

function confirmDelete(advance) {
  requireConfirmation({
    message: `${t("Are you sure you want to delete")} "${advance.formattedStartDate}"?`,
    accept: () => deleteAdvance(advance),
  })
}

async function deleteAdvance(advance) {
  if (deletingId.value !== null) {
    return
  }

  deletingId.value = advance.iid
  actionErrorMessage.value = ""
  successMessage.value = ""

  try {
    await courseProgressService.removeThematicAdvance(
      thematicId.value,
      advance.iid,
      { csrfToken: csrfToken.value },
      getContextParams(),
    )

    await loadAdvances()
    successMessage.value = t("Deleted")
  } catch (error) {
    console.error("Error deleting thematic advance", error)
    actionErrorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    deletingId.value = null
  }
}

async function consumeSavedMessage() {
  if (String(getQueryValue(route.query.saved) || "") !== "1") {
    return
  }

  successMessage.value = t("Update successful")
  const query = { ...route.query }
  delete query.saved

  await router.replace({
    name: route.name,
    params: route.params,
    query,
  })
}

async function loadAdvances() {
  isLoading.value = true
  loadErrorMessage.value = ""
  actionErrorMessage.value = ""

  try {
    const response = await courseProgressService.getThematicAdvances(thematicId.value, getContextParams())
    advances.value = Array.isArray(response.items) ? response.items : []
    thematicTitle.value = response.thematicTitle || ""
    thematicContent.value = response.thematicContent || ""
    csrfToken.value = response.csrfToken || ""
    canEdit.value = Boolean(response.canEdit)
  } catch (error) {
    console.error("Error loading thematic advances", error)
    loadErrorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    isLoading.value = false
  }
}

onMounted(async () => {
  await loadAdvances()
  await consumeSavedMessage()
})

watch(
  () => [route.params.thematicId, route.query.cid, route.query.sid, route.query.gid, route.query.isStudentView],
  loadAdvances,
)
</script>
