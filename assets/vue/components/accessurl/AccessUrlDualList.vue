<script setup>
import { ref, computed } from "vue"
import { useI18n } from "vue-i18n"

const { t } = useI18n()

const props = defineProps({
  available: { type: Array, required: true }, // [{ id, label }]
  assigned: { type: Array, required: true }, // [{ id, label }]
  availableTitle: { type: String, required: true },
  assignedTitle: { type: String, required: true },
  disabled: { type: Boolean, default: false },
})

const emit = defineEmits(["add", "remove", "add-all", "remove-all"])

const keyword = ref("")

const filteredAvailable = computed(() => {
  const kw = keyword.value.trim().toLowerCase()
  if (!kw) {
    return props.available
  }

  return props.available.filter((item) => item.label.toLowerCase().includes(kw))
})
</script>

<template>
  <div class="flex flex-col gap-3">
    <input
      v-model="keyword"
      type="text"
      :placeholder="t('Search')"
      class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full max-w-xs"
    />

    <div class="flex flex-col md:flex-row gap-6 items-start">
      <div class="flex-1 flex flex-col gap-2 min-w-0">
        <div class="font-medium text-gray-700">{{ availableTitle }}</div>
        <div class="border border-gray-200 rounded overflow-y-auto h-80 bg-white">
          <ul class="divide-y divide-gray-100">
            <li
              v-for="item in filteredAvailable"
              :key="item.id"
              class="flex items-center justify-between px-3 py-1.5 text-sm hover:bg-gray-50 cursor-pointer"
              @dblclick="!disabled && emit('add', item)"
            >
              <span>{{ item.label }}</span>
              <button
                type="button"
                class="text-green-600 hover:text-green-800 disabled:opacity-40"
                :disabled="disabled"
                :title="t('Add')"
                @click="emit('add', item)"
              >
                <span class="mdi mdi-chevron-right ch-tool-icon" />
              </button>
            </li>
          </ul>
        </div>
        <div class="text-xs text-gray-500">{{ filteredAvailable.length }}</div>
      </div>

      <div class="flex flex-col items-center justify-center gap-4 mt-8">
        <button
          type="button"
          class="w-10 h-10 rounded-full bg-green-100 hover:bg-green-200 shadow flex items-center justify-center disabled:opacity-40"
          :disabled="disabled"
          :title="t('Add all')"
          @click="emit('add-all', filteredAvailable)"
        >
          <span class="mdi mdi-chevron-double-right text-green-700" />
        </button>
        <button
          type="button"
          class="w-10 h-10 rounded-full bg-red-100 hover:bg-red-200 shadow flex items-center justify-center disabled:opacity-40"
          :disabled="disabled"
          :title="t('Remove all')"
          @click="emit('remove-all')"
        >
          <span class="mdi mdi-chevron-double-left text-red-700" />
        </button>

        <slot name="actions" />
      </div>

      <div class="flex-1 flex flex-col gap-2 min-w-0">
        <div class="font-medium text-gray-700">{{ assignedTitle }}</div>
        <div class="border border-gray-200 rounded overflow-y-auto h-80 bg-white">
          <ul class="divide-y divide-gray-100">
            <li
              v-for="item in assigned"
              :key="item.id"
              class="flex items-center justify-between px-3 py-1.5 text-sm hover:bg-gray-50 cursor-pointer"
              @dblclick="!disabled && emit('remove', item)"
            >
              <button
                type="button"
                class="text-red-500 hover:text-red-700 disabled:opacity-40"
                :disabled="disabled"
                :title="t('Remove')"
                @click="emit('remove', item)"
              >
                <span class="mdi mdi-chevron-left ch-tool-icon" />
              </button>
              <span>{{ item.label }}</span>
            </li>
          </ul>
        </div>
        <div class="text-xs text-gray-500">{{ assigned.length }}</div>
      </div>
    </div>
  </div>
</template>
