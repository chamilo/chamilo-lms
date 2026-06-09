<script setup>
import Uppy from "@uppy/core"
import { Dashboard } from "@uppy/vue"
import Webcam from "@uppy/webcam"
import Audio from "@uppy/audio"
import XHRUpload from "@uppy/xhr-upload"
import ImageEditor from "@uppy/image-editor"

import "@uppy/core/dist/style.css"
import "@uppy/dashboard/dist/style.css"
import "@uppy/image-editor/dist/style.css"
import "@uppy/webcam/dist/style.css"
import "@uppy/audio/dist/style.css"
import { useUppyLocale } from "../../composables/uppyLocale"

const { uppyLocale } = useUppyLocale()

const props = defineProps({
  endpoint: {
    type: String,
    required: true,
  },
  fieldName: {
    type: String,
    required: true,
  },
  autoProceed: {
    type: Boolean,
    required: false,
    default: false,
  },
})

const emit = defineEmits(["upload", "upload-success", "complete"])

const uppy = new Uppy({
  autoProceed: props.autoProceed,
  locale: uppyLocale.value,
})
  .use(ImageEditor, {
    cropperOptions: {
      viewMode: 1,
      background: false,
      autoCropArea: 1,
      responsive: true,
    },
    actions: {
      revert: true,
      rotate: true,
      granularRotate: true,
      flip: true,
      zoomIn: true,
      zoomOut: true,
      cropSquare: true,
      cropWidescreen: true,
      cropWidescreenVertical: true,
    },
  })
  .use(XHRUpload, {
    endpoint: props.endpoint,
    formData: true,
    fieldName: props.fieldName,
  })
  .use(Webcam)
  .use(Audio)
  .on("upload", ({ id, fileIDs }) => emit("upload", { id, fileIDs }))
  .on("upload-success", (file, { body }) => emit("upload-success", { file, response: body }))
  .on("complete", ({ successful, failed }) => emit("complete", { successful, failed }))
</script>

<template>
  <div class="ch-uppy-dashboard">
    <Dashboard
      :plugins="['Webcam', 'ImageEditor', 'Audio']"
      :props="{
        proudlyDisplayPoweredByUppy: false,
        hideCancelButton: true,
      }"
      :uppy="uppy"
    />
  </div>
</template>


<style scoped>
.ch-uppy-dashboard :deep(.uppy-StatusBar-actions) {
  @apply flex flex-wrap items-center justify-end gap-2;
}

.ch-uppy-dashboard :deep(.uppy-StatusBar-actionBtn--upload) {
  @apply inline-flex min-h-10 items-center justify-center rounded-xl border-0 bg-primary px-4 py-2 text-body-2 font-semibold text-white shadow-sm transition;
}

.ch-uppy-dashboard :deep(.uppy-StatusBar-actionBtn--upload:hover),
.ch-uppy-dashboard :deep(.uppy-StatusBar-actionBtn--upload:focus) {
  @apply bg-primary text-white;
}

.ch-uppy-dashboard :deep(.uppy-StatusBar-actionBtn--upload:disabled) {
  @apply cursor-not-allowed opacity-60;
}

.ch-uppy-dashboard :deep(.uppy-StatusBar-actionBtn--cancel) {
  @apply hidden;
}
</style>
