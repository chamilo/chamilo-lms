<script setup>
import BaseAppLink from "./BaseAppLink.vue"
import PanelMenu from "primevue/panelmenu"
import BaseIcon from "./BaseIcon.vue"

defineModel({
  type: Array,
  required: true,
})
</script>

<template>
  <PanelMenu :model="modelValue">
    <template #item="{ item, root, active, props }">
      <BaseAppLink
        v-if="item.route || item.url"
        :to="item.route"
        :url="item.url"
        :class="{ 'p-panelmenu-header-action': root, 'p-menuitem-link': !root }"
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
