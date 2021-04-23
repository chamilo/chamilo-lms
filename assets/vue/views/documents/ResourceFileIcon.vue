<template>
  <span>
    <font-awesome-icon
      v-if="file.image"
      icon="file-image"
      size="lg"
    />

    <font-awesome-icon
      v-else-if="file.video"
      icon="file-video"
      size="lg"
    />

    <font-awesome-icon
      v-else
      icon="file-alt"
      size="lg"
    />
  </span>
</template>

<script>
const servicePrefix = 'Documents';
export default {
  name: 'ResourceFileIcon',
  servicePrefix,
  props: {
    file: Object
  },
};
</script>
