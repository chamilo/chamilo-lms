<template>
  <Dialog
    v-model:visible="visible"
    modal
    :header="t('Edit submission')"
    :style="{ width: '500px' }"
    @hide="onHide"
  >
    <div class="space-y-4">
      <InputText
        v-model="form.title"
        :placeholder="t('Title')"
        class="w-full"
      />

      <div class="flex items-center gap-2">
        <a
          v-if="props.item && props.item.downloadUrl"
          :href="props.item.downloadUrl"
          class="btn btn--primary"
          target="_self"
        >
          <BaseIcon icon="download" />
          {{ t("Download file") }}
        </a>
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
import BaseIcon from "../basecomponents/BaseIcon.vue"

const props = defineProps({
  modelValue: Boolean,
  item: Object,
})

const emit = defineEmits(["update:modelValue", "updated"])

const visible = ref(false)
const form = ref({
  title: "",
  description: "",
  sendMail: false,
})

const { t } = useI18n()
const notification = useNotification()

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

function onHide() {
  emit("update:modelValue", false)
}

async function submit() {
  try {
    await cStudentPublicationService.updateSubmission(props.item.iid, {
      title: form.value.title,
      description: form.value.description,
      sendMail: form.value.sendMail,
    })
    notification.showSuccessNotification(t("Submission updated!"))
    emit("updated")
    emit("update:modelValue", false)
  } catch (error) {
    notification.showErrorNotification(error)
  }
}
</script>
