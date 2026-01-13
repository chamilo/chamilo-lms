<template>
  <DashboardModal
    v-if="ready"
    :uppy="uppy"
    :open="visible"
    :props="{
      closeAfterFinish: false,
      hideUploadButton: true,
      showProgressDetails: false,
      proudlyDisplayPoweredByUppy: false,
      maxNumberOfFiles: 1,
    }"
    @close="handleClose"
    :done-button-handler="handleDone"
  />
</template>

<script setup>
import { shallowRef, ref, onMounted, onBeforeUnmount, markRaw } from "vue"
import Uppy from "@uppy/core"
import { DashboardModal } from "@uppy/vue"
import "@uppy/core/dist/style.css"
import "@uppy/dashboard/dist/style.css"

const props = defineProps({
  visible: { type: Boolean, default: false },
})
const emit = defineEmits(["close", "files-selected", "file-added"])
const uppy = shallowRef(null)
const ready = ref(false)
let onFileAddedHandler = null

function handleClose() {
  emit("close")
}

function handleDone() {
  // Return all selected files without uploading
  const files = (uppy.value?.getFiles?.() ?? []).map((f) => f.data)
  if (files.length) emit("files-selected", files)
  handleClose()
}

onMounted(() => {
  uppy.value = markRaw(
    new Uppy({
      autoProceed: false,
      allowMultipleUploads: true,
      restrictions: { maxNumberOfFiles: null },
    }),
  )

  onFileAddedHandler = (file) => emit("file-added", file)
  uppy.value?.on?.("file-added", onFileAddedHandler)

  ready.value = true
})

onBeforeUnmount(() => {
  if (!uppy.value) return

  try {
    if (onFileAddedHandler) {
      uppy.value?.off?.("file-added", onFileAddedHandler)
    }
  } catch {}

  try {
    uppy.value.cancelAll()
  } catch {}
  try {
    uppy.value.reset()
  } catch {}

  try {
    uppy.value.close?.()
  } catch {}

  uppy.value = null
})
</script>
