<template>
  <a
      data-fancybox="gallery"
      :href="resource.contentUrl"
      :data-type="getDataType"
  >
    <ResourceFileIcon :file="resource.resourceNode.resourceFile" />
    {{ resource.title }}
  </a>
</template>

<script>
import ResourceFileIcon from './ResourceFileIcon.vue';
export default {
  name: 'ResourceFileLink',
  components: {
    ResourceFileIcon
  },
  computed: {
    getDataType() {
      if (this.resource.resourceNode.resourceFile.image) {
        return 'image';
      }
      if (this.resource.resourceNode.resourceFile.video) {
        return 'video';
      }

      return 'iframe';
    }
  },
  props: {
    resource: Object
  },
};
</script>
