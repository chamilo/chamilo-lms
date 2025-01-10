<script setup>
import { RouterLink } from "vue-router"
import { computed } from "vue"

defineOptions({
  inheritAttrs: false,
})

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
    v-else
    v-slot="{ href, navigate }"
    custom
    v-bind="$props"
  >
    <a
      :href="href"
      v-bind="$attrs"
      @click="navigate"
    >
      <slot />
    </a>
  </router-link>
</template>
