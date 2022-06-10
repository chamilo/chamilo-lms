<template>
  <div class="course-tool">
    <a
      :href="goToCourseTool(course, tool)"
      class="course-tool__link"
      :aria-labelledby="`course-tool-${tool.ctool.iid}`"
    >
      <span
        :class="tool.tool.icon"
        class="course-tool__icon mdi"
        aria-hidden="true"
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
        v-if="isCurrentTeacher && changeVisibility"
        @click="changeVisibility(course, tool)"
      >
        <v-icon
          v-if="tool.ctool.resourceNode.resourceLinks[0].visibility === 2"
          icon="mdi-eye"
          size="lg"
        />
        <v-icon
          v-else
          icon="mdi-eye-off"
          size="lg"
        />
      </button>

      <a
        v-if="isCurrentTeacher"
        :href="goToSettingCourseTool(course, tool)"
      >
        <v-icon
          icon="mdi-cog"
          size="lg"
        />
      </a>
    </div>
  </div>
</template>

<script setup>
import { useStore } from 'vuex'
import { computed } from 'vue'

const store = useStore();

// eslint-disable-next-line no-undef
defineProps({
    course: {
        type: Object,
        required: true,
    },
    tool: {
        type: Object,
        required: true,
    },
    goToCourseTool: {
        type: Function,
        required: true,
    },
    changeVisibility: {
        type: Function,
        required: true,
    },
    goToSettingCourseTool: {
        type: Function,
        required: true,
    },
});

const isCurrentTeacher = computed(() => store.getters['security/isCurrentTeacher']);
</script>
