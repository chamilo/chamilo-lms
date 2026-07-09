<template>
  <section class="space-y-6">
    <BaseToolbar
      v-if="canManage"
      class="mb-4 border-b border-gray-25 bg-white"
    >
      <template #start>
        <BaseButton
          v-for="type in toolbarTypes"
          :key="type.value"
          :icon="type.icon"
          :label="t(type.label)"
          only-icon
          size="large"
          type="primary-text"
          class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
          :route="getToolbarRoute(type.value)"
        />
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
      v-else-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      role="alert"
    >
      {{ errorMessage }}
    </div>

    <div
      v-else-if="descriptions.length === 0"
      class="rounded-xl border border-gray-20 bg-white px-6 py-10 text-center shadow-sm"
    >
      <BaseIcon
        class="mb-3 text-gray-500"
        icon="information"
        size="big"
      />
      <p class="text-sm italic text-gray-500">
        {{ t("No descriptions available yet.") }}
      </p>
    </div>

    <div
      v-else
      class="space-y-4"
    >
      <BaseCard
        v-for="description in descriptions"
        :id="`description_${description.descriptionType}`"
        :key="description.iid"
        :data-id="description.iid"
        data-type="course_description"
      >
        <template #title>
          <div class="flex min-w-0 items-center gap-2">
            <div
              class="min-w-0 flex-1 break-words text-lg font-semibold text-gray-90"
              v-html="description.title || getDescriptionTypeLabel(description.descriptionType)"
            ></div>
            <BaseIcon
              v-if="description.sessionId"
              :tooltip="t('Session')"
              icon="sessions"
              size="small"
            />
            <BaseButton
              v-if="canManage && description.canEdit"
              icon="pencil"
              :label="t('Edit')"
              only-icon
              size="small"
              type="secondary-text"
              :route="getEditRoute(description)"
              :tooltip="t('Edit')"
            />
            <BaseButton
              v-if="canManage && description.canDelete"
              icon="delete"
              :is-loading="deletingId === description.iid"
              :label="t('Delete')"
              only-icon
              size="small"
              type="danger-text"
              :tooltip="t('Delete')"
              @click="confirmDelete(description)"
            />
          </div>
        </template>

        <div
          v-if="description.content"
          class="break-words"
          v-html="description.content"
        ></div>
        <p
          v-else
          class="text-sm italic text-gray-500"
        >
          {{ t("No content") }}
        </p>
      </BaseCard>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import courseDescriptionService from "../../services/courseDescriptionService"

const { t } = useI18n()
const route = useRoute()
const { requireConfirmation } = useConfirmation()

const descriptions = ref([])
const isLoading = ref(false)
const errorMessage = ref("")
const actionErrorMessage = ref("")
const successMessage = ref("")
const canManage = ref(false)
const types = ref([])
const csrfToken = ref("")
const deletingId = ref(null)

const descriptionTypeLabels = {
  1: "Description",
  2: "Objectives",
  3: "Topics",
  4: "Methodology",
  5: "Course material",
  6: "Resources",
  7: "Assessment",
  8: "Other",
}

const toolbarTypes = computed(() => {
  if (types.value.length > 0) {
    return types.value
  }

  return Object.entries(descriptionTypeLabels).map(([value, label]) => ({
    value: Number(value),
    label,
    icon: "information",
  }))
})

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

function getRouteQuery(extraQuery = {}) {
  return {
    ...getContextParams(),
    ...extraQuery,
  }
}

function getDescriptionTypeLabel(descriptionType) {
  return t(descriptionTypeLabels[Number(descriptionType)] || "Other")
}

function getOwnDescriptionByType(descriptionType) {
  return descriptions.value.find(
    (description) =>
      Number(description.descriptionType) === Number(descriptionType) && !description.isInheritedFromCourse,
  )
}

function getToolbarRoute(descriptionType) {
  const existingDescription = getOwnDescriptionByType(descriptionType)

  if (Number(descriptionType) === 8) {
    return {
      name: "CourseDescriptionAdd",
      params: { node: route.params.node },
      query: getRouteQuery({ descriptionType }),
    }
  }

  return {
    name: "CourseDescriptionEdit",
    params: {
      node: route.params.node,
      id: existingDescription?.iid,
    },
    query: getRouteQuery({ descriptionType }),
  }
}

function getEditRoute(description) {
  return {
    name: "CourseDescriptionEdit",
    params: {
      node: route.params.node,
      id: description.iid,
    },
    query: getRouteQuery({ descriptionType: description.descriptionType }),
  }
}

function getPlainTitle(description) {
  const fallbackTitle = getDescriptionTypeLabel(description.descriptionType)
  const title = String(description.title || fallbackTitle)

  return title.replace(/<[^>]*>/g, "").trim() || fallbackTitle
}

function confirmDelete(description) {
  const title = getPlainTitle(description)

  requireConfirmation({
    message: `${t("Are you sure you want to delete")} "${title}"?`,
    accept: () => deleteDescription(description),
  })
}

async function deleteDescription(description) {
  if (deletingId.value !== null) {
    return
  }

  deletingId.value = description.iid
  actionErrorMessage.value = ""
  successMessage.value = ""

  try {
    await courseDescriptionService.remove(
      description.iid,
      { csrfToken: csrfToken.value },
      getContextParams(),
    )

    descriptions.value = descriptions.value.filter((item) => item.iid !== description.iid)
    successMessage.value = t("Description has been deleted")
  } catch (error) {
    console.error("Error deleting course description", error)
    actionErrorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    deletingId.value = null
  }
}

async function loadDescriptions() {
  isLoading.value = true
  errorMessage.value = ""
  actionErrorMessage.value = ""
  successMessage.value = ""

  try {
    const response = await courseDescriptionService.getList(getContextParams())
    descriptions.value = Array.isArray(response.items) ? response.items : []
    canManage.value = Boolean(response.canManage)
    types.value = Array.isArray(response.types) ? response.types : []
    csrfToken.value = response.csrfToken || ""
  } catch (error) {
    console.error("Error loading course descriptions", error)
    errorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    isLoading.value = false
  }
}

onMounted(loadDescriptions)

watch(
  () => [route.query.cid, route.query.sid, route.query.gid, route.query.isStudentView],
  loadDescriptions,
)
</script>
