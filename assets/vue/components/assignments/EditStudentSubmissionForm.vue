<template>
  <Dialog
    v-model:visible="visible"
    modal
    :header="t('Edit Submission')"
    :style="{ width: '500px' }"
  >
    <div class="space-y-4">
      <InputText
        v-model="form.title"
        :placeholder="t('Title')"
        class="w-full"
      />

      <div class="flex items-center gap-2">
        <Button
          icon="pi pi-download"
          class="p-button-sm"
          :label="t('Download')"
          @click="downloadFile"
        />
      </div>

      <Textarea
        v-model="form.description"
        :placeholder="t('Description')"
        rows="5"
        class="w-full"
      />

      <div class="flex items-center gap-2">
        <BaseCheckbox
          id="senemail"
          v-model="form.sendMail"
          :label="t('Send mail to student')"
          name="senemail"
        />
      </div>

      <div class="flex justify-end gap-2">
        <Button
          :label="t('Cancel')"
          class="p-button-text"
          @click="cancel"
        />
        <Button
          :label="t('Update')"
          @click="submit"
        />
      </div>
    </div>
  </Dialog>
</template>

<script setup>
import { ref, watch } from "vue"
import { useNotification } from "../../composables/notification"
import cStudentPublicationService from "../../services/cstudentpublication"
import BaseCheckbox from "../basecomponents/BaseCheckbox.vue"
import { useI18n } from "vue-i18n"
import Textarea from "primevue/textarea"

const props = defineProps({
  modelValue: Boolean,
  item: Object,
})

const emit = defineEmits(["update:modelValue", "updated"])

const notification = useNotification()

const visible = ref(false)
const form = ref({
  title: "",
  description: "",
  sendMail: false,
})

const { t } = useI18n()

watch(
  () => props.modelValue,
  (newVal) => {
    visible.value = newVal
    if (newVal && props.item) {
      form.value.title = props.item.title || ""
      form.value.description = props.item.description || ""
      form.value.sendMail = false
    }
  },
)

function cancel() {
  emit("update:modelValue", false)
}

async function submit() {
  try {
    await cStudentPublicationService.updateSubmission(props.item.iid, {
      title: form.value.title,
      description: form.value.description,
      sendMail: form.value.sendMail,
    })
    notification.showSuccessNotification("Submission updated!")
    emit("updated")
    emit("update:modelValue", false)
  } catch (error) {
    notification.showErrorNotification(error)
  }
}

function downloadFile() {
  if (props.item && props.item.resourceNode) {
    const id = props.item.resourceNode.split("/").pop()
    window.open(`/document/download.php?id=${id}&cidreq=${props.item.cidreq || ""}`, "_blank")
  }
}
</script>
