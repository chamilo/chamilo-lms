<template>
  <form>
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
        :label="t('Export')"
        icon="file-export"
        type="secondary"
        @click="submitForm"
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
import { useCidReq } from "../../composables/cidReq"
import { useNotification } from "../../composables/notification"
import glossaryService from "../../services/glossaryService"

const { t } = useI18n()
const { sid, cid } = useCidReq()
const notification = useNotification()

const emit = defineEmits(["backPressed"])

const formats = [
  { label: "CSV", value: "csv" },
  { label: "Excel", value: "xls" },
  { label: "PDF", value: "pdf" },
]
const selectedFormat = ref("csv")

const submitForm = async () => {
  const format = selectedFormat.value

  const formData = new FormData()
  formData.append("format", format)
  formData.append("sid", sid)
  formData.append("cid", cid)

  try {
    const data = await glossaryService.export(formData)
    const fileUrl = window.URL.createObjectURL(new Blob([data]))
    const link = document.createElement("a")
    link.href = fileUrl
    link.setAttribute("download", `glossary.${format}`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)

    notification.showSuccessNotification(t("Glossary exported"))
  } catch (error) {
    console.error("Error exporting glossary:", error)
    notification.showErrorNotification(t("Could not export glossary"))
  }
}
</script>
