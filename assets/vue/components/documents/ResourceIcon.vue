<template>
  <BaseIcon
    v-if="resourceData.filetype === 'folder'"
    icon="folder-generic"
  />
  <BaseIcon
    v-else-if="resourceData.resourceNode.resourceFile.image"
    icon="file-image"
  />
  <BaseIcon
    v-else-if="resourceData.resourceNode.resourceFile.video"
    icon="file-video"
  />
  <BaseIcon
    v-else-if="resourceData.resourceNode.resourceFile.text"
    icon="file-text"
  />
  <BaseIcon
    v-else-if="'application/pdf' === resourceData.resourceNode.resourceFile.mimeType"
    icon="file-pdf"
  />
  <BaseIcon
    v-else
    icon="file-generic"
  />
</template>

<script setup>
import BaseIcon from "../basecomponents/BaseIcon.vue";

defineProps({
  resourceData: {
    type: Object,
    required: true,
  },
});
</script>
