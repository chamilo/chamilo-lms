<template>
  <i class="text-xl/4" :class="iconClass"/>
</template>

<script setup>
import {computed} from "vue";
import {chamiloIconToClass} from "./ChamiloIcons";


const props = defineProps({
  icon: {
    type: String,
    required: true,
    validator: (value) => {
      if (typeof (value) !== "string") {
        return false
      }
      return Object.keys(chamiloIconToClass).includes(value)
    }
  },
});

const iconClass = computed(() => {
  return chamiloIconToClass[props.icon];
});
</script>
