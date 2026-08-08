<script setup>
import { computed } from "vue"
import { useRoute } from "vue-router"
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
  showStudentViewButton: {
    type: Boolean,
    default: true,
  },
})

const route = useRoute()
const cidReqStore = useCidReqStore()

const { course } = storeToRefs(cidReqStore)

function isTruthyQueryValue(value) {
  return ["1", "true", "yes", "on"].includes(String(value || "").toLowerCase())
}

const isEmbeddedStudentView = computed(
  () =>
    "learnpath" === String(route.query.origin || "").toLowerCase() &&
    isTruthyQueryValue(route.query.embedded) &&
    isTruthyQueryValue(route.query.isStudentView),
)
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

    <div class="section-header__actions">
      <slot />
      <StudentViewButton v-if="course && showStudentViewButton && !isEmbeddedStudentView" />
    </div>
  </div>
</template>
