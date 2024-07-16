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
    class="section-header"
    :class="`section-header--h${size}`"
  >
    <component
      :is="`h${size}`"
      class="section-header__title"
    >
      {{ title }}
    </component>

    <div class="section-header__actions">
      <StudentViewButton v-if="course" />

      <slot />
    </div>
  </div>
</template>
