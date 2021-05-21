<template>
  <div v-if="course" class="grid gap-4">
    <q-card-section>
      <div class="text-h6">
        {{ course.title }}
      </div>
      <div class="text-subtitle2">
        {{ course.description }}
      </div>
    </q-card-section>

    <div
      class="grid gap-3 grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 2xl:grid-cols-6"
    >
        <HomeCourseCard
          v-for="tool in tools.authoring"
          :course="course"
          :tool="tool"
          :go-to-course-tool="goToCourseTool"
          :change-visibility="changeVisibility"
        />
        <HomeCourseCard
            v-for="tool in tools.interaction"
            :course="course"
            :tool="tool"
            :go-to-course-tool="goToCourseTool"
            :change-visibility="changeVisibility"
        />

        <HomeShortCutCard
            v-for="shortcut in shortcuts"
            :shortcut="shortcut"
            :go-to-short-cut="goToShortCut"
            :change-visibility="changeVisibility"
        />
    </div>

    <h2 v-if="isCurrentTeacher && tools && course">Settings</h2>

    <div
        v-if="isCurrentTeacher"
        class="grid gap-3 grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 2xl:grid-cols-6"
    >
      <HomeCourseCard
          v-for="tool in tools.admin"
          :course="course"
          :tool="tool"
          :go-to-course-tool="goToCourseTool"
      />
    </div>
  </div>
</template>

<script>
import Loading from '../../components/Loading.vue';
import Toolbar from '../../components/Toolbar.vue';
import HomeCourseCard from '../../components/course/HomeCourseCard.vue';
import HomeShortCutCard from '../../components/course/HomeShortCutCard.vue';

import { useRoute } from 'vue-router'
import axios from "axios";
import { ENTRYPOINT } from '../../config/entrypoint';
import { reactive, toRefs} from 'vue'
import {mapGetters} from "vuex";

export default {
  name: 'Home',
  servicePrefix: 'Courses',
  components: {
    Loading,
    Toolbar,
    HomeCourseCard,
    HomeShortCutCard
  },
  setup() {
    const state = reactive({course: [], tools: [], shortcuts:[], goToCourseTool, changeVisibility, goToShortCut });
    const route = useRoute()
    let courseId = route.params.id;
    let sessionId = route.query.sid ?? 0;

    axios.get(ENTRYPOINT + '../course/' + courseId + '/home.json').then(response => {
      state.course = response.data.course;
      state.tools = response.data.tools;
      state.shortcuts = response.data.shortcuts;
    }).catch(function (error) {
      console.log(error);
    });

    function goToCourseTool(course, tool) {
      let url = '/course/' + courseId + '/tool/' + tool.name + '?sid=' + sessionId;

      return url;
    }

    function goToShortCut(shortcut) {
      var url = new URLSearchParams('?');
      //let url = new URL(shortcut.url);
      url.append('cid', courseId);
      url.append('sid', sessionId);

      return shortcut.url + '?' + url;
    }

    function changeVisibility(course, tool) {
      axios.post(ENTRYPOINT + '../r/course_tool/links/' + tool.resourceNode.id + '/change_visibility').then(response => {
        if (response.data.ok) {
          tool.resourceNode.resourceLinks[0].visibility = response.data.visibility;
        }
      }).catch(function (error) {
        console.log(error);
      });
    }

    return toRefs(state);
  },
  computed: {
    ...mapGetters({
      'isCurrentTeacher': 'security/isCurrentTeacher',
    }),
  },
};
</script>
