<template>
  <BaseDialog
    v-model:isVisible="visible"
    :header-icon="headerIconComputed"
    :title="dialogTitleComputed"
    :width="'560px'"
    @update:isVisible="(v) => !v && $emit('close')"
  >
    <div class="space-y-3">
      <BaseInputText
        id="t-title"
        v-model="title"
        :form-submitted="submitted"
        :is-invalid="!title"
        :label="t('Title')"
      />
      <BaseInputText
        id="t-desc"
        v-model="description"
        :label="t('Description')"
      />
      <div>
        <label class="text-sm block mb-1">{{ t("Color") }}</label>
        <input
          v-model="color"
          class="h-9 w-16 border rounded"
          type="color"
        />
      </div>

      <div v-if="isEdit && canEditStatusTemplate">
        <label class="text-sm block mb-1">{{ t("Template type") }}</label>
        <select
          v-model="systemTask"
          class="border rounded h-9 px-2 w-full"
        >
          <option :value="false">Standard</option>
          <option :value="true">System</option>
        </select>
      </div>
    </div>
    <template #footer>
      <BaseButton
        :disabled="!title"
        :icon="isEdit ? 'check' : 'check'"
        :isLoading="saving"
        :label="isEdit ? t('Save') : t('Create')"
        type="primary"
        @click="submit"
      />
    </template>
  </BaseDialog>
</template>

<script setup>
import { computed, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import BaseDialog from "../basecomponents/BaseDialog.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import service from "../../services/blogs"

const { t } = useI18n()
const emit = defineEmits(["close", "created", "saved"])

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

watch(
  () => props.initial,
  (v) => {
    title.value = v?.title || ""
    description.value = v?.description || ""
    color.value = v?.color || "#0ea5e9"
    systemTask.value = !!v?.system
  },
  { deep: true },
)

const dialogTitleComputed = computed(() => (isEdit.value ? t("Edit task") : t("New task")))
const headerIconComputed = computed(() => (isEdit.value ? "pencil" : "plus"))

function close() {
  visible.value = false
  emit("close")
}

async function submit() {
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
      await service.createTask({
        title: title.value.trim(),
        description: description.value,
        color: color.value,
        systemTask: !!systemTask.value,
      })
      emit("created")
    }
    close()
  } finally {
    saving.value = false
  }
}
</script>
