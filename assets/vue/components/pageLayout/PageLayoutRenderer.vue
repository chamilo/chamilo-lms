<template>
  <div class="flex gap-6 flex-wrap w-full">
    <div
      v-for="col in layout?.page?.layout?.columns"
      :key="col.id"
      :style="{ width: col.width }"
      class="border border-gray-300 rounded p-4 min-h-[200px] bg-white shadow flex-1"
    >
      <h3 class="text-center font-semibold mb-4">Column {{ col.id }}</h3>

      <div class="space-y-4">
        <component
          v-for="block in col.blocks"
          :is="getBlockComponent(block.type)"
          :key="block.id"
          :block="block"
        />
      </div>
    </div>
  </div>
</template>
<script setup>
import blockComponents from "./blocks"

const props = defineProps({
  layout: {
    type: Object,
    default: null,
  },
})

function getBlockComponent(type) {
  if (!type) {
    return {
      template: `<div class="text-red-600">Block type missing</div>`,
    }
  }

  return (
    blockComponents[type] || {
      template: `<div class="text-red-600">Unknown block type: ${type}</div>`,
    }
  )
}
</script>
