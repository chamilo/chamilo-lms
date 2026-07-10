<template>
  <section class="space-y-4">
    <BaseToolbar class="mb-4 border-b border-gray-25 bg-white">
      <template #start>
        <BaseButton
          icon="back"
          :label="t('Back')"
          only-icon
          :route="listRoute"
          size="large"
          :tooltip="t('Back')"
          type="primary-text"
        />
      </template>
    </BaseToolbar>

    <div
      v-if="isLoading"
      class="rounded-lg border border-gray-20 bg-white p-6 text-center text-sm text-gray-600"
      role="status"
    >
      {{ t("Loading...") }}
    </div>

    <div
      v-else-if="loadErrorMessage"
      class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      role="alert"
    >
      {{ loadErrorMessage }}
    </div>

    <form
      v-else
      class="space-y-4"
      novalidate
      @submit.prevent="saveAnnouncement"
    >
      <div
        v-if="formErrorMessage"
        ref="formErrorRef"
        class="flex items-start gap-3 rounded-lg border border-error bg-error/10 p-4 text-sm text-error shadow-sm"
        role="alert"
        aria-live="assertive"
        tabindex="-1"
      >
        <BaseIcon
          class="mt-0.5 shrink-0"
          icon="alert"
          size="small"
        />
        <span>{{ formErrorMessage }}</span>
      </div>

      <BaseCard>
        <template #title>
          <div class="flex items-center gap-2">
            <BaseIcon
              icon="announcement"
              size="small"
            />
            <span>{{ form.id ? t("Edit announcement") : t("Add an announcement") }}</span>
          </div>
        </template>

        <div class="space-y-4">
          <div>
            <BaseButton
              icon="join-group"
              :label="t('Choose recipients')"
              type="plain"
              @click="showRecipients = !showRecipients"
            />

            <div
              v-if="showRecipients"
              class="mt-3 space-y-4 rounded-lg border border-gray-25 bg-gray-10 p-4"
            >
              <BaseSelect
                v-if="form.classes.length"
                id="announcement_class"
                v-model="selectedClassId"
                :allow-clear="true"
                :label="form.classLabel || t('Classes')"
                name="announcement_class"
                option-label="label"
                option-value="id"
                :options="form.classes"
                @change="applyClassRecipients"
              />

              <BaseMultiSelect
                input-id="announcement_recipients"
                :error-text="t('Required field')"
                :is-invalid="formSubmitted && form.recipients.length === 0"
                :label="t('Recipients')"
                :model-value="form.recipients"
                option-label="label"
                option-value="value"
                :options="recipientOptions"
                @update:model-value="updateRecipients"
              />
              <input
                name="recipients"
                type="hidden"
                :value="form.recipients.join(',')"
              />
            </div>
          </div>

          <BaseInputText
            id="announcement_subject"
            v-model="form.title"
            :error-text="t('Required field')"
            :form-submitted="formSubmitted"
            :is-invalid="formSubmitted && !form.title.trim()"
            :label="t('Subject')"
            name="title"
            required
          />

          <div>
            <BaseButton
              icon="tag-outline"
              :label="t('Tags')"
              type="plain"
              @click="showTags = !showTags"
            />

            <div
              v-if="showTags"
              class="mt-3 rounded-lg border border-gray-25 bg-gray-10 p-4"
            >
              <p class="mb-3 text-sm text-gray-600">
                {{
                  t(
                    "Tags can be copied and pasted inside the text area below and will be dynamically replaced with their value for each user individually when sending them.",
                  )
                }}
              </p>
              <div class="flex flex-wrap gap-2">
                <code
                  v-for="tag in form.tags"
                  :key="tag"
                  class="rounded bg-white px-2 py-1 text-sm text-gray-700"
                >
                  {{ tag }}
                </code>
              </div>
            </div>
          </div>

          <BaseTinyEditor
            v-model="form.content"
            editor-id="announcement_content"
            :editor-config="editorConfig"
            :full-page="false"
            required
            :title="t('Description')"
          />

          <BaseAdvancedSettingsButton
            v-if="form.languages.length > 2"
            v-model="showAdvancedSettings"
          >
            <BaseSelect
              id="resource_language"
              v-model="form.language"
              :label="t('Language')"
              name="language"
              option-label="label"
              option-value="value"
              :options="form.languages"
            />
          </BaseAdvancedSettingsButton>
        </div>
      </BaseCard>

      <div class="space-y-3 rounded-lg border border-gray-20 bg-white p-4">
        <BaseButton
          icon="eye-on"
          :is-loading="isPreviewing"
          :label="t('Preview')"
          name="preview"
          type="plain"
          @click="previewAnnouncement"
        />

        <div
          v-if="previewRecipients.length"
          class="rounded-lg bg-gray-10 p-4"
        >
          <p class="mb-2 font-semibold text-gray-90">
            {{ t("Announcement will be sent to") }}
          </p>
          <ul class="list-disc space-y-1 pl-6 text-sm text-gray-700">
            <li
              v-for="recipient in previewRecipients"
              :key="recipient"
            >
              {{ recipient }}
            </li>
          </ul>
        </div>
      </div>

      <div class="flex flex-wrap justify-end gap-2">
        <BaseButton
          icon="back"
          :label="t('Cancel')"
          :route="listRoute"
          type="plain"
        />
        <BaseButton
          v-if="previewReady"
          icon="save"
          is-submit
          :is-loading="isSaving"
          :label="t('Save')"
          name="save"
          type="success"
        />
      </div>
    </form>
  </section>
</template>

<script setup>
import { computed, nextTick, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseAdvancedSettingsButton from "../../components/basecomponents/BaseAdvancedSettingsButton.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseMultiSelect from "../../components/basecomponents/BaseMultiSelect.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import announcementService from "../../services/announcementService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const isLoading = ref(false)
const isSaving = ref(false)
const isPreviewing = ref(false)
const formSubmitted = ref(false)
const loadErrorMessage = ref("")
const formErrorMessage = ref("")
const formErrorRef = ref(null)
const previewRecipients = ref([])
const previewReady = ref(false)
const previewPayload = ref(null)
const showRecipients = ref(false)
const showTags = ref(false)
const showAdvancedSettings = ref(false)
const selectedClassId = ref(null)

const form = ref({
  id: null,
  title: "",
  content: "",
  language: "",
  recipients: [],
  csrfToken: "",
  recipientOptions: [],
  classes: [],
  classLabel: "",
  languages: [],
  tags: [],
})

const editorConfig = {
  toolbar: "bold italic underline | bullist numlist | link unlink | removeformat",
  menubar: false,
  height: 320,
}

const listRoute = computed(() => ({
  name: "AnnouncementList",
  params: { node: route.params.node },
  query: getContextParams(),
}))

const recipientOptions = computed(() => {
  const selectedClass = form.value.classes.find((item) => Number(item.id) === Number(selectedClassId.value || 0))
  if (!selectedClass) {
    return form.value.recipientOptions
  }

  const allowedValues = new Set(Array.isArray(selectedClass.recipientValues) ? selectedClass.recipientValues : [])

  return form.value.recipientOptions.filter((option) => allowedValues.has(option.value))
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

  for (const key of ["origin", "page", "isStudentView"]) {
    if (Object.prototype.hasOwnProperty.call(route.query, key)) {
      params[key] = getQueryValue(route.query[key])
    }
  }

  return params
}

function getFormParams() {
  const params = getContextParams()
  const id = Number(route.params.id || 0)

  if (id > 0) {
    params.id = id
  }

  for (const key of ["remind_inactive", "remindallinactives", "since"]) {
    if (Object.prototype.hasOwnProperty.call(route.query, key)) {
      params[key] = getQueryValue(route.query[key])
    }
  }

  return params
}

function updateRecipients(values) {
  let nextValues = Array.isArray(values) ? [...values] : []

  if (nextValues.includes("everyone") && nextValues.length > 1) {
    nextValues = form.value.recipients.includes("everyone")
      ? nextValues.filter((value) => value !== "everyone")
      : ["everyone"]
  }

  form.value.recipients = nextValues
}

function applyClassRecipients() {
  const selectedClass = form.value.classes.find((item) => Number(item.id) === Number(selectedClassId.value || 0))
  if (!selectedClass) {
    form.value.recipients = ["everyone"]

    return
  }

  form.value.recipients = Array.isArray(selectedClass.recipientValues) ? [...selectedClass.recipientValues] : []
  showRecipients.value = true
}

function resetPreview() {
  previewReady.value = false
  previewRecipients.value = []
  previewPayload.value = null
}

async function loadForm() {
  isLoading.value = true
  loadErrorMessage.value = ""
  formErrorMessage.value = ""

  try {
    const response = await announcementService.getForm(getFormParams())
    form.value = {
      id: response.id ?? null,
      title: response.title || "",
      content: response.content || "",
      language: response.language || "",
      recipients: Array.isArray(response.recipients) ? response.recipients : ["everyone"],
      csrfToken: response.csrfToken || "",
      recipientOptions: Array.isArray(response.recipientOptions) ? response.recipientOptions : [],
      classes: Array.isArray(response.classes) ? response.classes : [],
      classLabel: response.classLabel || "",
      languages: Array.isArray(response.languages) ? response.languages : [],
      tags: Array.isArray(response.tags) ? response.tags : [],
    }
    showRecipients.value = form.value.recipients.length !== 1 || form.value.recipients[0] !== "everyone"
    showAdvancedSettings.value = Boolean(form.value.language)
    resetPreview()
  } catch (error) {
    console.error("Error loading announcement form", error)
    loadErrorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    isLoading.value = false
  }
}

function buildPayload() {
  return {
    title: form.value.title,
    content: form.value.content,
    language: form.value.language,
    recipients: form.value.recipients,
    csrfToken: form.value.csrfToken,
  }
}

async function showFormError(message) {
  formErrorMessage.value = message
  await nextTick()
  formErrorRef.value?.scrollIntoView({ behavior: "smooth", block: "center" })
  formErrorRef.value?.focus({ preventScroll: true })
}

async function previewAnnouncement() {
  formErrorMessage.value = ""

  if (selectedClassId.value && form.value.recipients.length === 0) {
    resetPreview()
    await showFormError(t("No available options"))

    return
  }

  isPreviewing.value = true

  try {
    const payload = buildPayload()
    const response = await announcementService.preview(payload, getContextParams())
    previewRecipients.value = Array.isArray(response.previewRecipients) ? response.previewRecipients : []
    previewReady.value = previewRecipients.value.length > 0

    if (response.csrfToken) {
      form.value.csrfToken = response.csrfToken
    }

    if (previewReady.value) {
      previewPayload.value = {
        ...payload,
        csrfToken: response.csrfToken || payload.csrfToken,
      }
    }
  } catch (error) {
    console.error("Error previewing announcement recipients", error)
    resetPreview()
    await showFormError(
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred"),
    )
  } finally {
    isPreviewing.value = false
  }
}

async function saveAnnouncement() {
  formSubmitted.value = true
  formErrorMessage.value = ""

  if (!form.value.title.trim() || !String(form.value.content || "").replace(/<[^>]*>/g, "").trim()) {
    await showFormError(t("Please fill all required fields"))

    return
  }

  if (!previewReady.value || !previewPayload.value) {
    await showFormError(t("Preview"))

    return
  }

  isSaving.value = true

  try {
    if (form.value.id) {
      await announcementService.update(form.value.id, previewPayload.value, getContextParams())
    } else {
      await announcementService.create(previewPayload.value, getContextParams())
    }

    await router.push(listRoute.value)
  } catch (error) {
    console.error("Error saving announcement", error)
    await showFormError(
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred"),
    )
  } finally {
    isSaving.value = false
  }
}

watch(
  () => [form.value.title, form.value.content, form.value.language, form.value.recipients],
  resetPreview,
  { deep: true },
)

watch(
  () => [route.params.id, route.query.cid, route.query.sid, route.query.gid, route.query.isStudentView],
  loadForm,
)

onMounted(loadForm)
</script>
