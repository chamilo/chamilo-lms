<template>
  <Dialog
    v-model:visible="isVisible"
    :modal="true"
    :style="{ width: '600px' }"
    header="Upload Correction"
    @hide="onDialogHide"
  >
    <Dashboard
      v-if="uppyInstance"
      :uppy="uppyInstance"
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
import { ref, watch, onBeforeUnmount, shallowRef, markRaw, computed } from "vue"
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

const uppy = shallowRef(null)
const uppyInstance = computed(() => uppy.value)

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
  // Always recreate to guarantee clean state per open.
  destroyUppy()

  const instance = markRaw(
    new Uppy({
      restrictions: { maxNumberOfFiles: 1 },
      autoProceed: true,
    }),
  )

  instance.on("file-added", async (file) => {
    try {
      const formData = new FormData()
      formData.append("uploadFile", file.data)

      const uploadUrl =
        `${ENTRYPOINT}c_student_publication_corrections/upload` +
        `?parentResourceNodeId=${props.parentResourceNodeId}` +
        `&submissionId=${props.submissionId}` +
        `&filetype=file`

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
      // Keep console output for debugging, but show a user-friendly notification.
      console.error("[UppyModalUploader] Upload failed", error)
      showErrorNotification(error)
    }
  })

  uppy.value = instance
}

function destroyUppy() {
  if (!uppy.value) return

  try {
    // Cancel running uploads (if any) before closing.
    uppy.value.cancelAll()

    // Uppy provides close() for cleanup.
    if (typeof uppy.value.close === "function") {
      uppy.value.close({ reason: "unmount" })
    }
  } catch (e) {
    console.warn("[UppyModalUploader] Failed to destroy Uppy instance", e)
  } finally {
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
