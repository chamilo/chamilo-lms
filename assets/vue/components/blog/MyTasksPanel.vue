<template>
  <div class="space-y-2">
    <div
      v-if="!items.length"
      class="text-sm text-gray-500"
    >
      {{ t("No tasks") }}
    </div>
    <div
      v-for="it in items"
      :key="it.id"
      class="text-sm flex items-center justify-between gap-2"
    >
      <div class="truncate">
        • <span class="font-medium">{{ it.title }}</span>
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

const items = computed(() => {
  return props.assignments.map(a => ({
    id: a.id,
    taskId: a.taskId,
    title: props.tasks.find(t => t.id===a.taskId)?.title || `#${a.taskId}`,
    targetDate: a.targetDate,
  }))
})
</script>
