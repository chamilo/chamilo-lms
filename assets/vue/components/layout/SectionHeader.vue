<script setup>
import { useCidReqStore } from "../../store/cidReq"
import { storeToRefs } from "pinia"
import StudentViewButton from "../StudentViewButton.vue"

defineProps({
  title: {
    type: String,
    required: true,
  },
})

const cidReqStore = useCidReqStore()

const { course } = storeToRefs(cidReqStore)
</script>

<template>
  <div class="section-header section-header--h2">
    <h2
      class="section-header__title"
      v-text="title"
    />

    <div class="section-header__actions">
      <StudentViewButton v-if="course" />

      <slot />
    </div>
  </div>
</template>
