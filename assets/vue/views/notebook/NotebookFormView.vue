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
      @submit.prevent="saveNote"
    >
      <BaseCard>
        <template #title>
          <div class="flex items-center gap-2">
            <BaseIcon
              icon="edit"
              size="normal"
            />
            <span>{{ t("Notebook") }}</span>
          </div>
        </template>

        <div class="space-y-5">
          <BaseInputText
            id="notebook_title"
            v-model="form.title"
            :error-text="t('Title is required')"
            :form-submitted="formSubmitted"
            :is-invalid="formSubmitted && !form.title.trim()"
            :label="t('Title')"
            maxlength="255"
            name="title"
            required
          />

          <BaseTinyEditor
            v-model="form.content"
            editor-id="notebook_content"
            :editor-config="editorConfig"
            :full-page="false"
            :title="t('Content')"
          />

          <BaseAdvancedSettingsButton
            v-if="form.languages.length > 1"
            v-model="showAdvancedSettings"
          >
            <BaseSelect
              id="resource_language"
              v-model="form.language"
              :label="t('Language')"
              name="language"
              :options="languageOptions"
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
import { useToast } from "primevue/usetoast"
import { useRoute, useRouter } from "vue-router"
import BaseAdvancedSettingsButton from "../../components/basecomponents/BaseAdvancedSettingsButton.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import notebookService from "../../services/notebookService"

const { t } = useI18n()
const toast = useToast()
const route = useRoute()
const router = useRouter()

const isLoading = ref(false)
const isSaving = ref(false)
const formSubmitted = ref(false)
const loadErrorMessage = ref("")
const showAdvancedSettings = ref(false)

const form = ref({
  iid: null,
  title: "",
  content: "",
  language: "",
  csrfToken: "",
  canWrite: false,
  isNew: true,
  fullEditor: false,
  languages: [],
})

const listRoute = computed(() => ({
  name: "NotebookList",
  params: { node: route.params.node },
  query: getContextParams(),
}))

const languageOptions = computed(() =>
  form.value.languages.map((language) => ({
    ...language,
    label: language.value === "" ? t("No specific language") : language.label,
  })),
)

const editorConfig = computed(() => {
  if (form.value.fullEditor) {
    return {
      height: 300,
    }
  }

  return {
    toolbar:
      "undo redo | styles blocks fontfamily fontsize | bold italic underline | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent blockquote | link unlink | image media table charmap | visualblocks removeformat",
    menubar: false,
    height: 300,
  }
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

function getFormParams() {
  const params = getContextParams()
  const id = Number(route.params.id || 0)

  if (id > 0) {
    params.id = id
  }

  return params
}

function showToast(severity, summaryKey, detail, life = 3500) {
  toast.add({
    severity,
    summary: t(summaryKey),
    detail,
    life,
  })
}

async function loadForm() {
  isLoading.value = true
  loadErrorMessage.value = ""

  try {
    const response = await notebookService.getForm(getFormParams())
    form.value = {
      iid: response.iid ?? null,
      title: response.title || "",
      content: response.content || "",
      language: response.language || "",
      csrfToken: response.csrfToken || "",
      canWrite: Boolean(response.canWrite),
      isNew: Boolean(response.isNew ?? true),
      fullEditor: Boolean(response.fullEditor),
      languages: Array.isArray(response.languages) ? response.languages : [],
    }
  } catch (error) {
    console.error("Error loading notebook form", error)
    loadErrorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    isLoading.value = false
  }
}

async function saveNote() {
  formSubmitted.value = true

  if (!form.value.title.trim()) {
    showToast("warn", "Warning", t("Please complete all required fields."), 5000)

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
      await notebookService.update(form.value.iid, payload, getContextParams())
    } else {
      await notebookService.create(payload, getContextParams())
    }

    await router.push({
      ...listRoute.value,
      query: {
        ...getContextParams(),
        result: form.value.iid ? "updated" : "created",
      },
    })
  } catch (error) {
    console.error("Error saving notebook entry", error)
    const message =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
    showToast("error", "Error", message, 5000)
  } finally {
    isSaving.value = false
  }
}

onMounted(loadForm)

watch(
  () => [route.params.id, route.query.cid, route.query.sid, route.query.gid, route.query.isStudentView],
  loadForm,
)
</script>
