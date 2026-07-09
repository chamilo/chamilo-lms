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
      @submit.prevent="saveDescription"
    >
      <div
        v-if="formErrorMessage"
        class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
        role="alert"
        aria-live="polite"
      >
        {{ formErrorMessage }}
      </div>
      <BaseCard>
        <template #title>
          <div class="flex items-center gap-2">
            <BaseIcon
              :icon="currentType.icon || 'information'"
              size="normal"
            />
            <span>{{ t(currentType.label || "Course description") }}</span>
          </div>
        </template>

        <div class="space-y-5">
          <div
            v-if="form.help"
            class="rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm text-blue-800"
          >
            <strong>{{ t("Help") }}</strong>
            <p class="mt-1">{{ t(form.help) }}</p>
          </div>

          <BaseTinyEditor
            v-if="settings.saveTitlesAsHtml"
            v-model="form.title"
            editor-id="course_description_title"
            :editor-config="titleEditorConfig"
            :full-page="false"
            :title="t('Title')"
          />
          <BaseInputText
            v-else
            id="course_description_title"
            v-model="form.title"
            :error-text="t('Title is required')"
            :form-submitted="formSubmitted"
            :is-invalid="formSubmitted && !form.title.trim()"
            :label="t('Title')"
            name="title"
            required
          />

          <BaseTinyEditor
            v-model="form.content"
            editor-id="course_description_content"
            :editor-config="contentEditorConfig"
            :full-page="false"
            :title="t('Content')"
          />

          <BaseAdvancedSettingsButton
            v-if="showAdvancedSettings"
            v-model="showAdvancedSettingsPanel"
          >
            <div class="space-y-4">
              <BaseSelect
                v-if="form.languages.length > 2"
                id="resource_language"
                v-model="form.language"
                :label="t('Language')"
                name="language"
                :options="translatedLanguages"
              />

              <BaseCheckbox
                v-if="settings.searchEnabled"
                id="enable_search"
                v-model="form.enableSearch"
                :label="t('Index this description for the global search')"
                name="enable_search"
              />
            </div>
          </BaseAdvancedSettingsButton>
        </div>
      </BaseCard>

      <div class="flex flex-wrap justify-end gap-2">
        <BaseButton
          icon="back"
          :label="t('Cancel')"
          type="plain"
          :route="listRoute"
        />
        <BaseButton
          icon="save"
          :is-loading="isSaving"
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
import BaseAdvancedSettingsButton from "../../components/basecomponents/BaseAdvancedSettingsButton.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import courseDescriptionService from "../../services/courseDescriptionService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const isLoading = ref(false)
const isSaving = ref(false)
const formSubmitted = ref(false)
const loadErrorMessage = ref("")
const formErrorMessage = ref("")
const showAdvancedSettingsPanel = ref(false)

const form = ref({
  iid: null,
  descriptionType: 1,
  title: "",
  content: "",
  progress: 0,
  language: "",
  enableSearch: true,
  csrfToken: "",
  help: "",
  information: "",
  types: [],
  languages: [],
})

const settings = ref({
  searchEnabled: false,
  saveTitlesAsHtml: false,
})

const titleEditorConfig = {
  toolbar: "bold italic underline | removeformat",
  menubar: false,
  height: 120,
}

const contentEditorConfig = {
  toolbar: "bold italic underline | bullist numlist | link unlink | removeformat",
  menubar: false,
  height: 240,
}

const listRoute = computed(() => ({
  name: "CourseDescriptionList",
  params: { node: route.params.node },
  query: getContextParams(),
}))

const currentType = computed(() => {
  const type = form.value.types.find((item) => Number(item.value) === Number(form.value.descriptionType))

  return type || { label: "Course description", icon: "information" }
})

const translatedLanguages = computed(() =>
  form.value.languages.map((language) => ({
    ...language,
    label: t(language.label),
  })),
)

const showAdvancedSettings = computed(() => form.value.languages.length > 2 || settings.value.searchEnabled)

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

  return params
}

function getFormParams() {
  const params = getContextParams()
  const id = Number(route.params.id || 0)
  const descriptionType = Number(getQueryValue(route.query.descriptionType) || 0)

  if (id > 0) {
    params.id = id
  }

  if (descriptionType > 0) {
    params.descriptionType = descriptionType
  }

  return params
}

async function loadForm() {
  isLoading.value = true
  loadErrorMessage.value = ""
  formErrorMessage.value = ""

  try {
    const response = await courseDescriptionService.getForm(getFormParams())
    form.value = {
      iid: response.iid ?? null,
      descriptionType: Number(response.descriptionType || 1),
      title: response.title || response.defaultTitle || "",
      content: response.content || "",
      progress: Number(response.progress || 0),
      language: response.language || "",
      enableSearch: Boolean(response.enableSearch ?? true),
      csrfToken: response.csrfToken || "",
      help: response.help || "",
      information: response.information || "",
      types: Array.isArray(response.types) ? response.types : [],
      languages: Array.isArray(response.languages) ? response.languages : [],
    }
    settings.value = {
      searchEnabled: Boolean(response.settings?.searchEnabled),
      saveTitlesAsHtml: Boolean(response.settings?.saveTitlesAsHtml),
    }
  } catch (error) {
    console.error("Error loading course description form", error)
    loadErrorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    isLoading.value = false
  }
}

async function saveDescription() {
  formSubmitted.value = true
  formErrorMessage.value = ""

  if (!form.value.title.trim() || !form.value.content.trim()) {
    formErrorMessage.value = t("Please fill all required fields")

    return
  }

  const payload = {
    iid: form.value.iid,
    descriptionType: form.value.descriptionType,
    title: form.value.title,
    content: form.value.content,
    progress: form.value.progress,
    language: form.value.language,
    enableSearch: form.value.enableSearch,
    csrfToken: form.value.csrfToken,
  }

  isSaving.value = true

  try {
    if (form.value.iid) {
      await courseDescriptionService.update(form.value.iid, payload, getContextParams())
    } else {
      await courseDescriptionService.create(payload, getContextParams())
    }

    await router.push(listRoute.value)
  } catch (error) {
    console.error("Error saving course description", error)
    formErrorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    isSaving.value = false
  }
}

onMounted(loadForm)

watch(
  () => [route.params.id, route.query.descriptionType, route.query.cid, route.query.sid, route.query.gid],
  loadForm,
)
</script>
