<template>
  <BaseIcon
    v-if="resourceData.filetype === 'folder'"
    icon="folder-generic"
  />
  <BaseIcon
    v-else-if="resourceData.resourceNode.firstResourceFile.image"
    icon="file-image"
  />
  <BaseIcon
    v-else-if="resourceData.resourceNode.firstResourceFile.video"
    icon="file-video"
  />
  <BaseIcon
    v-else-if="resourceData.resourceNode.firstResourceFile.text"
    icon="file-text"
  />
  <BaseIcon
    v-else-if="'application/pdf' === resourceData.resourceNode.firstResourceFile.mimeType"
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
import BaseIcon from "../basecomponents/BaseIcon.vue"
import { useFileUtils } from "../../composables/fileUtils"

const { isAudio } = useFileUtils()

defineProps({
  resourceData: {
    type: Object,
    required: true,
  },
})
</script>
