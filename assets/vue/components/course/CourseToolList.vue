<template>
  <div class="course-tool">
    <a
      :aria-labelledby="`course-tool-${tool.ctool.iid}`"
      :href="goToCourseTool(course, tool)"
      class="course-tool__link"
    >
      <span
        :class="tool.tool.icon"
        aria-hidden="true"
        class="course-tool__icon mdi"
      />
    </a>

    <a
      :id="`course-tool-${tool.ctool.iid}`"
      v-t="tool.tool.nameToShow"
      :href="goToCourseTool(course, tool)"
      class="course-tool__title"
    />

    <div class="course-tool__options">
      <button
        v-if="isCurrentTeacher && !isSorting && !isCustomizing"
        @click="changeVisibility(course, tool)"
      >
        <BaseIcon
          v-if="tool.ctool.resourceNode.resourceLinks[0].visibility === 2"
          icon="eye-on"
          size="small"
        />
        <BaseIcon
          v-else
          icon="eye-off"
          size="small"
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
defineProps({
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
</script>
