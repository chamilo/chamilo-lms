<template>
  <BaseDialog
    v-model:isVisible="visible"
    :title="dialogTitleComputed"
    :header-icon="headerIconComputed"
    :width="'560px'"
  >
    <div class="space-y-3">
      <BaseInputText id="t-title" :label="t('Title')" v-model="title" :form-submitted="submitted" :is-invalid="!title" />
      <BaseInputText id="t-desc" :label="t('Description')" v-model="description" />
      <div>
        <label class="text-sm block mb-1">{{ t("Color") }}</label>
        <input type="color" v-model="color" class="h-9 w-16 border rounded" />
      </div>

      <div v-if="isEdit && canEditStatusTemplate">
        <label class="text-sm block mb-1">{{ t("Template type") }}</label>
        <select v-model="systemTask" class="border rounded h-9 px-2 w-full">
          <option :value="false">Standard</option>
          <option :value="true">System</option>
        </select>
      </div>

      <div class="flex justify-end gap-2">
        <BaseButton type="black" icon="close" :label="t('Cancel')" @click="close" />
        <BaseButton
          type="primary"
          :icon="isEdit ? 'check' : 'check'"
          :label="isEdit ? t('Save') : t('Create')"
          :disabled="!title"
          :isLoading="saving"
          @click="submit"
        />
      </div>
    </div>
  </BaseDialog>
</template>

<script setup>
import { ref, computed, watch } from "vue"
import { useI18n } from "vue-i18n"
import BaseDialog from "../basecomponents/BaseDialog.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import service from "../../services/blogs"

const { t } = useI18n()
const emit = defineEmits(["close","created","saved"])

const props = defineProps({
  mode: { type: String, default: "create" },
  initial: { type: Object, default: () => ({ title: "", description: "", color: "#0ea5e9", system: false }) },
  taskId: { type: Number, default: null },
  canEditStatusTemplate: { type: Boolean, default: true }, // teacher/creator only
})

const visible = ref(true)
const submitted = ref(false)
const saving = ref(false)

const isEdit = computed(() => props.mode === "edit")

const title = ref(props.initial.title || "")
const description = ref(props.initial.description || "")
const color = ref(props.initial.color || "#0ea5e9")
const systemTask = ref(!!props.initial.system)

watch(() => props.initial, (v) => {
  title.value = v?.title || ""
  description.value = v?.description || ""
  color.value = v?.color || "#0ea5e9"
  systemTask.value = !!v?.system
}, { deep: true })

const dialogTitleComputed = computed(() => isEdit.value ? t("Edit task") : t("New task"))
const headerIconComputed = computed(() => isEdit.value ? "pencil" : "plus")

function close(){ visible.value=false; emit("close") }

async function submit(){
  submitted.value = true
  if (!title.value.trim()) return
  saving.value = true
  try {
    if (isEdit.value && props.taskId) {
      await service.updateTask(props.taskId, {
        title: title.value.trim(),
        description: description.value,
        color: color.value,
        ...(props.canEditStatusTemplate ? { systemTask: !!systemTask.value } : {}),
      })
      emit("saved")
    } else {
      await service.createTask({ title: title.value.trim(), description: description.value, color: color.value, systemTask: !!systemTask.value })
      emit("created")
    }
    close()
  } finally { saving.value = false }
}
</script>
