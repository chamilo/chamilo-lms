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

    <hr class="mt-1 mb-1">

    <div v-if="isCurrentTeacher" class="mb-4">
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
    <div v-else-if="intro" v-html="intro.introText" class="mb-4" />

    <div v-if="isCurrentTeacher" class="flex items-center gap-6">
      <h6 v-t="'Tools'" />
      <BaseToggleButton
        :model-value="false"
        :on-label="t('Show all')"
        :off-label="t('Show all')"
        :disabled="isSorting || isCustomizing"
        on-icon="eye-on"
        off-icon="eye-on"
        size="small"
        class="ml-auto"
        without-borders
        @click="onClickShowAll"
      />
      <BaseToggleButton
        :model-value="false"
        :on-label="t('Hide all')"
        :off-label="t('Hide all')"
        :disabled="isSorting || isCustomizing"
        on-icon="eye-off"
        off-icon="eye-off"
        size="small"
        without-borders
        @click="onClickHideAll"
      />
      <BaseToggleButton
        v-model="isSorting"
        :disabled="isCustomizing"
        :on-label="t('Sort')"
        on-icon="swap-vertical"
        :off-label="t('Sort')"
        off-icon="swap-vertical"
        size="small"
        without-borders
      />
      <BaseToggleButton
        v-model="isCustomizing"
        :disabled="isSorting"
        :on-label="t('Customize')"
        on-icon="customize"
        :off-label="t('Customize')"
        off-icon="customize"
        size="small"
        without-borders
      />
    </div>
    <hr class="mt-0 mb-4" />

    <div id="course-tools" class="grid gap-y-12 sm:gap-x-5 md:gap-x-16 md:gap-y-12 grid-cols-course-tools">
      <CourseTool
        v-for="(tool, index) in tools.authoring"
        :key="'authoring-' + index.toString()"
        :change-visibility="changeVisibility"
        :course="course"
        :go-to-course-tool="goToCourseTool"
        :go-to-setting-course-tool="goToSettingCourseTool"
        :tool="tool"
        data-tool="authoring"
        :data-index="index"
      />

      <CourseTool
        v-for="(tool, index) in tools.interaction"
        :key="'interaction-' + index.toString()"
        :change-visibility="changeVisibility"
        :course="course"
        :go-to-course-tool="goToCourseTool"
        :go-to-setting-course-tool="goToSettingCourseTool"
        :tool="tool"
        data-tool="interaction"
        :data-index="index"
      />

      <CourseTool
        v-for="(tool, index) in tools.plugin"
        :key="'plugin-' + index.toString()"
        :change-visibility="changeVisibility"
        :course="course"
        :go-to-course-tool="goToCourseTool"
        :go-to-setting-course-tool="goToSettingCourseTool"
        :tool="tool"
        data-tool="plugin"
        :data-index="index"
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
import {computed, provide, ref, watch} from "vue";
import { useStore } from "vuex";
import { useRoute, useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import axios from "axios";
import { ENTRYPOINT } from "../../config/entrypoint";
import CourseTool from "../../components/course/CourseTool";
import ShortCutList from "../../components/course/ShortCutList.vue";
import translateHtml from "../../../js/translatehtml.js";
import EmptyState from "../../components/EmptyState";
import Skeleton from "primevue/skeleton";
import BaseButton from "../../components/basecomponents/BaseButton.vue";
import BaseMenu from "../../components/basecomponents/BaseMenu.vue";
import BaseToggleButton from "../../components/basecomponents/BaseToggleButton.vue";
import StudentViewButton from "../../components/StudentViewButton.vue";
import Sortable from 'sortablejs';

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

// Sort behaviour
let sortable = null;
watch(isSorting, (isSortingEnabled) => {
  if (isCourseLoading.value) {
    return
  }
  if (sortable === null) {
    let el = document.getElementById("course-tools")
    sortable = Sortable.create(el, {
      ghostClass: "invisible",
      chosenClass: "cursor-move",
      onSort: (event) => {
        updateDisplayOrder(event.item, event.newIndex)
      }
    })
  }

  sortable.option("disabled", !isSortingEnabled)
})
function updateDisplayOrder(htmlItem, newIndex) {
  let tool = htmlItem.dataset.tool
  let index = htmlItem.dataset.index
  let toolItem = null

  switch (tool) {
    case 'authoring':
      toolItem = tools.value.authoring[index]
      break;
    case 'interaction':
      toolItem = tools.value.interaction[index]
      break;
    case 'plugin':
      toolItem = tools.value.plugin[index]
      break;
    default:
      return
  }

  console.log(toolItem, newIndex)
  // TODO send values to server
  // toolItem is the tool value, retrieve id or needed data
  // newIndex is the new Index of the tool (do we need to send the index of the other tools?)
}
</script>
