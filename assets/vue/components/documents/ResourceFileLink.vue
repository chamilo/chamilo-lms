<template>
  <a
      data-fancybox="gallery"
      :href="resource.contentUrl"
      :data-type="getDataType"
  >
    <ResourceIcon :file="resource.resourceNode.resourceFile" />
    {{ resource.title }}
  </a>
</template>

<script>
import ResourceIcon from './ResourceIcon.vue';
export default {
  name: 'ResourceFileLink',
  components: {
    ResourceIcon
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
