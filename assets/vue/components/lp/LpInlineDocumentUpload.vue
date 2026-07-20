<script setup>
import { computed, onUnmounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import Uppy from "@uppy/core"
import XHRUpload from "@uppy/xhr-upload"
import { Dashboard } from "@uppy/vue"
import "@uppy/core/dist/style.css"
import "@uppy/dashboard/dist/style.css"
import BaseAdvancedSettingsButton from "../basecomponents/BaseAdvancedSettingsButton.vue"
import BaseCheckbox from "../basecomponents/BaseCheckbox.vue"
import BaseRadioButtons from "../basecomponents/BaseRadioButtons.vue"
import BaseSelect from "../basecomponents/BaseSelect.vue"
import { useUppyLocale } from "../../composables/uppyLocale"

const props = defineProps({
  context: { type: Object, required: true },
  documentFolderOptions: { type: Array, default: () => [] },
  defaultDocumentParentId: { type: Number, default: 0 },
  lpParentId: { type: [Number, null], default: null },
  fileKind: { type: String, default: "files" },
  searchEnabled: { type: Boolean, default: false },
})

const emit = defineEmits(["uploaded"])
const { t } = useI18n()
const { uppyLocale } = useUppyLocale()

const showAdvancedSettings = ref(true)
const documentParentId = ref(props.defaultDocumentParentId || null)
const fileExistsOption = ref("rename")
const isUncompressZipEnabled = ref(false)
const indexDocumentContent = ref(props.searchEnabled)
const uploadedResources = []

const allowedFileTypes = computed(() => ("videos" === props.fileKind ? ["video/*"] : null))
const endpoint = computed(
  () =>
    `/api/documents?cid=${Number(props.context?.cid || 0)}&sid=${Number(props.context?.sid || 0)}&gid=${Number(props.context?.gid || 0)}`,
)

const uppy = new Uppy({
  autoProceed: false,
  locale: uppyLocale.value,
  restrictions: { allowedFileTypes: allowedFileTypes.value },
}).use(XHRUpload, {
  endpoint: endpoint.value,
  formData: true,
  fieldName: "uploadFile",
})

function getDocumentId(document) {
  const directId = Number(document?.iid || document?.id || 0)
  if (directId > 0) {
    return directId
  }

  const iri = String(document?.["@id"] || "")
  const match = iri.match(/\/api\/documents\/(\d+)/)

  return match ? Number(match[1]) : 0
}

function applyUploadConfiguration() {
  const parentId = Number(documentParentId.value || 0)
  uppy.getPlugin("XHRUpload")?.setOptions({ endpoint: endpoint.value })
  uppy.setOptions({ restrictions: { allowedFileTypes: allowedFileTypes.value } })
  uppy.setMeta({
    filetype: "file",
    parentResourceNodeId: parentId,
    resourceLinkList: JSON.stringify([{ visibility: 1 }]),
    isUncompressZipEnabled: String(Boolean(isUncompressZipEnabled.value)),
    fileExistsOption: fileExistsOption.value,
    indexDocumentContent: String(Boolean(indexDocumentContent.value)),
  })
}

uppy.on("upload-success", (_file, response) => {
  const document = response?.body || null
  const documentId = getDocumentId(document)
  if (!documentId) {
    return
  }

  uploadedResources.push({
    id: documentId,
    title: String(document?.title || ""),
    resourceType: "video" === String(document?.filetype || "").toLowerCase() ? "video" : "document",
    canAdd: true,
  })
})

uppy.on("complete", () => {
  if (uploadedResources.length === 0) {
    return
  }

  const resources = uploadedResources.splice(0)
  window.setTimeout(() => {
    emit("uploaded", {
      resources,
      parentId: props.lpParentId || null,
      exportAllowed: false,
    })
  }, 0)
})

uppy.on("upload-error", (_file, error) => {
  uppy.info(error?.message || t("An error occurred. Please try again."), "error", 7000)
})

watch(
  [endpoint, allowedFileTypes, documentParentId, fileExistsOption, isUncompressZipEnabled, indexDocumentContent],
  applyUploadConfiguration,
  { immediate: true },
)

watch(
  () => props.defaultDocumentParentId,
  (value) => {
    if (!documentParentId.value && Number(value || 0) > 0) {
      documentParentId.value = Number(value)
    }
  },
)

onUnmounted(() => {
  uppy.destroy()
})
</script>

<template>
  <div class="space-y-4">
    <BaseSelect
      id="lp-inline-upload-directory"
      v-model="documentParentId"
      :label="t('Destination folder')"
      :options="documentFolderOptions"
      name="documentParentId"
      option-label="label"
      option-value="value"
    />

    <BaseAdvancedSettingsButton v-model="showAdvancedSettings">
      <div class="space-y-4">
        <BaseRadioButtons
          v-model="fileExistsOption"
          :options="[
            { label: t('Do nothing'), value: 'nothing' },
            { label: t('Overwrite the existing file'), value: 'overwrite' },
            { label: t('Rename the uploaded file if it exists'), value: 'rename' },
          ]"
          name="fileExistsOption"
          :title="t('If file exists')"
        />

        <BaseCheckbox
          id="lp-inline-upload-uncompress"
          v-model="isUncompressZipEnabled"
          :disabled="fileKind === 'videos'"
          :label="t('Uncompress zip')"
          name="uncompress"
        />

        <BaseCheckbox
          v-if="searchEnabled"
          id="lp-inline-upload-index-content"
          v-model="indexDocumentContent"
          :label="t('Index document content?')"
          name="indexDocumentContent"
        />
      </div>
    </BaseAdvancedSettingsButton>

    <Dashboard
      :props="{
        proudlyDisplayPoweredByUppy: false,
        width: '100%',
        height: '320px',
      }"
      :uppy="uppy"
    />
  </div>
</template>
