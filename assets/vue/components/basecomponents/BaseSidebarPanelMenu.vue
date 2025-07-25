<script setup>
import { ref, watch } from "vue"
import PanelMenu from "primevue/panelmenu"
import BaseIcon from "./BaseIcon.vue"

const modelValue = defineModel({
  type: Array,
  required: true,
})

const expandedKeys = ref({})

watch(
  () => modelValue.value,
  (val) => {
    expandedKeys.value = buildExpandedKeys(val || [])
  },
  { immediate: true }
)

function buildExpandedKeys(items) {
  const keys = {}
  for (const item of items || []) {
    if (item.expanded) {
      keys[item.key] = true
    }
    if (item.items) {
      Object.assign(keys, buildExpandedKeys(item.items))
    }
  }
  return keys
}
</script>

<template>
  <PanelMenu
    :model="modelValue"
    :expandedKeys="expandedKeys"
    @update:expandedKeys="expandedKeys = $event"
  >
    <template #item="{ item, root, active, props }">
      <BaseAppLink
        v-if="item.route || item.url"
        :class="{ 'p-panelmenu-header-action': root, 'p-menuitem-link': !root }"
        :to="item.route"
        :url="item.url"
      >
        <span v-bind="props.icon" />
        <span
          v-bind="props.label"
          v-text="item.label"
        />
      </BaseAppLink>
      <a
        v-else-if="root"
        class="p-panelmenu-header-action"
      >
        <span v-bind="props.icon" />
        <span
          v-bind="props.label"
          v-text="item.label"
        />
        <BaseIcon
          :icon="active ? 'fold' : 'unfold'"
          class="p-icon p-submenu-icon"
        />
      </a>
    </template>
  </PanelMenu>
</template>
