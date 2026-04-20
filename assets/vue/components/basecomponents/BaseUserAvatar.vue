<template>
  <div
    :class="wrapperClass"
    class="relative inline-flex shrink-0 items-center justify-center overflow-hidden"
    :aria-label="alt"
  >
    <div
      v-if="isLoading"
      class="flex h-full w-full items-center justify-center bg-gray-15"
      aria-hidden="true"
    >
      <i
        class="mdi mdi-loading mdi-spin text-gray-50"
        :class="loadingIconClass"
      ></i>
    </div>

    <Avatar
      v-else-if="showImage"
      :image="loadedImageUrl"
      :shape="shape"
      :size="avatarSize"
      :aria-label="alt"
      class="h-full w-full"
      image-class="h-full w-full object-cover"
    />

    <Avatar
      v-else
      :shape="shape"
      :size="avatarSize"
      :aria-label="alt"
      icon="mdi mdi-account"
      class="h-full w-full bg-gray-15 text-gray-50"
    />
  </div>
</template>

<script setup>
import Avatar from "primevue/avatar"
import { computed, ref, watch } from "vue"

const props = defineProps({
  imageUrl: {
    type: String,
    required: false,
    default: "",
  },
  // This sets the accessible label for the avatar.
  alt: {
    type: String,
    required: true,
  },
  size: {
    type: String,
    required: false,
    default: "normal",
    validator: (value) => ["normal", "large", "xlarge"].includes(value),
  },
  shape: {
    type: String,
    required: false,
    default: "circle",
    validator: (value) => ["circle", "square"].includes(value),
  },
})

const isLoading = ref(false)
const isLoaded = ref(false)
const hasError = ref(false)
const loadedImageUrl = ref("")

const avatarSize = computed(() => {
  return props.size !== "normal" ? props.size : undefined
})

const imageSize = computed(() => {
  if (props.size === "xlarge") {
    return 112
  }

  if (props.size === "large") {
    return 64
  }

  return 32
})

const wrapperClass = computed(() => {
  const shapeClass = props.shape === "circle" ? "rounded-full" : "rounded-xl"

  if (props.size === "xlarge") {
    return `h-28 w-28 ${shapeClass}`
  }

  if (props.size === "large") {
    return `h-16 w-16 ${shapeClass}`
  }

  return `h-8 w-8 ${shapeClass}`
})

const loadingIconClass = computed(() => {
  if (props.size === "xlarge") {
    return "text-3xl"
  }

  if (props.size === "large") {
    return "text-xl"
  }

  return "text-base"
})

const finalImageUrl = computed(() => {
  if (!props.imageUrl) {
    return ""
  }

  if (props.imageUrl.endsWith(".svg")) {
    return props.imageUrl
  }

  const separator = props.imageUrl.includes("?") ? "&" : "?"
  return `${props.imageUrl}${separator}w=${imageSize.value}&h=${imageSize.value}&fit=crop`
})

const showImage = computed(() => {
  return isLoaded.value && !hasError.value && !!loadedImageUrl.value
})

function resetState() {
  isLoading.value = false
  isLoaded.value = false
  hasError.value = false
  loadedImageUrl.value = ""
}

function preloadImage(url) {
  resetState()

  if (!url) {
    return
  }

  isLoading.value = true

  const image = new window.Image()

  image.onload = () => {
    loadedImageUrl.value = url
    isLoaded.value = true
    isLoading.value = false
  }

  image.onerror = () => {
    hasError.value = true
    isLoading.value = false
  }

  image.src = url
}

watch(
  finalImageUrl,
  (url) => {
    preloadImage(url)
  },
  { immediate: true },
)
</script>
