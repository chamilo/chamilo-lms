<template>
  <span>
    <FontAwesomeIcon
      v-if="file.image"
      icon="file-image"
      size="lg"
    />

    <FontAwesomeIcon
      v-else-if="file.video"
      icon="file-video"
      size="lg"
    />

    <FontAwesomeIcon
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
