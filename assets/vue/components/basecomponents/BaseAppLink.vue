<script setup>
import { RouterLink } from "vue-router"
import { computed } from "vue"

const props = defineProps({
  ...RouterLink.props,
  url: {
    type: String,
    required: false,
    default: null,
  },
})

const isAnchor = computed(() => !!props.url)
</script>

<template>
  <a
    v-if="isAnchor"
    :href="url !== '#' ? url : undefined"
    v-bind="$attrs"
  >
    <slot />
  </a>
  <router-link
    v-else-if="props.to"
    v-bind="props"
  >
    <slot />
  </router-link>
  <span v-else>
    <slot />
  </span>
</template>
