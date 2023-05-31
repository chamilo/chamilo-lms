<template>
  <div v-if="isCourseLoading" class="flex flex-col gap-4">
    <div class="flex gap-4 items-center">
      <Skeleton class="mr-auto" height="2.5rem" width="12rem" />
      <Skeleton v-if="isCurrentTeacher" height="2.5rem" width="8rem" />
      <Skeleton v-if="isCurrentTeacher" height="2.5rem" width="3rem" />
    </div>

    <Skeleton height="16rem" />

    <div class="flex items-center gap-6">
      <Skeleton height="1.5rem" width="6rem" />
      <Skeleton v-if="isCurrentTeacher" class="ml-auto" height="1.5rem" width="6rem" />
      <Skeleton v-if="isCurrentTeacher" class="aspect-square" height="1.5rem" width="6rem" />
      <Skeleton v-if="isCurrentTeacher" class="aspect-square" height="1.5rem" width="6rem" />
      <Skeleton v-if="isCurrentTeacher" class="aspect-square" height="1.5rem" width="6rem" />
    </div>

    <hr class="mt-0 mb-4" />

    <div class="grid gap-y-12 sm:gap-x-5 md:gap-x-16 md:gap-y-12 justify-between grid-cols-course-tools">
      <Skeleton v-for="v in 30" :key="v" class="aspect-square" height="auto" width="7.5rem" />
    </div>
  </div>
  <div v-else class="flex flex-col gap-4">
    <div class="flex gap-4 items-center">
      <h2 class="mr-auto">
        {{ course.title }}
        <small v-if="session"> ({{ session.name }}) </small>
      </h2>

      <StudentViewButton v-if="course" />

      <BaseButton
        v-if="showUpdateIntroductionButton"
        :label="t('Edit introduction')"
        type="black"
        icon="edit"
        @click="updateIntro(intro)"
      />

      <BaseButton
        v-if="course && isCurrentTeacher"
        icon="cog"
        only-icon
        popup-identifier="course-tmenu"
        type="black"
        @click="toggleCourseTMenu"
      />

      <BaseMenu id="course-tmenu" ref="courseTMenu" :model="courseItems" />
    </div>

    <div v-if="isCurrentTeacher">
      <div v-if="intro" class="flex flex-col gap-4">
        <div v-html="intro.introText" />

        <BaseButton
          v-if="createInSession && introTool"
          :label="t('Course introduction')"
          class="ml-auto"
          icon="plus"
          type="primary"
          @click="addIntro(course, introTool)"
        />
      </div>
      <EmptyState
        v-else-if="introTool"
        :detail="t('Add a course introduction to display to your students.')"
        :summary="t('You don\'t have any course content yet.')"
        icon="mdi mdi-book-open-page-variant"
      >
        <BaseButton
          :label="t('Course introduction')"
          class="mt-4"
          icon="plus"
          type="primary"
          @click="addIntro(course, introTool)"
        />
      </EmptyState>
    </div>
    <div v-else-if="intro" v-html="intro.introText" />

    <div class="flex items-center gap-6">
      <h6 v-t="'Tools'" />
      <Button
        v-if="isCurrentTeacher"
        :disabled="isSorting || isCustomizing"
        :label="t('Show all')"
        class="p-button-text p-button-plain p-button-sm ml-auto"
        icon="mdi mdi-eye"
        @click="onClickShowAll"
      />
      <Button
        v-if="isCurrentTeacher"
        :disabled="isSorting || isCustomizing"
        :label="t('Hide all')"
        class="p-button-text p-button-plain p-button-sm"
        icon="mdi mdi-eye-off"
        @click="onClickHideAll"
      />
      <ToggleButton
        v-if="isCurrentTeacher"
        v-model="isSorting"
        :disabled="isCustomizing"
        :off-label="t('Sort')"
        :on-label="t('Sort')"
        class="p-button-text p-button-plain p-button-sm"
        off-icon="mdi mdi-swap-vertical"
        on-icon="mdi mdi-swap-vertical"
      />
      <ToggleButton
        v-if="isCurrentTeacher"
        v-model="isCustomizing"
        :disabled="isSorting"
        :off-label="t('Customize')"
        :on-label="t('Customize')"
        class="p-button-text p-button-plain p-button-sm"
        off-icon="mdi mdi-format-paint"
        on-icon="mdi mdi-format-paint"
      />
    </div>
    <hr class="mt-0 mb-4" />

    <div class="grid gap-y-12 sm:gap-x-5 md:gap-x-16 md:gap-y-12 grid-cols-course-tools">
      <CourseToolList
        v-for="(tool, index) in tools.authoring"
        :key="index"
        :change-visibility="changeVisibility"
        :course="course"
        :go-to-course-tool="goToCourseTool"
        :go-to-setting-course-tool="goToSettingCourseTool"
        :tool="tool"
      />

      <CourseToolList
        v-for="(tool, index) in tools.interaction"
        :key="index"
        :change-visibility="changeVisibility"
        :course="course"
        :go-to-course-tool="goToCourseTool"
        :go-to-setting-course-tool="goToSettingCourseTool"
        :tool="tool"
      />

      <CourseToolList
        v-for="(tool, index) in tools.plugin"
        :key="index"
        :change-visibility="changeVisibility"
        :course="course"
        :go-to-course-tool="goToCourseTool"
        :go-to-setting-course-tool="goToSettingCourseTool"
        :tool="tool"
      />

      <ShortCutList
        v-for="(shortcut, index) in shortcuts"
        :key="index"
        :change-visibility="changeVisibility"
        :go-to-short-cut="goToShortCut"
        :shortcut="shortcut"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, provide, ref } from "vue";
import { useStore } from "vuex";
import { useRoute, useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import axios from "axios";
import { ENTRYPOINT } from "../../config/entrypoint";
import Button from "primevue/button";
import ToggleButton from "primevue/togglebutton";
import CourseToolList from "../../components/course/CourseToolList.vue";
import ShortCutList from "../../components/course/ShortCutList.vue";
import translateHtml from "../../../js/translatehtml.js";
import EmptyState from "../../components/EmptyState";
import Skeleton from "primevue/skeleton";
import BaseButton from "../../components/basecomponents/BaseButton.vue";
import BaseMenu from "../../components/basecomponents/BaseMenu.vue";
import StudentViewButton from "../../components/StudentViewButton.vue";

const route = useRoute();
const store = useStore();
const router = useRouter();
const { t } = useI18n();

const course = ref(null);
const session = ref(null);
const tools = ref({});
const shortcuts = ref([]);
const intro = ref(null);
const introTool = ref(null);
const createInSession = ref(false);

let courseId = route.params.id;
let sessionId = route.query.sid ?? 0;

const isCourseLoading = ref(true);

const showUpdateIntroductionButton = computed(() => {
  return course.value && isCurrentTeacher.value && intro.value && !(createInSession.value && introTool.value);
});
const isCurrentTeacher = computed(() => store.getters["security/isCurrentTeacher"]);

const isSorting = ref(false);
const isCustomizing = ref(false);

provide("isSorting", isSorting);
provide("isCustomizing", isCustomizing);

// Remove the course session state.
store.dispatch("session/cleanSession");

const courseItems = ref([]);

axios
  .get(ENTRYPOINT + `../course/${courseId}/home.json?sid=${sessionId}`)
  .then(({ data }) => {
    course.value = data.course;
    session.value = data.session;
    tools.value = data.tools;
    shortcuts.value = data.shortcuts;

    if (tools.value.admin) {
      courseItems.value = tools.value.admin.map((tool) => ({
        label: tool.tool.nameToShow,
        url: goToCourseTool(course, tool),
      }));
    }

    getIntro();

    isCourseLoading.value = false;
  })
  .catch((error) => console.log(error));

const courseTMenu = ref(null);

const toggleCourseTMenu = (event) => {
  courseTMenu.value.toggle(event);
};

async function getIntro() {
  // Searching for the CTool called 'course_homepage'.
  let currentIntroTool = course.value.tools.find((element) => element.name === "course_homepage");

  if (!introTool.value) {
    introTool.value = currentIntroTool;

    if (sessionId) {
      createInSession.value = true;
    }

    // Search CToolIntro for this
    const filter = {
      courseTool: currentIntroTool.iid,
      cid: courseId,
      sid: sessionId,
    };

    try {
      const response = await store.dispatch("ctoolintro/findAll", filter);
      if (response) {
        if (sessionId) {
          createInSession.value = false;
        }
        // first item
        intro.value = response[0];
        translateHtml();
      }
    } catch (e) {
      console.error(e);
    }
  }
}

function addIntro(course, introTool) {
  return router.push({
    name: "ToolIntroCreate",
    params: { courseTool: introTool.iid },
    query: {
      cid: courseId,
      sid: sessionId,
      parentResourceNodeId: course.resourceNode.id,
    },
  });
}

function updateIntro(intro) {
  return router.push({
    name: "ToolIntroUpdate",
    params: { id: intro["@id"] },
    query: {
      cid: courseId,
      sid: sessionId,
      id: intro["@id"],
    },
  });
}

function goToSettingCourseTool(course, tool) {
  return "/course/" + courseId + "/settings/" + tool.tool.name + "?sid=" + sessionId;
}

function goToCourseTool(course, tool) {
  return "/course/" + courseId + "/tool/" + tool.tool.name + "?sid=" + sessionId;
}

function goToShortCut(shortcut) {
  const url = new URLSearchParams("?");

  url.append("cid", courseId);
  url.append("sid", sessionId);

  return shortcut.url + "?" + url;
}

const setToolVisibility = (tool, visibility) => {
  tool.ctool.resourceNode.resourceLinks[0].visibility = visibility;
};

function changeVisibility(course, tool) {
  axios
    .post(ENTRYPOINT + "../r/course_tool/links/" + tool.ctool.resourceNode.id + "/change_visibility")
    .then((response) => setToolVisibility(tool, response.data.visibility))
    .catch((error) => console.log(error));
}

function onClickShowAll() {
  axios
    .post(ENTRYPOINT + `../r/course_tool/links/change_visibility/show?cid=${courseId}&sid=${sessionId}`)
    .then(() => {
      tools.value.authoring.forEach((tool) => setToolVisibility(tool, 2));

      tools.value.interaction.forEach((tool) => setToolVisibility(tool, 2));

      tools.value.plugin.forEach((tool) => setToolVisibility(tool, 2));
    })
    .catch((error) => console.log(error));
}

function onClickHideAll() {
  axios
    .post(ENTRYPOINT + `../r/course_tool/links/change_visibility/hide?cid=${courseId}&sid=${sessionId}`)
    .then(() => {
      tools.value.authoring.forEach((tool) => setToolVisibility(tool, 0));

      tools.value.interaction.forEach((tool) => setToolVisibility(tool, 0));

      tools.value.plugin.forEach((tool) => setToolVisibility(tool, 0));
    })
    .catch((error) => console.log(error));
}
</script>
