<template>
  <section class="space-y-6">
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

    <form
      v-else
      class="space-y-6"
      novalidate
      @submit.prevent="savePlans(false)"
    >
      <div
        v-if="successMessage"
        class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700"
        role="status"
        aria-live="polite"
      >
        {{ successMessage }}
      </div>

      <div
        v-if="formErrorMessage"
        class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
        role="alert"
        aria-live="assertive"
      >
        {{ formErrorMessage }}
      </div>

      <BaseCard>
        <template #title>
          <div class="flex items-center gap-2">
            <BaseIcon
              icon="list"
              size="normal"
            />
            <span>{{ t("Thematic plan") }}</span>
          </div>
        </template>

        <div class="space-y-3">
          <div
            class="break-words text-xl font-bold text-gray-90"
            v-html="thematicTitle"
          ></div>
          <div
            v-if="thematicContent"
            class="prose max-w-none break-words text-gray-90"
            v-html="thematicContent"
          ></div>
        </div>
      </BaseCard>

      <div class="space-y-4">
        <BaseCard
          v-for="(item, index) in items"
          :key="item.descriptionType"
        >
          <template #title>
            <div class="flex min-w-0 items-center justify-between gap-3">
              <div class="min-w-0">
                <p class="text-sm font-semibold uppercase tracking-wide text-gray-50">
                  {{ item.isCustom ? t("Other") : t(item.defaultTitle || item.title) }}
                </p>
              </div>

              <BaseButton
                icon="delete"
                :label="t('Delete')"
                only-icon
                size="small"
                type="danger-text"
                @click="clearOrRemoveItem(item, index)"
              />
            </div>
          </template>

          <div class="space-y-4">
            <div
              v-if="item.help"
              class="flex items-start gap-2 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-700"
            >
              <BaseIcon
                class="mt-0.5 shrink-0"
                icon="information"
                size="small"
              />
              <p>{{ t(item.help) }}</p>
            </div>

            <BaseInputText
              :id="`course_progress_plan_title_${item.descriptionType}`"
              v-model="item.title"
              :label="t('Title')"
              :name="`title[${item.descriptionType}]`"
            />

            <BaseTinyEditor
              v-model="item.description"
              :editor-id="`course_progress_plan_description_${item.descriptionType}`"
              :editor-config="editorConfig"
              :full-page="false"
              :title="t('Description')"
            />
          </div>
        </BaseCard>
      </div>

      <div class="flex flex-wrap justify-end gap-2">
        <BaseButton
          icon="back"
          :label="t('Cancel')"
          type="plain"
          :route="listRoute"
        />
        <BaseButton
          icon="plus"
          :disabled="isSaving"
          :is-loading="isSaving && saveMode === 'add'"
          :label="t('Save and add new item')"
          name="save_and_add"
          type="success"
          @click="savePlans(true)"
        />
        <BaseButton
          icon="save"
          :disabled="isSaving"
          :is-loading="isSaving && saveMode === 'save'"
          :label="t('Save')"
          name="save"
          type="success"
          is-submit
        />
      </div>
    </form>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import courseProgressService from "../../services/courseProgressService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { requireConfirmation } = useConfirmation()

const isLoading = ref(false)
const isSaving = ref(false)
const saveMode = ref("")
const loadErrorMessage = ref("")
const formErrorMessage = ref("")
const successMessage = ref("")
const thematicTitle = ref("")
const thematicContent = ref("")
const csrfToken = ref("")
const items = ref([])

const editorConfig = {
  toolbar: "bold italic underline | bullist numlist | link unlink | removeformat",
  menubar: false,
  height: 180,
}

const thematicId = computed(() => Number(route.params.thematicId || 0))

const listRoute = computed(() => ({
  name: "CourseProgressList",
  params: { node: route.params.node },
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

function normalizeItems(responseItems) {
  return (Array.isArray(responseItems) ? responseItems : []).map((item) => ({
    iid: item.iid ?? null,
    descriptionType: Number(item.descriptionType || 0),
    title: item.usesDefaultTitle ? t(item.defaultTitle || item.title) : String(item.title || ""),
    description: String(item.description || ""),
    defaultTitle: String(item.defaultTitle || ""),
    help: String(item.help || ""),
    isCustom: Boolean(item.isCustom),
  }))
}

function applyResponse(response) {
  thematicTitle.value = response.thematicTitle || ""
  thematicContent.value = response.thematicContent || ""
  csrfToken.value = response.csrfToken || ""
  items.value = normalizeItems(response.items)
}

async function loadPlans() {
  isLoading.value = true
  loadErrorMessage.value = ""
  formErrorMessage.value = ""
  successMessage.value = ""

  try {
    const response = await courseProgressService.getThematicPlans(thematicId.value, getContextParams())
    applyResponse(response)
  } catch (error) {
    console.error("Error loading thematic plans", error)
    loadErrorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    isLoading.value = false
  }
}

function clearOrRemoveItem(item, index) {
  if (!item.isCustom) {
    item.description = ""

    return
  }

  requireConfirmation({
    message: `${t("Are you sure you want to delete")} "${item.title || t("Other")}"?`,
    accept: () => items.value.splice(index, 1),
  })
}

async function savePlans(addNewItem) {
  if (isSaving.value) {
    return
  }

  isSaving.value = true
  saveMode.value = addNewItem ? "add" : "save"
  formErrorMessage.value = ""
  successMessage.value = ""

  try {
    const payload = {
      thematicId: thematicId.value,
      items: items.value.map((item) => ({
        iid: item.iid,
        descriptionType: item.descriptionType,
        title: item.title,
        description: item.description,
      })),
      csrfToken: csrfToken.value,
      addNewItem,
    }

    const response = await courseProgressService.saveThematicPlans(thematicId.value, payload, getContextParams())

    if (addNewItem) {
      applyResponse(response)
      successMessage.value = t("Update successful")

      return
    }

    await router.push({
      name: "CourseProgressList",
      params: { node: route.params.node },
      query: {
        ...getContextParams(),
        saved: 1,
      },
    })
  } catch (error) {
    console.error("Error saving thematic plans", error)
    formErrorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    isSaving.value = false
    saveMode.value = ""
  }
}

onMounted(loadPlans)

watch(
  () => [route.params.thematicId, route.query.cid, route.query.sid, route.query.gid, route.query.isStudentView],
  loadPlans,
)
</script>
