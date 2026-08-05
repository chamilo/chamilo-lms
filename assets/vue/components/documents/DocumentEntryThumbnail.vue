<template>
  <span
    class="mr-2 flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded border border-gray-10 bg-white"
  >
    <img
      v-if="showThumbnail"
      :alt="thumbnailAlt"
      :src="thumbnailUrl"
      class="h-full w-full object-cover"
      decoding="async"
      loading="lazy"
      @error="thumbnailLoadFailed = true"
    />
    <ResourceIcon
      v-else
      :resource-data="data"
    />
  </span>
</template>

<script setup>
import { computed, ref, watch } from "vue"
import ResourceIcon from "./ResourceIcon.vue"
import { useFileUtils } from "../../composables/fileUtils"

const props = defineProps({
  data: {
    type: Object,
    required: true,
  },
  title: {
    type: String,
    default: "",
  },
})

const { isImage } = useFileUtils()

const thumbnailLoadFailed = ref(false)

const thumbnailAlt = computed(() => {
  return props.title || String(props.data?.title || props.data?.resourceNode?.title || "")
})

const thumbnailUrl = computed(() => {
  const contentUrl = String(props.data?.contentUrl || "").trim()

  if (!contentUrl || !isImage(props.data)) {
    return ""
  }

  try {
    const url = new URL(contentUrl, window.location.origin)
    url.searchParams.set("filter", "editor_thumbnail")

    return url.toString()
  } catch (error) {
    const hashIndex = contentUrl.indexOf("#")
    const urlWithoutHash = hashIndex >= 0 ? contentUrl.slice(0, hashIndex) : contentUrl
    const hash = hashIndex >= 0 ? contentUrl.slice(hashIndex) : ""
    const separator = urlWithoutHash.includes("?") ? "&" : "?"

    return `${urlWithoutHash}${separator}filter=editor_thumbnail${hash}`
  }
})

const showThumbnail = computed(() => {
  return "" !== thumbnailUrl.value && !thumbnailLoadFailed.value
})

watch(
  () => props.data?.contentUrl,
  () => {
    thumbnailLoadFailed.value = false
  },
)
</script>
