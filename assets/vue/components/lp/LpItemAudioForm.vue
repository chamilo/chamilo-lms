<template>
  <div class="space-y-4">
    <section class="space-y-3 rounded-lg border border-gray-20 bg-white p-4">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <h3 class="text-body-1 font-semibold text-gray-90">
          {{ t("Audio") }}
        </h3>

        <BaseButton
          v-if="item.hasAudio"
          id="lp-item-audio-remove"
          :disabled="saving"
          :label="t('Remove audio')"
          icon="delete"
          size="small"
          type="danger-text"
          @click="removeAudio"
        />
      </div>

      <audio
        v-if="item.audioUrl"
        :key="item.audioUrl"
        :aria-label="item.audioTitle || t('Audio')"
        class="w-full"
        controls
        preload="metadata"
        :src="item.audioUrl"
      />

      <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_auto] md:items-end">
        <BaseSelect
          id="lp-item-audio-document"
          v-model="selectedDocumentId"
          :disabled="saving || audioOptions.length === 0"
          :label="t('Select an audio file from documents')"
          name="audioDocumentId"
          :options="audioOptions"
          option-label="label"
          option-value="value"
        />

        <BaseButton
          id="lp-item-audio-attach"
          :disabled="saving || selectedDocumentId <= 0"
          :label="t('Save')"
          icon="save"
          type="success"
          @click="attachSelectedAudio"
        />
      </div>

      <div
        v-if="audioOptions.length === 0"
        class="rounded-md bg-gray-10 px-3 py-2 text-body-2 text-gray-50"
      >
        {{ t("No data available") }}
      </div>
    </section>

    <section class="space-y-3 rounded-lg border border-gray-20 bg-white p-4">
      <h3 class="text-body-1 font-semibold text-gray-90">
        {{ t("Audio file") }}
      </h3>

      <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_auto] md:items-end">
        <BaseFileUpload
          id="lp-item-audio-upload-file"
          accept="audio/*,.aac,.m4a,.mp3,.ogg,.wav,.webm"
          :label="t('Audio file')"
          name="audioFile"
          @file-selected="selectedFile = $event"
        />

        <BaseButton
          id="lp-item-audio-upload"
          :disabled="uploading || !selectedFile || documentsRootNodeId <= 0"
          :label="t('Upload')"
          icon="file-upload"
          type="success"
          @click="uploadAudio"
        />
      </div>
    </section>

    <section class="space-y-3 rounded-lg border border-gray-20 bg-white p-4">
      <h3 class="text-body-1 font-semibold text-gray-90">
        {{ t("Record audio") }}
      </h3>

      <DocumentAudioRecorder
        v-if="documentsRootNodeId > 0"
        :parent-resource-node-id="String(documentsRootNodeId)"
        @document-not-saved="handleRecordedAudioError"
        @document-saved="handleRecordedAudioSaved"
      />
    </section>
  </div>
</template>

<script setup>
import { computed, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseFileUpload from "../basecomponents/BaseFileUpload.vue"
import BaseSelect from "../basecomponents/BaseSelect.vue"
import DocumentAudioRecorder from "../documents/DocumentAudioRecorder.vue"
import { RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"
import { useNotification } from "../../composables/notification"
import lpService from "../../services/lpService"

const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
  audioItems: {
    type: Array,
    default: () => [],
  },
  context: {
    type: Object,
    required: true,
  },
  csrfToken: {
    type: String,
    required: true,
  },
  lpId: {
    type: Number,
    required: true,
  },
  documentsRootNodeId: {
    type: Number,
    default: 0,
  },
})

const emit = defineEmits(["saved"])
const { t } = useI18n()
const { showErrorNotification, showSuccessNotification } = useNotification()

const saving = ref(false)
const uploading = ref(false)
const selectedDocumentId = ref(Number(props.item.audioDocumentId || 0))
const selectedFile = ref(null)

const audioOptions = computed(() =>
  props.audioItems
    .map((audio) => ({
      label: String(audio.title || audio.originalName || ""),
      value: Number(audio.id || 0),
    }))
    .filter((audio) => audio.value > 0),
)

watch(
  () => props.item,
  (item) => {
    selectedDocumentId.value = Number(item?.audioDocumentId || 0)
  },
)

function getDocumentId(document) {
  const payload = document?.data || document
  const directId = Number(payload?.iid || payload?.id || 0)
  if (directId > 0) {
    return directId
  }

  const iri = String(payload?.["@id"] || "")
  const match = iri.match(/\/(\d+)\/?$/)

  return match ? Number(match[1]) : 0
}

async function saveAudioDocument(documentId) {
  saving.value = true

  try {
    await lpService.updateBuilderItemAudio(props.lpId, Number(props.item.id), props.context, {
      documentId: documentId > 0 ? documentId : null,
      csrfToken: props.csrfToken,
    })
    showSuccessNotification(t("Saved"))
    emit("saved", Number(props.item.id))
  } catch (error) {
    showErrorNotification(error)
  } finally {
    saving.value = false
  }
}

async function attachSelectedAudio() {
  await saveAudioDocument(Number(selectedDocumentId.value || 0))
}

async function removeAudio() {
  selectedDocumentId.value = 0
  await saveAudioDocument(0)
}

async function uploadAudio() {
  if (!(selectedFile.value instanceof File) || props.documentsRootNodeId <= 0) {
    return
  }

  uploading.value = true

  try {
    const formData = new FormData()
    formData.append("title", selectedFile.value.name)
    formData.append("filetype", "file")
    formData.append("uploadFile", selectedFile.value)
    formData.append("parentResourceNodeId", String(props.documentsRootNodeId))
    formData.append("resourceLinkList", JSON.stringify([{ visibility: RESOURCE_LINK_PUBLISHED }]))

    const document = await lpService.createBuilderDocument(props.context, formData)
    const documentId = getDocumentId(document)
    if (documentId <= 0) {
      throw new Error("The uploaded audio document identifier is missing.")
    }

    selectedFile.value = null
    selectedDocumentId.value = documentId
    await saveAudioDocument(documentId)
  } catch (error) {
    showErrorNotification(error)
  } finally {
    uploading.value = false
  }
}

async function handleRecordedAudioSaved(document) {
  const documentId = getDocumentId(document)
  if (documentId <= 0) {
    showErrorNotification(new Error("The recorded audio document identifier is missing."))
    return
  }

  selectedDocumentId.value = documentId
  await saveAudioDocument(documentId)
}

function handleRecordedAudioError(error) {
  showErrorNotification(error)
}
</script>
