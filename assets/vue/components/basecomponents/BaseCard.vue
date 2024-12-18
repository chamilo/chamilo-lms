<template>
  <Card :class="customClass">
    <template
      #header
      v-if="slots.header"
    >
      <slot name="header"></slot>
    </template>
    <template
      #title
      v-if="slots.title"
    >
      <slot name="title"></slot>
    </template>
    <template
      #footer
      v-if="slots.footer"
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
