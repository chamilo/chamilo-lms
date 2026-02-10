<template>
  <Avatar
    :aria-label="alt"
    :image="finalImageUrl"
    :shape="shape"
    :size="'normal' !== size ? size : undefined"
  />
</template>

<script setup>
import Avatar from "primevue/avatar"
import { computed } from "vue"

const props = defineProps({
  imageUrl: {
    type: String,
    require: true,
    default: "",
  },
  // this sets the aria-label which sets the alt property of the image inside Avatar component
  alt: {
    type: String,
    required: true,
  },
  size: {
    type: String,
    require: false,
    default: "normal",
    validator: (value) => ["normal", "large", "xlarge"].includes(value),
  },
  shape: {
    type: String,
    require: false,
    default: "circle",
    validator: (value) => ["circle", "square"].includes(value),
  },
})

const imageSize = computed(() => {
  // these numbers are approximate; they were calculated with a size
  // allowing to see the image clearly
  if (props.size === "xlarge") {
    return 112
  }

  if (props.size === "large") {
    return 64
  }

  return 32
})

const finalImageUrl = computed(() => {
  if (!props.imageUrl) {
    return `/img/icons/${imageSize.value}/unknown.png`
  }

  return `${props.imageUrl}?w=${imageSize.value}&h=${imageSize.value}&fit=crop`
})
</script>
