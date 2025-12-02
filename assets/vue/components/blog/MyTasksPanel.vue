<template>
  <div class="space-y-2">
    <div v-if="!items.length" class="text-sm text-gray-500">
      {{ t("No tasks") }}
    </div>
    <div
      v-for="it in items"
      :key="it.id"
      class="text-sm flex items-center justify-between gap-2"
    >
      <div class="truncate">
        • <span class="font-medium">{{ it.title }}</span>
        <span class="ml-2 text-xs px-2 py-0.5 rounded border">
          {{ it.statusText }}
        </span>
      </div>
      <span class="text-xs text-gray-500">{{ it.targetDate }}</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue"
import { useI18n } from "vue-i18n"

const props = defineProps({
  assignments: { type: Array, default: () => [] },
  tasks: { type: Array, default: () => [] },
})

const { t } = useI18n()

function statusLabel(s) {
  switch (Number(s)) {
    case 1: return t("In progress")
    case 2: return t("Pending validation")
    case 3: return t("Done")
    default: return t("Open")
  }
}

const items = computed(() => {
  return props.assignments.map(a => {
    const tRow = props.tasks.find(tk => tk.id === a.taskId)
    return {
      id: a.id,
      taskId: a.taskId,
      title: tRow?.title || `#${a.taskId}`,
      targetDate: a.targetDate,
      statusText: statusLabel(a.status),
    }
  })
})
</script>
