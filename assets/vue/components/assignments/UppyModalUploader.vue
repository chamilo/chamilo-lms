<template>
  <Dialog
    v-model:visible="isVisible"
    :modal="true"
    :style="{ width: '600px' }"
    header="Upload Correction"
    @hide="onDialogHide"
  >
    <Dashboard
      v-if="uppy"
      :uppy="uppy"
      :height="300"
      :showProgressDetails="true"
      :hideUploadButton="false"
      :hidePauseResumeButton="false"
      :hideCancelButton="false"
      note="Only one file allowed"
    />
  </Dialog>
</template>

<script setup>
import { ref, watch, onBeforeUnmount } from "vue"
import { Dashboard } from "@uppy/vue"
import Uppy from "@uppy/core"
import Dialog from "primevue/dialog"
import { useNotification } from "../../composables/notification"
import { ENTRYPOINT } from "../../config/entrypoint"
import axios from "axios"
import "@uppy/core/dist/style.css"
import "@uppy/dashboard/dist/style.css"

const props = defineProps({
  parentResourceNodeId: {
    type: Number,
    required: true,
  },
  submissionId: {
    type: Number,
    required: true,
  },
  visible: {
    type: Boolean,
    required: true,
  },
})

const emit = defineEmits(["close", "uploaded"])

const isVisible = ref(false)
const uppy = ref(null)
const { showErrorNotification, showSuccessNotification } = useNotification()

watch(
  () => props.visible,
  (newVal) => {
    isVisible.value = newVal
    if (newVal) {
      setupUppy()
    } else {
      destroyUppy()
    }
  },
)

function setupUppy() {
  destroyUppy()

  uppy.value = new Uppy({
    restrictions: { maxNumberOfFiles: 1 },
    autoProceed: true,
  })

  uppy.value.on("file-added", async (file) => {
    try {
      const formData = new FormData()
      formData.append("uploadFile", file.data)

      const uploadUrl = `${ENTRYPOINT}c_student_publication_corrections/upload?parentResourceNodeId=${props.parentResourceNodeId}&submissionId=${props.submissionId}&filetype=file`

      await axios.post(uploadUrl, formData, {
        headers: {
          "Content-Type": "multipart/form-data",
          Accept: "application/json",
        },
      })

      showSuccessNotification("Correction uploaded successfully!")
      emit("uploaded", file)
      closeUploader()
    } catch (error) {
      console.error(error)
      showErrorNotification(error)
    }
  })
}

function destroyUppy() {
  if (uppy.value) {
    uppy.value.cancelAll()
    if (typeof uppy.value.close === "function") {
      uppy.value.close()
    }
    uppy.value = null
  }
}

function closeUploader() {
  destroyUppy()
  emit("close")
}

function onDialogHide() {
  closeUploader()
}

onBeforeUnmount(() => {
  destroyUppy()
})
</script>
