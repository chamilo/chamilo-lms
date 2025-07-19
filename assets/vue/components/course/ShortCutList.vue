<template>
  <div class="course-tool">
    <BaseAppLink
      :url="url"
      :target="props.shortcut.target || '_self'"
      rel="noopener"
      class="course-tool__link"
    >
      <img
        :alt="shortcut.title"
        :src="shortcut.customImageUrl || `/img/tools/${shortcut.type}.png`"
        class="course-tool__icon"
      />
    </BaseAppLink>
    <BaseAppLink
      :url="url"
      :target="props.shortcut.target || '_self'"
      rel="noopener"
      class="course-tool__title"
    >
      {{ shortcut.title }}
    </BaseAppLink>
  </div>
</template>

<script setup>
import { computed } from "vue"
import { storeToRefs } from "pinia"
import { useCidReqStore } from "../../store/cidReq"

const cidReqStore = useCidReqStore()
const { course, session } = storeToRefs(cidReqStore)

const props = defineProps({
  shortcut: {
    type: Object,
    required: true,
  },
})

const url = computed(() => `${props.shortcut.url}?cid=${course.value.id}&sid=${session.value?.id || 0}`)
</script>
