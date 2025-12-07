<script setup>
import { useCidReqStore } from "../../store/cidReq"
import { storeToRefs } from "pinia"
import StudentViewButton from "../StudentViewButton.vue"

defineProps({
  title: {
    type: String,
    required: true,
  },
  size: {
    type: String,
    required: false,
    default: "2",
  },
})

const cidReqStore = useCidReqStore()

const { course } = storeToRefs(cidReqStore)
</script>

<template>
  <div
    :class="`section-header--h${size}`"
    class="section-header"
  >
    <component
      :is="`h${size}`"
      class="section-header__title"
    >
      {{ title }}
    </component>

    <div class="section-header__actions flex items-center gap-2">
      <slot />
      <StudentViewButton v-if="course" />
    </div>
  </div>
</template>
