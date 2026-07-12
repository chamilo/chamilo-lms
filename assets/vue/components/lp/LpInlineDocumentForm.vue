<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useNotification } from "../../composables/notification"
import BaseAdvancedSettingsButton from "../basecomponents/BaseAdvancedSettingsButton.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseCheckbox from "../basecomponents/BaseCheckbox.vue"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import BaseSelect from "../basecomponents/BaseSelect.vue"
import BaseTinyEditor from "../basecomponents/BaseTinyEditor.vue"
import TemplateList from "../documents/TemplateList.vue"
import documentsService from "../../services/documents"
import lpService from "../../services/lpService"

const props = defineProps({
  context: { type: Object, required: true },
  documentFolderOptions: { type: Array, default: () => [] },
  lpParentOptions: { type: Array, default: () => [] },
  defaultDocumentParentId: { type: Number, default: 0 },
  defaultLpParentId: { type: [Number, null], default: null },
  searchEnabled: { type: Boolean, default: false },
})

const emit = defineEmits(["created"])
const { t } = useI18n()
const { showErrorNotification } = useNotification()

const saving = ref(false)
const formSubmitted = ref(false)
const showAdvancedSettings = ref(false)
const templates = ref([])
const form = reactive({
  title: "",
  content: "",
  documentParentId: props.defaultDocumentParentId || null,
  lpParentId: props.defaultLpParentId,
  exportAllowed: true,
  indexDocumentContent: props.searchEnabled,
})

const hasValidTitle = computed(() => Boolean(String(form.title || "").trim()))
const hasValidDestination = computed(() => Number(form.documentParentId || 0) > 0)

watch(
  () => props.defaultDocumentParentId,
  (value) => {
    if (!form.documentParentId && Number(value || 0) > 0) {
      form.documentParentId = Number(value)
    }
  },
)

watch(
  () => props.defaultLpParentId,
  (value) => {
    form.lpParentId = value || null
  },
)

onMounted(loadTemplates)

async function loadTemplates() {
  const courseId = Number(props.context?.cid || 0)
  if (!courseId) {
    return
  }

  try {
    const response = await documentsService.getTemplates(courseId)
    templates.value = Array.isArray(response) ? response : response?.items || response?.["hydra:member"] || []
  } catch {
    templates.value = []
  }
}

function selectTemplate(content) {
  form.content = String(content || "")
}

function getDocumentId(document) {
  const directId = Number(document?.iid || document?.id || 0)
  if (directId > 0) {
    return directId
  }

  const iri = String(document?.["@id"] || "")
  const match = iri.match(/\/api\/documents\/(\d+)/)

  return match ? Number(match[1]) : 0
}

async function saveDocument() {
  formSubmitted.value = true
  if (!hasValidTitle.value || !hasValidDestination.value) {
    return
  }

  saving.value = true
  try {
    const formData = new FormData()
    formData.append("title", String(form.title).trim())
    formData.append("comment", "")
    formData.append("filetype", "file")
    formData.append("contentFile", String(form.content || ""))
    formData.append("contentFileExtension", "html")
    formData.append("contentFileMimeType", "text/html")
    formData.append("parentResourceNodeId", String(form.documentParentId))
    formData.append("resourceLinkList", JSON.stringify([{ visibility: 1 }]))
    formData.append("indexDocumentContent", String(Boolean(form.indexDocumentContent)))

    const document = await lpService.createBuilderDocument(props.context, formData)
    const documentId = getDocumentId(document)
    if (!documentId) {
      throw new Error(t("An error occurred. Please try again."))
    }

    emit("created", {
      resource: {
        id: documentId,
        title: String(document?.title || form.title),
        resourceType: "document",
        canAdd: true,
      },
      parentId: form.lpParentId || null,
      exportAllowed: Boolean(form.exportAllowed),
    })

    form.title = ""
    form.content = ""
    form.exportAllowed = true
    formSubmitted.value = false
  } catch (error) {
    showErrorNotification(error)
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="grid gap-4 xl:grid-cols-[minmax(220px,30%)_minmax(0,1fr)]">
    <div class="rounded-lg border border-gray-20 bg-gray-10 p-3">
      <div class="mb-3 text-body-1 font-semibold text-gray-90">
        {{ t("Templates") }}
      </div>

      <TemplateList
        v-if="templates.length"
        :templates="templates"
        @template-selected="selectTemplate"
      />
      <div
        v-else
        class="rounded-lg border border-dashed border-gray-25 p-4 text-center text-caption text-gray-50"
      >
        {{ t("No data available") }}
      </div>
    </div>

    <div class="space-y-4">
      <div class="text-h4 font-semibold text-gray-90">
        {{ t("Add") }}
      </div>

      <BaseInputText
        id="lp-inline-document-title"
        v-model="form.title"
        :error-text="t('The title is required.')"
        :form-submitted="formSubmitted"
        :is-invalid="formSubmitted && !hasValidTitle"
        :label="t('Title')"
        name="title"
        required
      />

      <BaseSelect
        id="lp-inline-document-parent"
        v-model="form.lpParentId"
        :label="t('Parent')"
        :options="lpParentOptions"
        name="parentId"
        option-label="label"
        option-value="value"
      />

      <BaseTinyEditor
        editor-id="lp-inline-document-content"
        v-model="form.content"
        :editor-config="{ height: 360 }"
        :title="t('Content')"
      />

      <BaseCheckbox
        id="lp-inline-document-export-pdf"
        v-model="form.exportAllowed"
        :label="t('Export to PDF')"
        name="exportAllowed"
      />

      <BaseAdvancedSettingsButton
        v-if="searchEnabled"
        v-model="showAdvancedSettings"
      >
        <BaseCheckbox
          id="lp-inline-document-index-content"
          v-model="form.indexDocumentContent"
          :label="t('Index document content?')"
          name="indexDocumentContent"
        />
      </BaseAdvancedSettingsButton>

      <BaseSelect
        id="lp-inline-document-directory"
        v-model="form.documentParentId"
        :is-invalid="formSubmitted && !hasValidDestination"
        :label="t('Destination folder')"
        :options="documentFolderOptions"
        name="documentParentId"
        option-label="label"
        option-value="value"
      />

      <div class="flex justify-end">
        <BaseButton
          :is-loading="saving"
          :label="t('Save')"
          icon="save"
          type="success"
          @click="saveDocument"
        />
      </div>
    </div>
  </div>
</template>
