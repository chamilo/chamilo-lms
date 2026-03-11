<template>
  <div
    v-if="blocks.length"
    :data-region="region"
    class="plugin-region"
  >
    <div
      v-for="block in blocks"
      :key="block.pluginName"
      :class="`plugin-block plugin-block--${block.pluginName}`"
      v-html="block.html"
    />
  </div>
</template>

<script setup>
import { toRef } from "vue"
import { usePluginRegion } from "../../composables/pluginRegion"

const props = defineProps({
  region: {
    required: true,
    type: String,
  },
  context: {
    default: () => ({}),
    require: false,
    type: Object,
  },
})

const { blocks } = usePluginRegion(props.region, toRef(props, "context"))
</script>
