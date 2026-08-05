<template>
  <form @submit.prevent="submitForm">
    <BaseSelect
      id="format"
      v-model="selectedFormat"
      :label="t('Export format')"
      :options="formats"
    />

    <LayoutFormButtons>
      <BaseButton
        :label="t('Back')"
        icon="back"
        type="black"
        @click="emit('backPressed')"
      />
      <BaseButton
        :label="isExporting ? t('Exporting...') : t('Export')"
        icon="file-export"
        type="secondary"
        is-submit
      />
    </LayoutFormButtons>
  </form>
</template>

<script setup>
import { useI18n } from "vue-i18n"
import { ref } from "vue"
import LayoutFormButtons from "../layout/LayoutFormButtons.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseSelect from "../basecomponents/BaseSelect.vue"
import { getCourseContext } from "../../utils/courseContext"
import { useNotification } from "../../composables/notification"
import glossaryService from "../../services/glossaryService"

const { t } = useI18n()
const { sid, cid } = getCourseContext()
const notification = useNotification()

const emit = defineEmits(["backPressed", "exported"])

const formats = [
  { label: "CSV", value: "csv" },
  { label: "Excel", value: "xls" },
  { label: "PDF", value: "pdf" },
]

const mimeTypes = {
  csv: "text/csv;charset=UTF-8",
  xls: "application/vnd.ms-excel",
  pdf: "application/pdf",
}

const selectedFormat = ref("csv")
const isExporting = ref(false)

const submitForm = async () => {
  if (isExporting.value) {
    return
  }

  isExporting.value = true

  try {
    const formData = new FormData()
    formData.append("cid", normalizeContextValue(cid))
    formData.append("sid", normalizeContextValue(sid))
    formData.append("format", selectedFormat.value)

    const response = await glossaryService.export(formData)
    downloadExport(response, selectedFormat.value)

    notification.showSuccessNotification(t("Glossary exported"))
    emit("exported")
  } catch (error) {
    console.error("[Glossary] Error exporting glossary:", error)
    notification.showErrorNotification(t("Could not export glossary"))
  } finally {
    isExporting.value = false
  }
}

function normalizeContextValue(value) {
  if (value && typeof value === "object" && "value" in value) {
    return value.value || ""
  }

  return value || ""
}

function downloadExport(response, format) {
  const headers = response?.headers || {}
  const contentType = headers["content-type"] || mimeTypes[format] || "application/octet-stream"
  const blob = response.data instanceof Blob ? response.data : new Blob([response.data], { type: contentType })
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement("a")

  link.href = url
  link.download = getFilename(headers["content-disposition"], format)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)

  window.setTimeout(() => {
    window.URL.revokeObjectURL(url)
  }, 1000)
}

function getFilename(contentDisposition, format) {
  const fallback = `glossary.${format}`

  if (!contentDisposition) {
    return fallback
  }

  const utf8Match = contentDisposition.match(/filename\*=UTF-8''([^;]+)/i)
  if (utf8Match?.[1]) {
    return decodeURIComponent(utf8Match[1].replace(/"/g, ""))
  }

  const asciiMatch = contentDisposition.match(/filename="?([^";]+)"?/i)
  if (asciiMatch?.[1]) {
    return asciiMatch[1]
  }

  return fallback
}
</script>
