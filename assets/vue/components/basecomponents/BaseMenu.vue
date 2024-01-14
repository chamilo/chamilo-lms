<template>
  <Menu
    :id="id"
    ref="menu"
    :model="model"
    :popup="true"
    class="app-topbar__user-submenu"
  />
  <!-- this class should use tailwind in the future  -->
</template>

<script setup>
import Menu from "primevue/menu";
import {ref} from "vue";

defineProps({
  id: {
    type: String,
    required: true,
  },
  // https://primevue.org/menu/#api.options.MenuItem
  model: {
    type: Array,
    required: true,
  },
});

const menu = ref(null);

const toggle = (event) => {
  menu.value.toggle(event);
};

defineExpose({
  toggle,
});
</script>
