<template>
  <form
    action="/api/glossaries/export"
    method="post"
    @submit="submitForm"
  >
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
        is-submit
      />
    </LayoutFormButtons>

    <input
      name="cid"
      type="hidden"
      :value="cid"
    />
    <input
      name="sid"
      type="hidden"
      :value="sid"
    />
    <input
      name="format"
      type="hidden"
      :value="selectedFormat"
    />
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

const submitForm = () => {
  notification.showSuccessNotification(t("Glossary exported"))
}
</script>
