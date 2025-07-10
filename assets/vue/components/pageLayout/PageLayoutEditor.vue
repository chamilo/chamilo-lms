<template>
  <div class="flex flex-col space-y-6 w-full">
    <div
      v-if="layoutColumns.length"
      class="flex gap-4 w-full"
    >
      <div
        v-for="column in layoutColumns"
        :key="column.id"
        class="flex-1 border border-gray-300 rounded p-4 bg-gray-50 min-h-[200px]"
      >
        <h3 class="text-center font-semibold mb-4">
          {{ t("Column") }} {{ column.id }}
        </h3>

        <draggable
          v-model="column.blocks"
          group="blocks"
          animation="200"
          item-key="id"
          class="space-y-2"
        >
          <template #item="{ element }">
            <div
              class="p-2 bg-white border rounded shadow cursor-move flex justify-between items-center"
            >
              <span>{{ element.name }}</span>
              <BaseIcon
                icon="drag"
                v-if="!readonly"
              />
            </div>
          </template>
        </draggable>
      </div>
    </div>
    <div
      v-if="!readonly"
      class="w-full bg-white p-4 rounded shadow"
    >
      <h2 class="text-lg font-semibold mb-2">
        {{ t("Blocks Palette") }}
      </h2>
      <div class="flex flex-wrap gap-3">
        <div
          v-for="block in blocksPalette"
          :key="block.id"
          class="cursor-pointer bg-gray-100 p-3 rounded shadow hover:bg-gray-200 flex items-center gap-2"
          @click="addBlockToFirstColumn(block)"
        >
          <BaseIcon
            :icon="block.icon"
            class="text-primary"
          />
          <span>{{ block.name }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import draggable from "vuedraggable"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"

const props = defineProps({
  modelValue: Object,
  templateOptions: Array,
  readonly: Boolean,
})

const emit = defineEmits(["update:modelValue"])

const { t } = useI18n()

const layoutColumns = ref([])
const pageId = ref(null)
const pageTitle = ref(null)

const blocksPalette = [
  { id: "text", name: "Text Block", icon: "text" },
  { id: "image", name: "Image Block", icon: "image" },
  { id: "button", name: "Button Block", icon: "button" },
]

watch(
  () => props.modelValue,
  (newVal) => {
    if (newVal?.page?.layout?.columns?.length) {
      pageId.value = newVal.page.id ?? null
      pageTitle.value = newVal.page.title ?? null
      layoutColumns.value = JSON.parse(
        JSON.stringify(newVal.page.layout.columns)
      )
    } else {
      pageId.value = null
      pageTitle.value = null
      layoutColumns.value = []
    }
    emitLayout()
  },
  { immediate: true }
)

function addBlockToFirstColumn(block) {
  if (!layoutColumns.value.length) return
  layoutColumns.value[0].blocks.push({
    ...block,
    id: Date.now(),
  })
  emitLayout()
}

function emitLayout() {
  emit("update:modelValue", {
    page: {
      id: pageId.value ?? null,
      title: pageTitle.value ?? null,
      layout: {
        columns: layoutColumns.value,
      },
    },
  })
}
</script>

<style scoped>
.min-h-\[200px\] {
  min-height: 200px;
}
</style>
