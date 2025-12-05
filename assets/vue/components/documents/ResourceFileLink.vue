<template>
  <a
    :data-type="getDataType"
    :href="resource.contentUrl"
    data-fancybox="gallery"
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
      const node = this.resource && this.resource.resourceNode
      const file = node && node.firstResourceFile

      if (file && file.image) {
        return "image"
      }

      if (file && file.video) {
        return "video"
      }

      return "iframe"
    },
  },
}
</script>
