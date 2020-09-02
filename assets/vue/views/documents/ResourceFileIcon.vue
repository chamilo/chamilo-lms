<template>
  <span>
    <font-awesome-icon
      v-if="file.image"
      icon="file-image"
    />

    <font-awesome-icon
      v-else-if="file.video"
      icon="file-video"
    />

    <font-awesome-icon
      v-else
      icon="file-alt"
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
