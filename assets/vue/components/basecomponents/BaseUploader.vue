<script setup>
import Uppy from "@uppy/core"
import { Dashboard } from "@uppy/vue"
import Webcam from "@uppy/webcam"
import Audio from "@uppy/audio"
import es_ES from "@uppy/locales/lib/es_ES"
import en_US from "@uppy/locales/lib/en_US"
import fr_FR from "@uppy/locales/lib/fr_FR"
import de_DE from "@uppy/locales/lib/de_DE"
import it_IT from "@uppy/locales/lib/it_IT"
import pl_PL from "@uppy/locales/lib/pl_PL"
import pt_PT from "@uppy/locales/lib/pt_PT"

const XHRUpload = require("@uppy/xhr-upload")
const ImageEditor = require("@uppy/image-editor")

import "@uppy/core/dist/style.css"
import "@uppy/dashboard/dist/style.css"
import "@uppy/image-editor/dist/style.css"
import "@uppy/webcam/dist/style.css"
import "@uppy/audio/dist/style.css"
import { useLocale } from "../../composables/locale"

const { appLocale } = useLocale()
const supportedLanguages = {
  es: es_ES,
  en: en_US,
  fr: fr_FR,
  de: de_DE,
  it: it_IT,
  pl: pl_PL,
  pt: pt_PT,
}

function getUppyLanguageConfig(appLocale) {
  const defaultLang = en_US

  if (typeof appLocale !== 'string') {
    return defaultLang
  }

  const localePrefix = appLocale.split('_')[0]

  return supportedLanguages[localePrefix] || defaultLang
}

const locale = getUppyLanguageConfig(appLocale.value)

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
  locale: locale,
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
