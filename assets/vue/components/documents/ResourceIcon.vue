<template>
  <BaseIcon
    v-if="resourceData.filetype === 'folder'"
    icon="folder-generic"
  />
  <BaseIcon
    v-else-if="isImage(resourceData)"
    icon="file-image"
  />
  <BaseIcon
    v-else-if="isVideo(resourceData)"
    icon="file-video"
  />
  <BaseIcon
    v-else-if="hasTextFlag"
    icon="file-text"
  />
  <BaseIcon
    v-else-if="isPdfFile"
    icon="file-pdf"
  />
  <BaseIcon
    v-else-if="isAudio(resourceData)"
    icon="file-audio"
  />
  <BaseIcon
    v-else
    icon="file-generic"
  />
</template>

<script setup>
import { computed } from "vue"
import BaseIcon from "../basecomponents/BaseIcon.vue"
import { useFileUtils } from "../../composables/fileUtils"

const { isImage, isVideo, isAudio } = useFileUtils()

const props = defineProps({
  resourceData: {
    type: Object,
    required: true,
  },
})

const hasTextFlag = computed(() => {
  const file = props.resourceData?.resourceNode?.firstResourceFile
  return !!file && !!file.text
})

const isPdfFile = computed(() => {
  const file = props.resourceData?.resourceNode?.firstResourceFile

  if (!file || !file.mimeType) {
    return false
  }

  const mime = String(file.mimeType).split(";")[0].trim().toLowerCase()

  return mime === "application/pdf"
})
</script>
