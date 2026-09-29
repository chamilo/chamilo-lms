<script setup>
import { useI18n } from "vue-i18n"
import BaseIcon from "../basecomponents/BaseIcon.vue"

defineOptions({ name: "LpBuilderResourceList" })

const { t } = useI18n()

const props = defineProps({
  items: { type: Array, default: () => [] },
  canManage: { type: Boolean, default: false },
  depth: { type: Number, default: 0 },
})

const emit = defineEmits(["add"])

function iconFor(item) {
  if (item.isFolder) {
    return "folder-generic"
  }

  const icons = {
    document: "file-text",
    video: "file-video",
    quiz: "multiple-marked",
    link: "link",
    student_publication: "inbox",
    forum: "comment",
    thread: "comment",
    survey: "form-dropdown",
  }

  return icons[item.resourceType] || "file-generic"
}

function displayTitle(item) {
  return item.titleKey ? t(item.titleKey) : item.title
}

function depthClass() {
  const classes = ["", "pl-6", "pl-10", "pl-14", "pl-16"]

  return classes[Math.min(props.depth, classes.length - 1)]
}

function startDrag(event, item) {
  if (!props.canManage || !item.canAdd) {
    event.preventDefault()
    return
  }

  event.dataTransfer.effectAllowed = "copy"
  event.dataTransfer.setData("application/x-chamilo-lp-resource", JSON.stringify(item))
}

function addItem(item) {
  if (props.canManage && item.canAdd) {
    emit("add", item)
  }
}
</script>

<template>
  <div
    v-if="items.length"
    :class="depth === 0 ? 'divide-y divide-gray-20 rounded-lg border border-gray-20 bg-white' : ''"
  >
    <template
      v-for="item in items"
      :key="`${item.resourceType}-${item.id}`"
    >
      <div
        :draggable="canManage && item.canAdd"
        class="flex items-center gap-2 border-gray-20 px-3 py-3"
        :class="[
          depthClass(),
          canManage && item.canAdd ? 'cursor-grab' : '',
          item.visible === false ? 'text-gray-50' : '',
        ]"
        @dragstart="startDrag($event, item)"
      >
        <BaseIcon
          v-if="canManage && item.canAdd"
          icon="cursor-move"
          class="shrink-0 text-primary"
          size="small"
        />
        <BaseIcon
          :icon="iconFor(item)"
          class="shrink-0 text-primary"
          size="small"
        />

        <div class="min-w-0 flex-1">
          <button
            v-if="canManage && item.canAdd"
            class="block max-w-full truncate text-left text-body-2 hover:underline"
            :class="item.visible === false ? 'text-gray-50' : 'text-primary'"
            type="button"
            @click="addItem(item)"
          >
            {{ displayTitle(item) }}
          </button>
          <div
            v-else
            class="truncate text-body-2 text-gray-90"
          >
            {{ displayTitle(item) }}
          </div>
          <div
            v-if="item.questionCount === 0"
            class="text-caption text-gray-50"
          >
            {{ t("You must add at least one question") }}
          </div>
        </div>
      </div>

      <LpBuilderResourceList
        v-if="item.children?.length"
        :can-manage="canManage"
        :depth="depth + 1"
        :items="item.children"
        @add="emit('add', $event)"
      />
    </template>
  </div>

  <div
    v-else-if="depth === 0"
    class="rounded-lg border border-dashed border-gray-25 p-6 text-center text-body-2 text-gray-50"
  >
    {{ t("No data available") }}
  </div>
</template>
