<template>
  <Menu
    :id="id"
    ref="menu"
    :model="innerModel"
    :popup="true"
    class="app-topbar__user-submenu"
  />
  <!-- this class should use tailwind in the future  -->
</template>

<script setup>
import Menu from "primevue/menu";
import {ref, computed} from "vue";

const props = defineProps({
  id: {
    type: String,
    required: true,
  },
  model: {
    type: Array,
    required: true,
  },
})

const innerModel = computed(() => {
  return props.model.map(e => {
    return {
      ...e,
      'class': 'text-primary',
    };
  });
})

const menu = ref(null);

const toggle = (event) => {
  menu.value.toggle(event);
};

defineExpose({
  toggle,
});
</script>
