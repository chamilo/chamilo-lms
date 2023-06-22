<template>
  <div class="course-tool">
    <a
      :aria-labelledby="`course-tool-${tool.ctool.iid}`"
      :href="goToCourseTool(course, tool)"
      class="course-tool__link"
      :class="cardCustomClass"
    >
      <span
        :class="tool.tool.icon + ' ' + iconCustomClass"
        aria-hidden="true"
        class="course-tool__icon mdi"
      />
    </a>

    <a
      :id="`course-tool-${tool.ctool.iid}`"
      v-t="tool.tool.nameToShow"
      :href="goToCourseTool(course, tool)"
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
        <BaseIcon icon="edit" size="small" />
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
import { useStore } from "vuex";
import { computed, inject } from "vue";
import BaseIcon from "../basecomponents/BaseIcon.vue";

const store = useStore();

const isSorting = inject("isSorting");
const isCustomizing = inject("isCustomizing");

// eslint-disable-next-line no-undef
const props = defineProps({
  course: {
    type: Object,
    required: true
  },
  tool: {
    type: Object,
    required: true
  },
  goToCourseTool: {
    type: Function,
    required: true
  },
  changeVisibility: {
    type: Function,
    required: true
  },
  goToSettingCourseTool: {
    type: Function,
    required: true
  }
});

const isCurrentTeacher = computed(() => store.getters["security/isCurrentTeacher"]);
const cardCustomClass = computed(() => {
  if (isVisible.value) {
    return '';
  }
  return 'bg-primary-bgdisabled border-primary-borderdisabled shadow-none ';
})
const iconCustomClass = computed(() => {
  if (isVisible.value) {
    return 'bg-primary-bgdisabled ';
  }
  return 'bg-gradient-to-b from-gray-50 to-gray-25 ';
})
const titleCustomClass = computed(() => {
  if (isVisible.value) {
    return '';
  }
  return 'text-gray-90 ';
})
const isVisible = computed(() => props.tool.ctool.resourceNode.resourceLinks[0].visibility === 2);
</script>
