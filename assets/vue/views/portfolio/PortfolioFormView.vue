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
      @submit.prevent="saveItem"
    >
      <BaseCard>
        <template #title>
          <div class="flex items-center gap-2">
            <BaseIcon icon="edit" />
            <span>{{ form.isNew ? t("Add") : t("Edit") }}</span>
          </div>
        </template>

        <div class="space-y-5">
          <BaseSelect
            v-if="form.templates.length"
            id="portfolio_template"
            v-model="selectedTemplateId"
            :label="t('Template')"
            name="templateId"
            :options="templateOptions"
            allow-clear
            @change="applyTemplate"
          />

          <BaseTinyEditor
            v-if="form.titleAsHtml"
            v-model="form.title"
            editor-id="portfolio_title"
            :editor-config="titleEditorConfig"
            :full-page="false"
            :title="t('Title')"
            required
          />
          <BaseInputText
            v-else
            id="portfolio_title"
            v-model="form.title"
            :error-text="t('Title is required')"
            :form-submitted="formSubmitted"
            :is-invalid="formSubmitted && !plainText(form.title)"
            :label="t('Title')"
            maxlength="255"
            name="title"
            required
          />

          <BaseTinyEditor
            v-model="form.content"
            editor-id="portfolio_content"
            :editor-config="contentEditorConfig"
            :full-page="false"
            :title="t('Content')"
            required
          />

          <BaseSelect
            v-if="categoryOptions.length"
            id="portfolio_category"
            v-model="form.categoryId"
            :label="t('Category')"
            name="categoryId"
            :options="categoryOptions"
            allow-clear
          />

          <BaseMultiSelect
            v-if="form.mode === 'course' && form.tags.length"
            v-model="form.tagIds"
            input-id="portfolio_tags"
            :label="t('Tags')"
            :options="form.tags"
            option-label="label"
            option-value="id"
          />

          <BaseSelect
            id="portfolio_visibility"
            v-model="form.visibility"
            :label="t('Visibility')"
            name="visibility"
            :options="visibilityOptions"
          />

          <BaseMultiSelect
            v-if="form.advancedSharingEnabled && Number(form.visibility) === 3"
            v-model="form.recipientIds"
            input-id="portfolio_recipients"
            :label="t('Choose recipients')"
            :options="form.recipientOptions"
            option-label="fullName"
            option-value="id"
          />
        </div>
      </BaseCard>

      <BaseCard v-if="form.extraFields.length">
        <template #title>
          <div class="flex items-center gap-2">
            <BaseIcon icon="settings" />
            <span>{{ t("Additional fields") }}</span>
          </div>
        </template>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
          <template
            v-for="field in form.extraFields"
            :key="field.id"
          >
            <BaseCheckbox
              v-if="Number(field.type) === 13"
              :id="`portfolio_extra_${field.id}`"
              v-model="form.extraValues[field.id]"
              :label="field.label"
              :name="`extra_${field.id}`"
            />

            <BaseSelect
              v-else-if="[3, 4].includes(Number(field.type))"
              :id="`portfolio_extra_${field.id}`"
              v-model="form.extraValues[field.id]"
              :label="field.label"
              :name="`extra_${field.id}`"
              :options="field.options"
              allow-clear
            />

            <BaseMultiSelect
              v-else-if="Number(field.type) === 5"
              v-model="form.extraValues[field.id]"
              :input-id="`portfolio_extra_${field.id}`"
              :label="field.label"
              :options="field.options"
              option-label="label"
              option-value="value"
            />

            <BaseTextArea
              v-else-if="Number(field.type) === 2"
              :id="`portfolio_extra_${field.id}`"
              v-model="form.extraValues[field.id]"
              :label="field.label"
              :name="`extra_${field.id}`"
              :help-text="field.help"
            />

            <div
              v-else-if="[16, 18].includes(Number(field.type))"
              class="space-y-2"
            >
              <label
                class="block text-sm font-medium text-gray-90"
                :for="`portfolio_extra_file_${field.id}`"
              >
                {{ field.label }}
              </label>
              <div
                v-if="field.assetName"
                class="text-xs text-gray-500"
              >
                {{ field.assetName }}
              </div>
              <input
                :id="`portfolio_extra_file_${field.id}`"
                :accept="Number(field.type) === 16 ? 'image/png,image/jpeg,image/gif' : undefined"
                class="block w-full rounded-lg border border-gray-30 bg-white px-3 py-2 text-sm"
                :name="`extraFile_${field.id}`"
                type="file"
                @change="selectExtraFile(field.id, $event)"
              />
              <small v-if="field.help">{{ field.help }}</small>
            </div>

            <BaseInputText
              v-else
              :id="`portfolio_extra_${field.id}`"
              v-model="form.extraValues[field.id]"
              :label="field.label"
              :name="`extra_${field.id}`"
              :help-text="field.help"
            />
          </template>
        </div>
      </BaseCard>

      <BaseCard>
        <template #title>
          <div class="flex items-center gap-2">
            <BaseIcon icon="attachment" />
            <span>{{ t("Attachments") }}</span>
          </div>
        </template>

        <div class="space-y-4">
          <div
            v-if="form.attachments.length"
            class="space-y-2"
          >
            <div
              v-for="attachment in form.attachments"
              :key="attachment.id"
              class="flex items-center justify-between gap-3 rounded-lg border border-gray-20 p-3"
            >
              <a
                :href="attachment.downloadUrl"
                class="min-w-0 truncate text-sm text-primary hover:underline"
              >
                {{ attachment.filename }}
              </a>
              <BaseButton
                v-if="attachment.canDelete"
                icon="delete"
                :label="t('Delete')"
                only-icon
                size="small"
                type="danger-text"
                @click="deleteAttachment(attachment)"
              />
            </div>
          </div>

          <BaseFileUploadMultiple
            v-model="form.newAttachments"
            :label="t('Add attachments')"
          />

          <div
            v-for="(file, index) in form.newAttachments"
            :key="`${file.name}-${index}`"
          >
            <BaseInputText
              :id="`portfolio_attachment_description_${index}`"
              v-model="form.attachmentDescriptions[index]"
              :label="`${t('Description')}: ${file.name}`"
              :name="`attachmentDescription_${index}`"
            />
          </div>
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
import { computed, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useToast } from "primevue/usetoast"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseFileUploadMultiple from "../../components/basecomponents/BaseFileUploadMultiple.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseMultiSelect from "../../components/basecomponents/BaseMultiSelect.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import portfolioService from "../../services/portfolioService"

const { t } = useI18n()
const toast = useToast()
const route = useRoute()
const router = useRouter()

const isLoading = ref(false)
const isSaving = ref(false)
const formSubmitted = ref(false)
const loadErrorMessage = ref("")
const selectedTemplateId = ref(null)
const form = reactive(emptyForm())

const titleEditorConfig = {
  toolbar: "undo redo | bold italic underline | removeformat",
  menubar: false,
  height: 120,
}
const contentEditorConfig = {
  toolbar:
    "undo redo | styles blocks fontfamily fontsize | bold italic underline | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent blockquote | link unlink | image media table charmap | visualblocks removeformat",
  menubar: false,
  height: 360,
}

const prefix = computed(() => (form.mode === "course" ? "PortfolioCourse" : "PortfolioPersonal"))
const listRoute = computed(() => ({
  name: `${prefix.value}List`,
  params: form.mode === "course" ? { node: route.params.node } : {},
  query: contextParams(),
}))
const templateOptions = computed(() =>
  form.templates.map((template) => ({ label: plainText(template.title), value: template.id })),
)
const categoryOptions = computed(() =>
  form.categories.map((category) => ({
    label: `${category.parentId ? "— " : ""}${plainText(category.label)}`,
    value: category.id,
  })),
)
const visibilityOptions = computed(() => {
  const options = [
    { label: t("Visible"), value: 1 },
    { label: t("Hidden"), value: 0 },
  ]
  if (form.mode === "course") {
    options.splice(1, 0, { label: t("Visible only to teachers"), value: 2 })
    if (form.advancedSharingEnabled) {
      options.push({ label: t("Choose recipients"), value: 3 })
    }
  }

  return options
})

function emptyForm() {
  return {
    id: null,
    mode: route.meta.portfolioMode || "personal",
    title: "",
    content: "",
    categoryId: null,
    visibility: 1,
    recipientIds: [],
    tagIds: [],
    extraValues: {},
    extraFiles: {},
    categories: [],
    templates: [],
    tags: [],
    extraFields: [],
    attachments: [],
    recipientOptions: [],
    newAttachments: [],
    attachmentDescriptions: [],
    isNew: true,
    canEdit: false,
    advancedSharingEnabled: false,
    titleAsHtml: false,
    csrfToken: "",
  }
}

function firstQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function contextParams() {
  const params = {}
  const cid = Number(firstQueryValue(route.query.cid) || 0)
  const sid = Number(firstQueryValue(route.query.sid) || 0)
  if (cid > 0) params.cid = cid
  if (sid > 0) params.sid = sid

  return params
}

function formParams() {
  const params = contextParams()
  const id = Number(route.params.id || 0)
  if (id > 0) params.id = id

  return params
}

function plainText(value) {
  const element = document.createElement("div")
  element.innerHTML = String(value || "")

  return String(element.textContent || element.innerText || "").trim()
}

function normalizeExtraValue(field, value) {
  if (Number(field.type) === 13) {
    return [true, 1, "1", "true", "yes", "on"].includes(value)
  }
  if (Number(field.type) === 5) {
    return Array.isArray(value) ? value : String(value || "").split(";").filter(Boolean)
  }

  return value ?? ""
}

function applyResponse(response) {
  const defaults = emptyForm()
  Object.assign(form, defaults, response, {
    recipientIds: Array.isArray(response.recipientIds) ? response.recipientIds.map(Number) : [],
    tagIds: Array.isArray(response.tagIds) ? response.tagIds.map(Number) : [],
    newAttachments: [],
    attachmentDescriptions: [],
    extraFiles: {},
  })
  const values = response.extraValues && typeof response.extraValues === "object" ? response.extraValues : {}
  form.extraValues = {}
  form.extraFields.forEach((field) => {
    form.extraValues[field.id] = normalizeExtraValue(field, values[field.id] ?? field.defaultValue)
  })
}

function applyTemplate() {
  const template = form.templates.find((item) => Number(item.id) === Number(selectedTemplateId.value))
  if (!template) return
  form.title = template.title || ""
  form.content = template.content || ""
  form.categoryId = template.categoryId || null
}

function selectExtraFile(fieldId, event) {
  const file = event.target?.files?.[0]
  if (file) form.extraFiles[fieldId] = file
}

function toastError(error) {
  return error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
}

async function loadForm() {
  isLoading.value = true
  loadErrorMessage.value = ""
  try {
    applyResponse(await portfolioService.getForm(formParams()))
  } catch (error) {
    console.error("Error loading Portfolio form", error)
    loadErrorMessage.value = toastError(error)
  } finally {
    isLoading.value = false
  }
}

async function deleteAttachment(attachment) {
  try {
    await portfolioService.itemAction(
      form.id,
      { action: "delete_attachment", attachmentId: attachment.id, csrfToken: form.csrfToken },
      contextParams(),
    )
    form.attachments = form.attachments.filter((item) => Number(item.id) !== Number(attachment.id))
    toast.add({ severity: "success", summary: t("Success"), detail: t("Deleted"), life: 3000 })
  } catch (error) {
    toast.add({ severity: "error", summary: t("Error"), detail: toastError(error), life: 5000 })
  }
}

async function saveItem() {
  formSubmitted.value = true
  if (!plainText(form.title) || !plainText(form.content)) {
    toast.add({ severity: "warn", summary: t("Warning"), detail: t("Please complete all required fields."), life: 5000 })
    return
  }
  if (Number(form.visibility) === 3 && form.recipientIds.length === 0) {
    toast.add({ severity: "warn", summary: t("Warning"), detail: t("Choose recipients"), life: 5000 })
    return
  }

  const payload = {
    title: form.title,
    content: form.content,
    categoryId: form.categoryId,
    visibility: Number(form.visibility),
    recipientIds: form.recipientIds,
    tagIds: form.tagIds,
    extraValues: form.extraValues,
    extraFiles: form.extraFiles,
    attachments: form.newAttachments,
    attachmentDescriptions: form.attachmentDescriptions,
    csrfToken: form.csrfToken,
  }

  isSaving.value = true
  try {
    const response = form.id
      ? await portfolioService.update(form.id, payload, contextParams())
      : await portfolioService.create(payload, contextParams())
    toast.add({ severity: "success", summary: t("Success"), detail: t(form.id ? "Updated" : "Created"), life: 3000 })
    await router.push({
      name: `${prefix.value}Item`,
      params: form.mode === "course" ? { node: route.params.node, id: response.id } : { id: response.id },
      query: contextParams(),
    })
  } catch (error) {
    console.error("Error saving Portfolio item", error)
    toast.add({ severity: "error", summary: t("Error"), detail: toastError(error), life: 6000 })
  } finally {
    isSaving.value = false
  }
}

watch(
  () => route.fullPath,
  () => loadForm(),
  { immediate: true },
)
</script>
