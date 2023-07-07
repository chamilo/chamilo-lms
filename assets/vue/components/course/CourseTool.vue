<template>
  <div class="course-tool">
    <router-link
      v-if="to"
      :aria-labelledby="`course-tool-${tool.ctool.iid}`"
      :to="to"
      class="course-tool__link hover:primary-gradient"
      :class="cardCustomClass"
    >
      <span
        :class="tool.tool.icon + ' ' + iconCustomClass"
        aria-hidden="true"
        class="course-tool__icon mdi"
      />
    </router-link>
    <a
      v-else
      :aria-labelledby="`course-tool-${tool.ctool.iid}`"
      :href="url"
      class="course-tool__link"
      :class="cardCustomClass"
    >
      <span
        :class="tool.tool.icon + ' ' + iconCustomClass"
        aria-hidden="true"
        class="course-tool__icon mdi"
      />
    </a>

    <router-link
      v-if="to"
      :id="`course-tool-${tool.ctool.iid}`"
      :class="titleCustomClass"
      :to="to"
      class="course-tool__title"
    >
      {{ tool.tool.nameToShow }}
    </router-link>
    <a
      v-else
      :id="`course-tool-${tool.ctool.iid}`"
      v-t="tool.tool.nameToShow"
      :href="url"
      class="course-tool__title"
      :class="titleCustomClass"
    />

    <div class="course-tool__options">
      <button
        v-if="isCurrentTeacher && !isSorting && !isCustomizing"
        @click="changeVisibility(course, tool)"
      >
        <BaseIcon
          v-if="isVisible"
          icon="eye-on"
        />
        <BaseIcon
          v-else
          icon="eye-off"
          class="text-gray-50"
        />
      </button>

      <a
        v-if="isCurrentTeacher && isCustomizing"
        href="#"
      >
        <BaseIcon
          icon="edit"
          size="small"
        />
      </a>

      <!-- a
        v-if="isCurrentTeacher"
        :href="goToSettingCourseTool(course, tool)"
      >
        <BaseIcon
          icon="cog"
          size="lg"
        />
      </a -->
    </div>
  </div>
</template>

<script setup>
import { useStore } from "vuex"
import { computed, inject } from "vue"
import BaseIcon from "../basecomponents/BaseIcon.vue"

const store = useStore()

const isSorting = inject("isSorting")
const isCustomizing = inject("isCustomizing")

// eslint-disable-next-line no-undef
const props = defineProps({
  course: {
    type: Object,
    required: true,
  },
  tool: {
    type: Object,
    required: true,
  },
  url: {
    type: String,
    required: false,
    default: () => null,
  },
  to: {
    type: String,
    required: false,
    default: () => null,
  },
  changeVisibility: {
    type: Function,
    required: true,
  },
  goToSettingCourseTool: {
    type: Function,
    required: true,
  },
})

const isCurrentTeacher = computed(() => store.getters["security/isCurrentTeacher"])
const cardCustomClass = computed(() => {
  if (!isVisible.value) {
    return "bg-primary-bgdisabled hover:bg-gray-50/25 border-primary-borderdisabled shadow-none "
  }
  if (isSorting.value) {
    return "border-2 border-dashed border-primary hover:bg-primary-gradient/10 "
  }
  return "hover:bg-primary-gradient/10 "
})
const iconCustomClass = computed(() => {
  if (!isVisible.value) {
    return "bg-gradient-to-b from-gray-50 to-gray-25 "
  }
  return "bg-primary-bgdisabled "
})
const titleCustomClass = computed(() => {
  if (!isVisible.value) {
    return "text-gray-90 "
  }
  return ""
})
const isVisible = computed(() => props.tool.ctool.resourceNode.resourceLinks[0].visibility === 2)
</script>
