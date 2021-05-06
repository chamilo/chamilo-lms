<template>
  <div
      class="bg-gray-100 rounded-xl p-2 shadow-md"
  >
    <div class="flex flex-col flex-center">
      <div class="mx-auto">
        <a :href="goToCourseTool(course, tool)">
          <img
              :alt="tool.name"
              :src="'/img/tools/' + tool.name + '.png'"
              class="w-32 h-32 object-contain"
          />
        </a>
      </div>

      <div class="flex flex-row gap-2 text-gray-500">
        <a
            :href="goToCourseTool(course, tool)"
        >
          {{ tool.nameToTranslate }}
        </a>

        <button v-if="isCurrentTeacher && changeVisibility" @click="changeVisibility(course, tool)">
          <FontAwesomeIcon
              v-if="tool.resourceNode.resourceLinks[0].visibility === 2"
              icon="eye" size="lg"
          />
          <FontAwesomeIcon
              v-else
              icon="eye-slash"
              size="lg"
          />
        </button>
      </div>
    </div>
  </div>
</template>

<script>

import {mapGetters} from "vuex";

export default {
  name: 'HomeCourseCard',
  props: {
    course: Object,
    tool: Object,
    goToCourseTool: {
      type: Function,
      required: true
    },
    changeVisibility: {
      type: Function,
      required: false
    },
  },
  computed: {
    ...mapGetters({
      'isCurrentTeacher': 'security/isCurrentTeacher',
    }),
  },
};
</script>
