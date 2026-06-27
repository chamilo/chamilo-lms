<script setup>
import { computed, nextTick, ref } from "vue"
import Draggable from "vuedraggable"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseIcon from "../basecomponents/BaseIcon.vue"

defineOptions({ name: "LpBuilderTree" })

const RESOURCE_MIME_TYPE = "application/x-chamilo-lp-resource"

const props = defineProps({
  items: { type: Array, required: true },
  selectedId: { type: Number, default: 0 },
  canManage: { type: Boolean, default: false },
  parentId: { type: Number, default: 0 },
})

const emit = defineEmits([
  "update:items",
  "select",
  "edit",
  "prerequisite",
  "delete",
  "structure-changed",
  "resource-drop",
])

const externalDropActive = ref(false)

const itemsModel = computed({
  get: () => props.items,
  set: (value) => emit("update:items", value),
})

function canMove(event) {
  return !event.draggedContext?.element?.isFinal
}

function updateChildren(item, children) {
  item.children = children
}

function isExternalResourceDrag(event) {
  const types = Array.from(event.dataTransfer?.types || [])

  return types.includes(RESOURCE_MIME_TYPE)
}

function handleExternalDragOver(event) {
  if (!props.canManage || !isExternalResourceDrag(event)) {
    return
  }

  event.preventDefault()
  externalDropActive.value = true

  if (event.dataTransfer) {
    event.dataTransfer.dropEffect = "copy"
  }
}

function handleExternalDragLeave(event) {
  const currentTarget = event.currentTarget
  const relatedTarget = event.relatedTarget

  if (currentTarget && relatedTarget && currentTarget.contains(relatedTarget)) {
    return
  }

  externalDropActive.value = false
}

function handleResourceDrop(event, parentId) {
  if (!isExternalResourceDrag(event)) {
    externalDropActive.value = false
    return
  }

  event.preventDefault()
  event.stopPropagation()
  externalDropActive.value = false

  const raw = event.dataTransfer?.getData(RESOURCE_MIME_TYPE)
  if (!raw) {
    return
  }

  try {
    const resource = JSON.parse(raw)
    emit("resource-drop", { resource, parentId: parentId || null })
  } catch {
    // Ignore data that does not come from the learning path resource selector.
  }
}

async function handleStructureChanged() {
  externalDropActive.value = false

  // Cross-list moves update the source and destination models in separate steps.
  // Wait until both recursive lists have settled before serializing the full tree.
  await nextTick()
  emit("structure-changed")
}
</script>

<template>
  <Draggable
    v-model="itemsModel"
    :animation="150"
    :disabled="!canManage"
    :dragover-bubble="false"
    :empty-insert-threshold="80"
    :fallback-on-body="true"
    :group="{ name: 'learning-path-builder', pull: true, put: true }"
    :move="canMove"
    :swap-threshold="0.65"
    class="min-h-14 space-y-2 rounded-lg"
    handle=".lp-builder-drag-handle"
    item-key="id"
    @end="handleStructureChanged"
  >
    <template #item="{ element }">
      <div>
        <div
          class="rounded-lg border px-2 py-2 transition"
          :class="selectedId === element.id ? 'border-primary bg-primary/5' : 'border-gray-20 bg-white hover:bg-gray-10'"
        >
          <div class="flex items-center gap-2">
            <button
              v-if="canManage && !element.isFinal"
              :aria-label="$t('Move')"
              :title="$t('Move')"
              class="lp-builder-drag-handle grid h-7 w-7 shrink-0 cursor-move place-content-center text-primary"
              type="button"
            >
              <BaseIcon
                icon="cursor-move"
                size="small"
              />
            </button>
            <span
              v-else
              class="inline-block h-7 w-7 shrink-0"
            />

            <BaseIcon
              :icon="element.isSection ? 'folder-generic' : 'file-text'"
              size="small"
            />

            <button
              class="min-w-0 flex-1 text-left"
              type="button"
              @click="emit('select', element.id)"
            >
              <span class="block truncate text-body-2 font-semibold text-gray-90">
                {{ element.displayTitle || element.title }}
              </span>
            </button>
          </div>

          <div
            v-if="canManage"
            class="mt-1 flex items-center gap-1 pl-9"
          >
            <BaseButton
              :label="$t('Edit')"
              icon="edit"
              only-icon
              size="small"
              type="secondary-text"
              @click="emit('edit', element.id)"
            />
            <BaseButton
              v-if="!element.isSection"
              :label="$t('Prerequisites')"
              icon="graph"
              only-icon
              size="small"
              type="primary-text"
              @click="emit('prerequisite', element.id)"
            />
            <BaseButton
              :label="$t('Delete')"
              icon="delete"
              only-icon
              size="small"
              type="danger-text"
              @click="emit('delete', element.id)"
            />
          </div>
        </div>

        <div
          v-if="element.isSection"
          class="ml-5 mt-2 border-l border-gray-20 pl-3"
        >
          <LpBuilderTree
            :can-manage="canManage"
            :items="element.children"
            :parent-id="element.id"
            :selected-id="selectedId"
            @delete="emit('delete', $event)"
            @edit="emit('edit', $event)"
            @prerequisite="emit('prerequisite', $event)"
            @resource-drop="emit('resource-drop', $event)"
            @select="emit('select', $event)"
            @structure-changed="emit('structure-changed')"
            @update:items="updateChildren(element, $event)"
          />
        </div>
      </div>
    </template>

    <template #footer>
      <div
        v-if="canManage"
        class="lp-builder-drop-zone mt-2 flex min-h-14 items-center justify-center rounded-lg border border-dashed px-3 py-4 text-center text-body-2 transition"
        :class="externalDropActive ? 'border-primary bg-primary/10 text-primary' : 'border-support-3 bg-support-1 text-support-5 hover:border-primary hover:bg-primary/5'"
        @dragenter="handleExternalDragOver"
        @dragleave="handleExternalDragLeave"
        @dragover="handleExternalDragOver"
        @drop="handleResourceDrop($event, parentId)"
      >
        {{ $t("Drag and drop an element here") }}
      </div>
    </template>
  </Draggable>
</template>
