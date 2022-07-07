<template>
  <div
    v-if="course"
    class="flex flex-col gap-4"
  >
    <div class="flex gap-4 items-center">
      <h2 class="mr-auto">
        {{ course.title }}
        <small v-if="session">
          ({{ session.name }})
        </small>
      </h2>

      <Button
        v-if="isCurrentTeacher"
        :label="t('See as student')"
        icon="pi pi-eye"
        class="p-button-outlined p-button-plain"
        type="button"
      />

      <Button
        icon="mdi mdi-cog"
        class="p-button-text p-button-plain"
        type="button"
        aria-haspopup="true"
        aria-controls="course-tmenu"
        @click="toggleCourseTMenu"
      />

      <TieredMenu
        id="course-tmenu"
        ref="courseTMenu"
        :model="courseItems"
        :popup="true"
      />
    </div>

    <div
      v-if="isCurrentTeacher"
    >
      <div
        v-if="intro"
        class="flex flex-col gap-4"
      >
        <div v-html="intro.introText" />

        <Button
          v-if="createInSession && introTool"
          class="p-button-outlined ml-auto"
          icon="mdi mdi-plus"
          :label="t('Course introduction')"
          @click="addIntro(course, introTool)"
        />
        <Button
          v-else
          class="p-button-outlined ml-auto"
          icon="mdi mdi-pencil"
          :label="t('Update')"
          @click="updateIntro(intro)"
        />
      </div>
      <EmptyState
        v-else
        :summary="t('You don\'t have any course content yet.')"
        :detail="t('Add a course introduction to display to your students.')"
        icon="mdi mdi-book-open-page-variant"
      >
        <Button
          v-if="introTool"
          class="mt-4 p-button-outlined"
          icon="mdi mdi-plus"
          :label="t('Course introduction')"
          @click="addIntro(course, introTool)"
        />
      </EmptyState>
    </div>
    <div
      v-else-if="intro"
      v-html="intro.introText"
    />

    <div
      v-if="isCurrentTeacher && course"
      class="flex justify-between border-b-2 border-gray-200"
    >
      <div class="text-h6 font-bold">
        {{ $t('Tools') }}
      </div>
    </div>

    <div
      class="grid gap-y-12 sm:gap-x-5 md:gap-x-16 md:gap-y-12 justify-between grid-cols-course-tools"
    >
      <CourseToolList
        v-for="tool in tools.authoring"
        :change-visibility="changeVisibility"
        :course="course"
        :go-to-course-tool="goToCourseTool"
        :go-to-setting-course-tool="goToSettingCourseTool"
        :tool="tool"
      />

      <CourseToolList
        v-for="tool in tools.interaction"
        :change-visibility="changeVisibility"
        :course="course"
        :go-to-course-tool="goToCourseTool"
        :go-to-setting-course-tool="goToSettingCourseTool"
        :tool="tool"
      />

      <CourseToolList
        v-for="tool in tools.plugin"
        :change-visibility="changeVisibility"
        :course="course"
        :go-to-course-tool="goToCourseTool"
        :go-to-setting-course-tool="goToSettingCourseTool"
        :tool="tool"
      />

      <ShortCutList
        v-for="shortcut in shortcuts"
        :change-visibility="changeVisibility"
        :go-to-short-cut="goToShortCut"
        :shortcut="shortcut"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useStore } from 'vuex';
import { useRoute, useRouter } from 'vue-router';
import isEmpty from 'lodash/isEmpty';
import axios from 'axios';
import { ENTRYPOINT } from '../../config/entrypoint';
import Button from 'primevue/button';
import TieredMenu from 'primevue/tieredmenu';
import CourseToolList from '../../components/course/CourseToolList.vue';
import ShortCutList from '../../components/course/ShortCutList.vue';
import translateHtml from '../../../js/translatehtml.js';
import { useI18n } from 'vue-i18n';
import EmptyState from '../../components/EmptyState';

const servicePrefix = 'Courses';

const course = ref([]);
const session = ref([]);
const tools = ref([]);
const shortcuts = ref([]);
const intro = ref(null);
const introTool = ref(null);
const createInSession = ref(false);

const route = useRoute();
const store = useStore();
const router = useRouter();
const { t } = useI18n();

let courseId = route.params.id;
let sessionId = route.query.sid ?? 0;

const isCurrentTeacher = computed(() => store.getters['security/isCurrentTeacher']);

// Remove the course session state.
store.dispatch('session/cleanSession');

axios.get(ENTRYPOINT + '../course/' + courseId + '/home.json?sid=' + sessionId).then(response => {
  course.value = response.data.course;
  session.value = response.data.session;
  tools.value = response.data.tools;
  shortcuts.value = response.data.shortcuts;
  getIntro();
}).catch(function (error) {
  console.log(error);
});

const courseTMenu = ref(null);

const courseItems = computed(() => {
  if (tools.value.admin) {
    return tools.value.admin.map(tool => ({
      label: tool.tool.nameToShow,
      url: goToCourseTool(course, tool)
    }));
  }

  return [];
});

const toggleCourseTMenu = event => {
  courseTMenu.value.toggle(event);
}

async function getIntro () {
  // Searching for the CTool called 'course_homepage'.
  let currentIntroTool = course.value.tools.find(element => element.name === 'course_homepage');

  if (!isEmpty(introTool)) {
    introTool.value = currentIntroTool;

    // Search CToolIntro for this
    const filter = {
      courseTool: currentIntroTool.iid,
      cid: courseId,
    };

    store.dispatch('ctoolintro/findAll', filter).then(response => {
      if (!isEmpty(response)) {
        // first item
        intro.value = response[0];
        translateHtml();
      }
    });

    if (!isEmpty(sessionId)) {
      createInSession.value = true;
      const filter = {
        courseTool: currentIntroTool.iid,
        cid: courseId,
        sid: sessionId,
      };

      store.dispatch('ctoolintro/findAll', filter).then(response => {
        if (!isEmpty(response)) {
          createInSession.value = false;
          intro.value = response[0];
          translateHtml();
        }
      });
    }
  }
}

function addIntro (course, introTool) {
  return router.push({
    name: 'ToolIntroCreate',
    params: { 'courseTool': introTool.iid },
    query: {
      'cid': courseId,
      'sid': sessionId,
      'parentResourceNodeId': course.resourceNode.id
    }
  });
}

function updateIntro (intro) {
  return router.push({
    name: 'ToolIntroUpdate',
    params: { 'id': intro['@id'] },
    query: {
      'cid': courseId,
      'sid': sessionId,
      'id': intro['@id']
    }
  });
}

function goToSettingCourseTool (course, tool) {
  return '/course/' + courseId + '/settings/' + tool.tool.name + '?sid=' + sessionId;
}

function goToCourseTool (course, tool) {
  return '/course/' + courseId + '/tool/' + tool.tool.name + '?sid=' + sessionId;
}

function goToShortCut (shortcut) {
  const url = new URLSearchParams('?');

  url.append('cid', courseId);
  url.append('sid', sessionId);

  return shortcut.url + '?' + url;
}

function changeVisibility (course, tool) {
  axios.post(ENTRYPOINT + '../r/course_tool/links/' + tool.ctool.resourceNode.id + '/change_visibility').then(response => {
    if (response.data.ok) {
      tool.ctool.resourceNode.resourceLinks[0].visibility = response.data.visibility;
    }
  }).catch(function (error) {
    console.log(error);
  });
}
</script>
