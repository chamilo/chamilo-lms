<template>
  <BaseDialog v-model:isVisible="visible" :title="t('New Task')" header-icon="plus" :width="'560px'">
    <div class="space-y-3">
      <BaseInputText id="t-title" :label="t('Title')" v-model="title" :form-submitted="submitted" :is-invalid="!title" />
      <BaseInputText id="t-desc" :label="t('Description')" v-model="description" />
      <div>
        <label class="text-sm block mb-1">{{ t("Color") }}</label>
        <input type="color" v-model="color" class="h-9 w-16 border rounded" />
      </div>
      <div class="flex justify-end gap-2">
        <BaseButton type="black" icon="close" :label="t('Cancel')" @click="close" />
        <BaseButton type="primary" icon="check" :label="t('Create')" :disabled="!title" :isLoading="saving" @click="submit" />
      </div>
    </div>
  </BaseDialog>
</template>

<script setup>
import { ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseDialog from "../basecomponents/BaseDialog.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import service from "../../services/blogs"

const { t } = useI18n()
const emit = defineEmits(["close","created"])
const visible = ref(true)
const submitted = ref(false)
const saving = ref(false)
const title = ref("")
const description = ref("")
const color = ref("#0ea5e9")

function close(){ visible.value=false; emit("close") }
async function submit(){
  submitted.value = true
  if (!title.value.trim()) return
  saving.value = true
  try {
    await service.createTask({ title: title.value.trim(), description: description.value, color: color.value })
    emit("created")
    close()
  } finally { saving.value = false }
}
</script>
