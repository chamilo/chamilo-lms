<template>
  <Card
    :class="customClass"
  >
    <template #header>
      <slot name="header"></slot>
    </template>
    <template #content>
      <slot></slot>
    </template>
  </Card>
</template>

<script setup>
import Card from 'primevue/card'
import {computed} from "vue"

const props = defineProps({
  plain: {
    type: Boolean,
    default: false,
  },
})

const customClass = computed(() => {
  let resultClass = ''
  if (props.plain) {
    resultClass += 'bg-gray-15 border border-gray-25 shadow-none '
  }
  return resultClass
})
</script>
