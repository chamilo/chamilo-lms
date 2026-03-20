<template>
  <Menu
    :id="id"
    ref="menu"
    :model="model"
    :popup="true"
    class="app-topbar__user-submenu"
  >
    <template #item="{ item, props }">
      <router-link
        v-if="item.route"
        v-slot="{ href, navigate }"
        :to="item.route"
        custom
      >
        <a
          :href="href"
          v-bind="props.action"
          @click="navigate"
        >
          <span
            v-if="item.icon"
            :class="item.icon"
            class="mr-2"
          />
          <span>{{ item.label }}</span>
        </a>
      </router-link>
      <a
        v-else
        :href="item.url"
        :target="item.target"
        v-bind="props.action"
      >
        <span
          v-if="item.icon"
          :class="item.icon"
          class="mr-2"
        />
        <span>{{ item.label }}</span>
      </a>
    </template>
  </Menu>
</template>

<script setup>
import Menu from "primevue/menu"
import { ref } from "vue"

defineProps({
  id: {
    type: String,
    required: true,
  },
  model: {
    type: Array,
    required: true,
  },
})

const menu = ref(null)

const toggle = (event) => {
  menu.value.toggle(event)
}

defineExpose({
  toggle,
})
</script>
