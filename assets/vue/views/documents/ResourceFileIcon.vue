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
export default {
  name: 'ResourceFileIcon',
  props: {
    file: Object
  },
};
</script>
