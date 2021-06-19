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
      v-else
      icon="mdi-file-alt"
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
