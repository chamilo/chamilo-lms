<script setup>
import { computed, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import BaseAdvancedSettingsButton from "../basecomponents/BaseAdvancedSettingsButton.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import BaseSelect from "../basecomponents/BaseSelect.vue"
import BaseTinyEditor from "../basecomponents/BaseTinyEditor.vue"
import { useNotification } from "../../composables/notification"
import lpService from "../../services/lpService"

const props = defineProps({
  certificate: { type: Object, default: () => ({}) },
  context: { type: Object, required: true },
  csrfToken: { type: String, required: true },
  documentsRootNodeId: { type: Number, required: true },
  lpId: { type: Number, required: true },
})

const emit = defineEmits(["saved"])
const { t } = useI18n()
const { showErrorNotification, showSuccessNotification } = useNotification()

const saving = ref(false)
const formSubmitted = ref(false)
const showAdvancedSettings = ref(false)
const form = reactive({
  title: "",
  content: "",
  documentId: 0,
  gradebookCategoryId: null,
})

const hasValidTitle = computed(() => Boolean(String(form.title || "").trim()))
const gradebookCategories = computed(() => props.certificate?.gradebookCategories || [])

watch(
  () => props.certificate,
  (certificate) => {
    form.title = String(certificate?.title || "")
    form.content = String(certificate?.content || certificate?.defaultContent || "")
    form.documentId = Number(certificate?.documentId || 0)
    form.gradebookCategoryId = certificate?.gradebookCategoryId || null
    showAdvancedSettings.value = Boolean(form.gradebookCategoryId)
  },
  { immediate: true, deep: true },
)

function getDocumentId(document) {
  const directId = Number(document?.iid || document?.id || 0)
  if (directId > 0) {
    return directId
  }

  const iri = String(document?.["@id"] || "")
  const match = iri.match(/\/api\/documents\/(\d+)/)

  return match ? Number(match[1]) : 0
}

async function saveCertificate() {
  formSubmitted.value = true
  if (!hasValidTitle.value || props.documentsRootNodeId <= 0) {
    return
  }

  saving.value = true
  try {
    let documentId = Number(form.documentId || 0)

    if (documentId > 0) {
      const document = await lpService.updateBuilderDocument(documentId, props.context, {
        title: String(form.title).trim(),
        contentFile: String(form.content || ""),
        parentResourceNodeId: Number(props.documentsRootNodeId),
      })
      documentId = getDocumentId(document) || documentId
    } else {
      const formData = new FormData()
      formData.append("title", String(form.title).trim())
      formData.append("comment", "")
      formData.append("filetype", "certificate")
      formData.append("contentFile", String(form.content || ""))
      formData.append("contentFileExtension", "html")
      formData.append("contentFileMimeType", "text/html")
      formData.append("parentResourceNodeId", String(props.documentsRootNodeId))
      formData.append("resourceLinkList", JSON.stringify([{ visibility: 1 }]))

      const document = await lpService.createBuilderDocument(props.context, formData)
      documentId = getDocumentId(document)
    }

    if (!documentId) {
      throw new Error(t("An error occurred. Please try again."))
    }

    const result = await lpService.saveBuilderFinalItem(props.lpId, props.context, {
      documentId,
      title: String(form.title).trim(),
      gradebookCategoryId: form.gradebookCategoryId ? Number(form.gradebookCategoryId) : null,
      csrfToken: props.csrfToken,
    })

    form.documentId = documentId
    showSuccessNotification(t("Saved"))
    emit("saved", result)
  } catch (error) {
    showErrorNotification(error)
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="space-y-4">
    <BaseInputText
      id="lp-certificate-title"
      v-model="form.title"
      :error-text="t('The title is required.')"
      :form-submitted="formSubmitted"
      :is-invalid="formSubmitted && !hasValidTitle"
      :label="t('Title')"
      name="title"
      required
    />

    <div class="rounded-lg border border-info/20 bg-info/10 p-4 text-body-2 text-gray-90">
      <div class="mb-2 font-semibold">
        {{ t("Options") }}
      </div>
      <div>((certificate))</div>
      <div>((skill))</div>
    </div>

    <BaseTinyEditor
      editor-id="lp-certificate-content"
      v-model="form.content"
      :editor-config="{ height: 480 }"
      :title="t('Content')"
      full-page
    />

    <BaseAdvancedSettingsButton v-model="showAdvancedSettings">
      <BaseSelect
        id="lp-certificate-gradebook-category"
        v-model="form.gradebookCategoryId"
        allow-clear
        :label="t('Gradebook options')"
        :options="gradebookCategories"
        name="gradebookCategoryId"
        option-label="label"
        option-value="value"
      />
    </BaseAdvancedSettingsButton>

    <div class="flex justify-end">
      <BaseButton
        :is-loading="saving"
        :label="t('Save')"
        icon="save"
        type="success"
        @click="saveCertificate"
      />
    </div>
  </div>
</template>
