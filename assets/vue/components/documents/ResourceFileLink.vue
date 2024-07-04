<template>
  <a
    data-fancybox="gallery"
    :href="resource.contentUrl"
    :data-type="getDataType"
  >
    <ResourceIcon :resource-data="resource" />
    {{ resource.title }}
  </a>
</template>

<script>
import ResourceIcon from "./ResourceIcon.vue"
export default {
  name: "ResourceFileLink",
  components: {
    ResourceIcon,
  },
  props: {
    resource: {
      type: Object,
      required: true,
    },
  },
  computed: {
    getDataType() {
      if (this.resource.resourceNode.firstResourceFile.image) {
        return "image"
      }
      if (this.resource.resourceNode.firstResourceFile.video) {
        return "video"
      }

      return "iframe"
    },
  },
}
</script>
