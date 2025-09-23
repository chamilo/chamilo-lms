<template>
  <DashboardModal
    v-if="ready"
    :uppy="uppy"
    :open="visible"
    :close-after-finish="false"
    :hide-upload-button="true"
    :show-progress-details="false"
    :proudly-display-powered-by-uppy="false"
    @close="handleClose"
    :done-button-handler="handleDone"
  />
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue"
import Uppy from "@uppy/core"
import { DashboardModal } from "@uppy/vue"
import "@uppy/core/dist/style.css"
import "@uppy/dashboard/dist/style.css"

const props = defineProps({
  visible: { type: Boolean, default: false },
})
const emit = defineEmits(["close", "files-selected", "file-added"])

const uppy = ref(null)
const ready = ref(false)

function handleClose() {
  emit("close")
}

function handleDone() {
  // Return all selected files without uploading
  const files = Object.values(uppy.value.getFiles()).map(f => f.data)
  if (files.length) emit("files-selected", files)
  handleClose()
}

onMounted(() => {
  uppy.value = new Uppy({
    autoProceed: false,
    allowMultipleUploads: true,
    restrictions: { maxNumberOfFiles: null },
  })

  uppy.value.on("file-added", file => emit("file-added", file))

  ready.value = true
})

onBeforeUnmount(() => {
  if (uppy.value) {
    try { uppy.value.cancelAll() } catch {}
    try { uppy.value.reset() } catch {}
    uppy.value = null
  }
})
</script>
