<template>
  <span>
    <v-icon
      v-if="file.image"
      icon="mdi-file-image"
      medium
    />

    <v-icon
      v-else-if="file.video"
      icon="mdi-file-video"
      medium
    />

    <v-icon
        v-else-if="'application/pdf' === file.mimeType"
        icon="mdi-file-pdf-box"
        medium
    />

    <v-icon
      v-else
      icon="mdi-file"
      medium
    />
  </span>
</template>

<script>
export default {
  name: 'ResourceFileIcon',
  props: {
    file: Object
  },
};
</script>
