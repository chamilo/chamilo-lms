<template>
  <BaseDialog
    v-model:isVisible="visible"
    :title="t('Assign task')"
    :width="'560px'"
    header-icon="account-plus"
    @update:isVisible="(v) => !v && $emit('close')"
  >
    <div class="space-y-3">
      <BaseSelect
        v-model="taskId"
        :options="tasks"
        :placeholder="t('Select a task')"
        label=""
        optionLabel="title"
        optionValue="id"
      />
      <BaseSelect
        v-model="userId"
        :options="members"
        :placeholder="t('Select a user')"
        label=""
        optionLabel="name"
        optionValue="id"
      />
      <BaseCalendar
        id="target-date"
        v-model="date"
        :label="t('Target date')"
      />
    </div>
    <template #footer>
      <BaseButton
        :disabled="!canSubmit"
        :isLoading="saving"
        :label="t('Assign')"
        icon="check"
        type="primary"
        @click="submit"
      />
    </template>
  </BaseDialog>
</template>

<script setup>
import { computed, ref } from "vue"
import { useI18n } from "vue-i18n"

import service from "../../services/blogs"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseSelect from "../basecomponents/BaseSelect.vue"
import BaseDialog from "../basecomponents/BaseDialog.vue"
import BaseCalendar from "../basecomponents/BaseCalendar.vue"

const { t } = useI18n()
const props = defineProps({
  blogId: { type: Number, required: true },
  tasks: { type: Array, default: () => [] },
  members: { type: Array, default: () => [] },
})
const emit = defineEmits(["close", "assigned"])
const visible = ref(true)
const taskId = ref(null)
const userId = ref(null)
const date = ref(new Date())
const saving = ref(false)
const canSubmit = computed(() => !!taskId.value && !!userId.value && !!date.value)

function close() {
  visible.value = false
  emit("close")
}
async function submit() {
  if (!canSubmit.value) return
  saving.value = true
  try {
    await service.assignTask({
      blogId: props.blogId,
      taskId: taskId.value,
      userId: userId.value,
      targetDate: date.value instanceof Date ? date.value.toISOString().slice(0, 10) : date.value,
    })
    emit("assigned")
    close()
  } finally {
    saving.value = false
  }
}
</script>
