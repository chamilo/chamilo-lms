<template>
  <BaseIcon
    v-if="fileType === 'image'"
    icon="file-image"
  />
  <BaseIcon
    v-else-if="fileType === 'video'"
    icon="file-video"
  />
  <BaseIcon
    v-else-if="fileType === 'folder'"
    icon="folder-generic"
  />
  <BaseIcon
    v-else-if="'application/pdf' === fileType.mimeType"
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
  fileType: {
    type: Object,
    required: true,
  },
});
</script>
