<template>
  <div
    id="course-home"
    class="hide-content"
  >
    <div
      v-if="isCourseLoading"
      class="flex flex-col gap-4"
    >
      <div class="flex gap-4 items-center">
        <Skeleton
          class="mr-auto"
          height="2.5rem"
          width="12rem"
        />
        <Skeleton
          v-if="isCurrentTeacher"
          height="2.5rem"
          width="8rem"
        />
        <Skeleton
          v-if="isCurrentTeacher"
          height="2.5rem"
          width="3rem"
        />
      </div>

      <Skeleton height="16rem" />

      <div class="flex items-center gap-6">
        <Skeleton
          height="1.5rem"
          width="6rem"
        />
        <Skeleton
          v-if="isCurrentTeacher"
          class="ml-auto"
          height="1.5rem"
          width="6rem"
        />
        <Skeleton
          v-if="isCurrentTeacher"
          class="aspect-square"
          height="1.5rem"
          width="6rem"
        />
        <Skeleton
          v-if="isCurrentTeacher"
          class="aspect-square"
          height="1.5rem"
          width="6rem"
        />
        <Skeleton
          v-if="isCurrentTeacher"
          class="aspect-square"
          height="1.5rem"
          width="6rem"
        />
      </div>

      <hr class="mt-0 mb-4" />

      <div class="grid gap-y-12 sm:gap-x-5 md:gap-x-16 md:gap-y-12 justify-between grid-cols-course-tools">
        <Skeleton
          v-for="v in 30"
          :key="v"
          class="aspect-square"
          height="auto"
          width="7.5rem"
        />
      </div>
    </div>
    <div
      v-else
      class="flex flex-col gap-4"
    >
      <div class="flex gap-4 items-center">
        <h2 class="mr-auto">
          {{ course.title }}
          <small v-if="session"> ({{ session.name }}) </small>
        </h2>

        <StudentViewButton
          v-if="course"
          @change="onStudentViewChanged"
        />

        <BaseButton
          v-if="showUpdateIntroductionButton"
          :label="t('Edit introduction')"
          icon="edit"
          type="black"
          @click="updateIntro(intro)"
        />

        <BaseButton
          v-if="isAllowedToEdit"
          icon="cog"
          only-icon
          popup-identifier="course-tmenu"
          type="black"
          @click="toggleCourseTMenu"
        />

        <BaseMenu
          id="course-tmenu"
          ref="courseTMenu"
          :model="courseItems"
        />
      </div>

      <hr class="mt-1 mb-1" />

      <div
        v-if="isAllowedToEdit"
        class="mb-4"
      >
        <div
          v-if="intro"
          class="flex flex-col gap-4"
        >
          <div v-html="intro.introText" />

          <BaseButton
            v-if="createInSession && introTool"
            :label="t('Course introduction')"
            class="ml-auto"
            icon="plus"
            type="primary"
            @click="addIntro(course, intro)"
          />
        </div>
        <EmptyState
          v-else-if="introTool"
          :detail="t('Add a course introduction to display to your students.')"
          :summary="t('You don\'t have any course content yet.')"
          icon="courses"
        >
          <BaseButton
            :label="t('Course introduction')"
            class="mt-4"
            icon="plus"
            type="primary"
            @click="addIntro(course, intro)"
          />
        </EmptyState>
      </div>
      <div
        v-else-if="intro"
        class="mb-4"
        v-html="intro.introText"
      />

      <div
        v-if="isAllowedToEdit"
        class="flex items-center gap-6"
      >
        <h6 v-t="'Tools'" />

        <div class="ml-auto">
          <BaseToggleButton
            :disabled="isSorting || isCustomizing"
            :model-value="false"
            :off-label="t('Show all')"
            :on-label="t('Show all')"
            class="ml-auto"
            off-icon="eye-on"
            on-icon="eye-on"
            size="small"
            without-borders
            @click="onClickShowAll"
          />
          <BaseToggleButton
            :disabled="isSorting || isCustomizing"
            :model-value="false"
            :off-label="t('Hide all')"
            :on-label="t('Hide all')"
            off-icon="eye-off"
            on-icon="eye-off"
            size="small"
            without-borders
            @click="onClickHideAll"
          />
          <BaseToggleButton
            v-model="isSorting"
            :disabled="isCustomizing"
            :off-label="t('Sort')"
            :on-label="t('Sort')"
            off-icon="swap-vertical"
            on-icon="swap-vertical"
            size="small"
            without-borders
          />
          <BaseToggleButton
            v-model="isCustomizing"
            :disabled="isSorting"
            :off-label="t('Customize')"
            :on-label="t('Customize')"
            off-icon="customize"
            on-icon="customize"
            size="small"
            without-borders
          />
        </div>
      </div>
      <hr class="mt-0 mb-4" />

      <div
        id="course-tools"
        class="grid gap-y-12 sm:gap-x-5 md:gap-x-16 md:gap-y-12 grid-cols-course-tools"
      >
        <CourseTool
          v-for="(tool, index) in tools"
          :key="'tool-' + index.toString()"
          :change-visibility="changeVisibility"
          :course="course"
          :data-index="index"
          :data-tool="tool.ctool.name"
          :go-to-setting-course-tool="goToSettingCourseTool"
          :to="tool.to"
          :tool="tool"
          :url="tool.url"
        />

        <ShortCutList
          v-for="(shortcut, index) in shortcuts"
          :key="'shortcut-' + index.toString()"
          :change-visibility="changeVisibility"
          :go-to-short-cut="goToShortCut"
          :shortcut="shortcut"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeMount, onMounted, provide, ref, watch } from "vue"
import { useStore } from "vuex"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import axios from "axios"
import { ENTRYPOINT } from "../../config/entrypoint"
import CourseTool from "../../components/course/CourseTool"
import ShortCutList from "../../components/course/ShortCutList.vue"
import translateHtml from "../../../js/translatehtml.js"
import EmptyState from "../../components/EmptyState"
import Skeleton from "primevue/skeleton"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseMenu from "../../components/basecomponents/BaseMenu.vue"
import BaseToggleButton from "../../components/basecomponents/BaseToggleButton.vue"
import StudentViewButton from "../../components/StudentViewButton.vue"
import Sortable from "sortablejs"
import { checkIsAllowedToEdit } from "../../composables/userPermissions"
import { useCidReqStore } from "../../store/cidReq"

const route = useRoute()
const store = useStore()
const router = useRouter()
const { t } = useI18n()
const cidReqStore = useCidReqStore()

const course = ref(null)
const session = ref(null)
const tools = ref({})
const shortcuts = ref([])
const intro = ref(null)
const introTool = ref(null)
const createInSession = ref(false)

let courseId = route.params.id
let sessionId = route.query.sid ?? 0

const isCourseLoading = ref(true)

const showUpdateIntroductionButton = computed(() => {
  return course.value && isCurrentTeacher.value && intro.value && !(createInSession.value && introTool.value)
})
const isCurrentTeacher = computed(() => store.getters["security/isCurrentTeacher"])

const isSorting = ref(false)
const isCustomizing = ref(false)

provide("isSorting", isSorting)
provide("isCustomizing", isCustomizing)

// Remove the course session state.
cidReqStore.resetCidReq()

const courseItems = ref([])

const routerTools = ["document", "link", "glossary", "agenda", "student_publication", "course_homepage"]

axios
  .get(ENTRYPOINT + `../course/${courseId}/home.json?sid=${sessionId}`)
  .then(({ data }) => {
    course.value = data.course
    session.value = data.session

    cidReqStore.course = data.course
    cidReqStore.session = data.session

    tools.value = data.tools.map((element) => {
      if (routerTools.includes(element.ctool.name)) {
        element.to = element.url
      }

      return element
    })

    shortcuts.value = data.shortcuts

    let adminTool = tools.value.filter((element) => element.category === "admin")

    if (Array.isArray(adminTool)) {
      courseItems.value = adminTool.map((tool) => ({
        label: tool.tool.nameToShow,
        url: tool.url,
      }))
    }

    getIntro()

    isCourseLoading.value = false
  })
  .catch((error) => console.log(error))

const courseTMenu = ref(null)

const toggleCourseTMenu = (event) => {
  courseTMenu.value.toggle(event)
}

async function getIntro() {
  axios
    .get("/course/" + courseId + "/getToolIntro", {
      params: {
        cid: courseId,
        sid: sessionId,
      },
    })
    .then((response) => {
      intro.value = response.data
      introTool.value = response.data.c_tool
      createInSession.value = response.data.createInSession
    })
    .catch(function (error) {
      console.log(error)
    })
}

function addIntro(course, introTool) {
  return router.push({
    name: "ToolIntroCreate",
    params: { courseTool: introTool.iid },
    query: {
      cid: courseId,
      sid: sessionId,
      parentResourceNodeId: course.resourceNode.id,
      ctoolId: introTool.cToolId,
    },
  })
}

function updateIntro(intro) {
  return router.push({
    name: "ToolIntroUpdate",
    params: { id: "/api/c_tool_intros/" + intro.iid },
    query: {
      cid: courseId,
      sid: sessionId,
      ctoolintroIid: intro.iid,
      ctoolId: intro.c_tool.iid,
      id: "/api/c_tool_intros/" + intro.iid,
    },
  })
}

function goToSettingCourseTool(course, tool) {
  return "/course/" + courseId + "/settings/" + tool.tool.name + "?sid=" + sessionId
}

function goToShortCut(shortcut) {
  const url = new URLSearchParams("?")

  url.append("cid", courseId)
  url.append("sid", sessionId)

  return shortcut.url + "?" + url
}

const setToolVisibility = (tool, visibility) => {
  tool.ctool.resourceNode.resourceLinks[0].visibility = visibility
}

function changeVisibility(course, tool) {
  axios
    .post(ENTRYPOINT + "../r/course_tool/links/" + tool.ctool.resourceNode.id + "/change_visibility")
    .then((response) => setToolVisibility(tool, response.data.visibility))
    .catch((error) => console.log(error))
}

function onClickShowAll() {
  axios
    .post(ENTRYPOINT + `../r/course_tool/links/change_visibility/show?cid=${courseId}&sid=${sessionId}`)
    .then(() => {
      tools.value.forEach((tool) => setToolVisibility(tool, 2))
    })
    .catch((error) => console.log(error))
}

function onClickHideAll() {
  axios
    .post(ENTRYPOINT + `../r/course_tool/links/change_visibility/hide?cid=${courseId}&sid=${sessionId}`)
    .then(() => {
      tools.value.forEach((tool) => setToolVisibility(tool, 0))
    })
    .catch((error) => console.log(error))
}

// Sort behaviour
let sortable = null
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
      },
    })
  }

  sortable.option("disabled", !isSortingEnabled)
})

async function updateDisplayOrder(htmlItem, newIndex) {
  const tool = htmlItem.dataset.tool
  let toolItem = null

  if (typeof tools !== "undefined" && Array.isArray(tools.value)) {
    const toolList = tools.value
    toolItem = toolList.find((element) => element.tool.name === tool)
  } else {
    console.error("Error: tools.value is undefined")
    return
  }

  console.log(toolItem, newIndex)

  // Send the updated values to the server
  const url = ENTRYPOINT + `../course/${courseId}/home.json?sid=${sessionId}`
  const data = {
    index: newIndex,
    toolItem: toolItem,
    // Add any other necessary data that you need to send to the server
  }

  try {
    console.log(url, data)
    const response = await axios.post(url, data)
    console.log(response.data) // Server response
  } catch (error) {
    console.log(error)
  }
}

const isAllowedToEdit = ref(false)

onBeforeMount(async () => {
  try {
    const response = await axios.get(ENTRYPOINT + `../course/${courseId}/checkLegal.json`)

    if (response.data.redirect) {
      window.location.href = response.data.url
    } else {
      document.getElementById("course-home").classList.remove("hide-content")
    }
  } catch (error) {
    console.error("Error checking terms and conditions:", error)
    document.getElementById("course-home").classList.remove("hide-content")
  }
})

onMounted(async () => {
  isAllowedToEdit.value = await checkIsAllowedToEdit()
  setTimeout(() => {
    translateHtml()
  }, 1000)
})

const onStudentViewChanged = async () => {
  isAllowedToEdit.value = await checkIsAllowedToEdit()
}
</script>