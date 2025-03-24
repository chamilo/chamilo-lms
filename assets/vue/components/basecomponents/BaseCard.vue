<template>
  <Card :class="customClass">
    <template
      v-if="slots.header"
      #header
    >
      <slot name="header"></slot>
    </template>
    <template
      v-if="slots.title"
      #title
    >
      <slot name="title"></slot>
    </template>
    <template
      v-if="slots.footer"
      #footer
    >
      <slot name="footer"></slot>
    </template>
    <template #content>
      <slot></slot>
    </template>
  </Card>
</template>

<script setup>
import Card from "primevue/card"
import { computed, useSlots } from "vue"

const props = defineProps({
  plain: {
    type: Boolean,
    default: false,
  },
})

const slots = useSlots()

const customClass = computed(() => {
  let resultClass = ""
  if (props.plain) {
    resultClass += "bg-gray-15 border border-gray-25 shadow-none "
  }
  return resultClass
})
</script>
