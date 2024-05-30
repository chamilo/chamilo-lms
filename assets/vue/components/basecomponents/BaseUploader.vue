<script setup>
import Uppy from "@uppy/core"
import { Dashboard } from "@uppy/vue"
import Webcam from "@uppy/webcam"
import Audio from "@uppy/audio"

const XHRUpload = require("@uppy/xhr-upload")
const ImageEditor = require("@uppy/image-editor")

import "@uppy/core/dist/style.css"
import "@uppy/dashboard/dist/style.css"
import "@uppy/image-editor/dist/style.css"
import "@uppy/webcam/dist/style.css"
import "@uppy/audio/dist/style.css"

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
  <Dashboard
    :plugins="['Webcam', 'ImageEditor', 'Audio']"
    :props="{
      proudlyDisplayPoweredByUppy: false,
    }"
    :uppy="uppy"
  />
</template>
