<template>
  <div class="space-y-3">
    <Dashboard
      :plugins="['ImageEditor']"
      :props="{
        proudlyDisplayPoweredByUppy: false,
        hideCancelButton: true,
        height: 230,
        restrictions: {
          allowedFileTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
          maxNumberOfFiles: 1,
          maxFileSize: 10485760,
        },
      }"
      :uppy="uppy"
    />
    <p class="text-caption text-gray-500">
      {{
        t(
          "Recommended course picture: 1600 × 900 px (16:9). Use at least 1280 × 720 px for sharp display on larger or high-density screens.",
        )
      }}
    </p>
    <p class="text-caption text-gray-500">
      {{ t("Use the image editor before uploading to crop, rotate or resize the course picture.") }}
    </p>
  </div>
</template>

<script setup>
import Uppy from "@uppy/core"
import { Dashboard } from "@uppy/vue"
import ImageEditor from "@uppy/image-editor"
import XHRUpload from "@uppy/xhr-upload"
import { onBeforeUnmount, shallowRef } from "vue"
import { useI18n } from "vue-i18n"
import { useUppyLocale } from "../../composables/uppyLocale"

import "@uppy/core/dist/style.css"
import "@uppy/dashboard/dist/style.css"
import "@uppy/image-editor/dist/style.css"

const { t } = useI18n()
const { uppyLocale } = useUppyLocale()

const props = defineProps({
  endpoint: {
    type: String,
    required: true,
  },
})

const emit = defineEmits(["uploaded", "error"])

const uppy = shallowRef(
  new Uppy({
    autoProceed: false,
    locale: uppyLocale.value,
    restrictions: {
      allowedFileTypes: ["image/jpeg", "image/png", "image/gif", "image/webp"],
      maxNumberOfFiles: 1,
      maxFileSize: 10 * 1024 * 1024,
    },
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
      fieldName: "file",
      formData: true,
    })
    .on("upload-success", (_file, response) => {
      emit("uploaded", response?.body || {})
    })
    .on("upload-error", (_file, error) => {
      emit("error", error)
    }),
)

onBeforeUnmount(() => {
  if (typeof uppy.value?.close === "function") {
    uppy.value.close({ reason: "unmount" })
  }
})
</script>
