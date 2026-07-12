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
      @submit.prevent="saveThematic"
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
              icon="tracking"
              size="normal"
            />
            <span>{{ form.iid ? t("Edit thematic section") : t("New thematic section") }}</span>
          </div>
        </template>

        <div class="space-y-5">
          <BaseTinyEditor
            v-if="settings.saveTitlesAsHtml"
            v-model="form.title"
            editor-id="course_progress_thematic_title"
            :editor-config="titleEditorConfig"
            :full-page="false"
            :title="t('Title')"
          />
          <BaseInputText
            v-else
            id="course_progress_thematic_title"
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
            editor-id="course_progress_thematic_content"
            :editor-config="contentEditorConfig"
            :full-page="false"
            :title="t('Content')"
          />

          <BaseAdvancedSettingsButton
            v-if="showAdvancedSettings"
            v-model="showAdvancedSettingsPanel"
          >
            <BaseSelect
              id="resource_language"
              v-model="form.language"
              :label="t('Language')"
              name="language"
              :options="translatedLanguages"
            />
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
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import courseProgressService from "../../services/courseProgressService"

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
  title: "",
  content: "",
  language: "",
  csrfToken: "",
  languages: [],
})

const settings = ref({
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
  name: "CourseProgressList",
  params: { node: route.params.node },
  query: getContextParams(),
}))

const translatedLanguages = computed(() =>
  form.value.languages.map((language) => ({
    ...language,
    label: t(language.label),
  })),
)

const showAdvancedSettings = computed(() => form.value.languages.length > 2)

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

function getFormParams() {
  const params = getContextParams()
  const id = Number(route.params.id || 0)

  if (id > 0) {
    params.id = id
  }

  return params
}

async function loadForm() {
  isLoading.value = true
  loadErrorMessage.value = ""
  formErrorMessage.value = ""
  formSubmitted.value = false

  try {
    const response = await courseProgressService.getThematicForm(getFormParams())
    form.value = {
      iid: response.iid ?? null,
      title: response.title || "",
      content: response.content || "",
      language: response.language || "",
      csrfToken: response.csrfToken || "",
      languages: Array.isArray(response.languages) ? response.languages : [],
    }
    settings.value = {
      saveTitlesAsHtml: Boolean(response.settings?.saveTitlesAsHtml),
    }
  } catch (error) {
    console.error("Error loading thematic form", error)
    loadErrorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    isLoading.value = false
  }
}

async function saveThematic() {
  formSubmitted.value = true
  formErrorMessage.value = ""

  if (!form.value.title.trim()) {
    formErrorMessage.value = t("Please fill all required fields")

    return
  }

  const payload = {
    iid: form.value.iid,
    title: form.value.title,
    content: form.value.content,
    language: form.value.language,
    csrfToken: form.value.csrfToken,
  }

  isSaving.value = true

  try {
    if (form.value.iid) {
      await courseProgressService.updateThematic(form.value.iid, payload, getContextParams())
    } else {
      await courseProgressService.createThematic(payload, getContextParams())
    }

    await router.push({
      ...listRoute.value,
      query: {
        ...listRoute.value.query,
        saved: 1,
      },
    })
  } catch (error) {
    console.error("Error saving thematic", error)
    formErrorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    isSaving.value = false
  }
}

onMounted(loadForm)

watch(() => [route.params.id, route.query.cid, route.query.sid, route.query.gid, route.query.isStudentView], loadForm)
</script>
