<template>
  <div
    v-if="course"
    id="course-home"
    class="course-home"
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
          v-if="securityStore.isCurrentTeacher"
          height="2.5rem"
          width="8rem"
        />
        <Skeleton
          v-if="securityStore.isCurrentTeacher"
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
          v-if="securityStore.isCurrentTeacher"
          class="ml-auto"
          height="1.5rem"
          width="6rem"
        />
        <Skeleton
          v-if="securityStore.isCurrentTeacher"
          class="aspect-square"
          height="1.5rem"
          width="6rem"
        />
        <Skeleton
          v-if="securityStore.isCurrentTeacher"
          class="aspect-square"
          height="1.5rem"
          width="6rem"
        />
        <!-- Skeleton
          v-if="securityStore.isCurrentTeacher"
          class="aspect-square"
          height="1.5rem"
          width="6rem"
        / -->
      </div>

      <hr class="mt-0 mb-4" />

      <div class="course-home__tools">
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
      <div class="section-header section-header--h2">
        <h2 class="">
          {{ course.title }}
          <small v-if="session"> ({{ session.title }}) </small>
        </h2>

        <p
          v-if="isAllowedToEdit && documentAutoLaunch === 1"
          class="text-sm text-gray-600"
        >
          {{
            t(
              "The document auto-launch feature configuration is enabled. Learners will be automatically redirected to document tool.",
            )
          }}
        </p>

        <p
          v-if="isAllowedToEdit && (exerciseAutoLaunch === 1 || exerciseAutoLaunch === 2)"
          class="text-sm text-gray-600"
        >
          {{
            t(
              "The exercises auto-launch feature configuration is enabled. Learners will be automatically redirected to the selected exercise.",
            )
          }}
        </p>

        <p
          v-if="isAllowedToEdit && (lpAutoLaunch === 1 || lpAutoLaunch === 2)"
          class="text-sm text-gray-600"
        >
          {{
            t(
              "The learning path auto-launch setting is ON. When learners enter this course, they will be automatically redirected to the learning path marked as auto-launch.",
            )
          }}
        </p>

        <p
          v-if="isAllowedToEdit && (forumAutoLaunch === 1 || forumAutoLaunch === 2)"
          class="text-sm text-gray-600"
        >
          {{
            t(
              "The forum's auto-launch setting is on. Students will be redirected to the forum tool when entering this course.",
            )
          }}
        </p>

        <div class="grow-0">
          <StudentViewButton
            v-if="course"
            @change="onStudentViewChanged"
          />
        </div>

        <BaseButton
          v-if="isAllowedToEdit && courseIntroEl?.introduction?.iid"
          :label="t('Edit introduction')"
          class="grow-0"
          icon="edit"
          type="black"
          @click="courseIntroEl.goToCreateOrUpdate()"
        />

        <div class="grow-0">
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
      </div>

      <CourseThematicProgress />

      <div class="flex flex-col lg:flex-row gap-6">
        <div :class="showCourseSequence ? 'w-full lg:w-[80%]' : 'w-full'">
          <CourseIntroduction
            ref="courseIntroEl"
            :is-allowed-to-edit="isAllowedToEdit"
          />
        </div>
        <div
          v-if="showCourseSequence"
          class="w-full lg:w-[20%] lg:border-l lg:pl-4"
        >
          <NextCourseSequence />
        </div>
      </div>

      <div
        v-if="isAllowedToEdit"
        class="section-header section-header--h6"
      >
        <h6 v-text="t('Tools')" />

        <div class="ml-auto">
          <BaseToggleButton
            :disabled="isSorting || isCustomizing || !allowEditToolVisibilityInSession"
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
            :disabled="isSorting || isCustomizing || !allowEditToolVisibilityInSession"
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
          <!-- BaseToggleButton
            v-model="isCustomizing"
            :disabled="isSorting"
            :off-label="t('Customize')"
            :on-label="t('Customize')"
            off-icon="customize"
            on-icon="customize"
            size="small"
            without-borders
          / -->
        </div>
      </div>

      <div
        id="course-tools"
        class="course-home__tools"
      >
        <CourseTool
          v-for="(tool, index) in toolsForDisplay"
          :key="'tool-' + index.toString()"
          :change-visibility="changeVisibility"
          :data-index="index"
          :data-tool="tool.title"
          :go-to-setting-course-tool="goToSettingCourseTool"
          :is-allowed-to-edit="isAllowedToEdit"
          :tool="tool"
        />

        <ShortCutList
          v-for="(shortcut, index) in shortcuts"
          :key="'shortcut-' + index.toString()"
          :change-visibility="changeVisibility"
          :shortcut="shortcut"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, provide, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import axios from "axios"
import { ENTRYPOINT } from "../../config/entrypoint"
import CourseTool from "../../components/course/CourseTool"
import ShortCutList from "../../components/course/ShortCutList.vue"
import translateHtml from "../../../js/translatehtml.js"
import Skeleton from "primevue/skeleton"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseMenu from "../../components/basecomponents/BaseMenu.vue"
import BaseToggleButton from "../../components/basecomponents/BaseToggleButton.vue"
import StudentViewButton from "../../components/StudentViewButton.vue"
import Sortable from "sortablejs"
import { checkIsAllowedToEdit } from "../../composables/userPermissions"
import { useCidReqStore } from "../../store/cidReq"
import { storeToRefs } from "pinia"
import courseService from "../../services/courseService"
import CourseIntroduction from "../../components/course/CourseIntroduction.vue"
import { usePlatformConfig } from "../../store/platformConfig"
import { useSecurityStore } from "../../store/securityStore"
import { useCourseSettings } from "../../store/courseSettingStore"
import NextCourseSequence from "../../components/course/NextCourseSequence.vue"
import CourseThematicProgress from "../../components/course/CourseThematicProgress.vue"

const { t } = useI18n()
const cidReqStore = useCidReqStore()
const platformConfigStore = usePlatformConfig()
const securityStore = useSecurityStore()
const { course, session } = storeToRefs(cidReqStore)
const { getSetting } = storeToRefs(platformConfigStore)

const tools = ref([])
const shortcuts = ref([])

const courseIntroEl = ref(null)

const isCourseLoading = ref(true)

const isSorting = ref(false)
const isCustomizing = ref(false)

provide("isSorting", isSorting)
provide("isCustomizing", isCustomizing)

const courseItems = ref([])

const routerTools = ["document", "link", "glossary", "agenda", "student_publication", "course_homepage"]
const documentAutoLaunch = ref(0)
const exerciseAutoLaunch = ref(0)
const lpAutoLaunch = ref(0)
const forumAutoLaunch = ref(0)
const courseSettingsStore = useCourseSettings()

const TOOL_VISIBILITY_VISIBLE = 2

function getToolVisibility(tool) {
  return tool?.resourceNode?.resourceLinks?.[0]?.visibility
}

const toolsForDisplay = computed(() => {
  // Teachers/admins can see all tools (even hidden) to manage them.
  if (isAllowedToEdit.value) {
    return tools.value
  }

  // Learners must not see hidden tools.
  return tools.value.filter((tool) => getToolVisibility(tool) === TOOL_VISIBILITY_VISIBLE)
})

/**
 * Load tools for the course, split admin tools into the cog menu
 * and keep the rest in the main tools grid.
 * This function is reused on initial load and when toggling student view.
 */
async function loadCourseTools(showSkeleton = true) {
  if (showSkeleton) {
    isCourseLoading.value = true
  }

  try {
    const cTools = await courseService.loadCTools(course.value.id, session.value?.id)

    const normalizedTools = cTools.map((rawTool) => {
      const tool = { ...rawTool }

      if (routerTools.includes(tool.title)) {
        tool.to = tool.url
      }

      // Convenience flag for UI states (e.g. customize mode)
      tool.isEnabled = tool.resourceNode?.resourceLinks?.[0]?.visibility === 2

      return tool
    })

    const adminMenuItems = []
    const regularTools = []

    normalizedTools.forEach((tool) => {
      if (tool.tool?.category === "admin") {
        adminMenuItems.push({
          label: t(tool.tool.titleToShow),
          url: tool.url,
        })
      } else {
        regularTools.push(tool)
      }
    })

    tools.value = regularTools
    courseItems.value = adminMenuItems
  } catch (error) {
    console.error("[CourseHome] Failed to load course tools", error)
    tools.value = []
    courseItems.value = []
  } finally {
    if (showSkeleton) {
      isCourseLoading.value = false
    }
  }
}

loadCourseTools(true)

courseService
  .loadTools(course.value.id, session.value?.id)
  .then((data) => {
    shortcuts.value = data.shortcuts.map((shortcut) => {
      return {
        ...shortcut,
        customImageUrl: shortcut.customImageUrl || null,
      }
    })
  })
  .catch((error) => console.error(error))

const courseTMenu = ref(null)

const toggleCourseTMenu = (event) => {
  courseTMenu.value.toggle(event)
}

function goToSettingCourseTool(tool) {
  return "/course/" + course.value.id + "/settings/" + tool.tool.title + "?sid=" + session.value?.id
}

const setToolVisibility = (tool, visibility) => {
  tool.resourceNode.resourceLinks[0].visibility = visibility
}

function changeVisibility(tool) {
  axios
    .post(
      ENTRYPOINT +
        "../r/course_tool/links/" +
        tool.resourceNode.id +
        "/change_visibility?cid=" +
        course.value.id +
        "&sid=" +
        session.value?.id,
    )
    .then((response) => setToolVisibility(tool, response.data.visibility))
    .catch((error) => console.log(error))
}

function onClickShowAll() {
  axios
    .post(ENTRYPOINT + `../r/course_tool/links/change_visibility/show?cid=${course.value.id}&sid=${session.value?.id}`)
    .then(() => {
      tools.value.forEach((tool) => setToolVisibility(tool, 2))
    })
    .catch((error) => console.log(error))
}

function onClickHideAll() {
  axios
    .post(ENTRYPOINT + `../r/course_tool/links/change_visibility/hide?cid=${course.value.id}&sid=${session.value?.id}`)
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
    const el = document.getElementById("course-tools")
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
  const toolTitle = htmlItem.dataset.tool
  const toolItem = tools.value.find((element) => element.title === toolTitle)

  if (!toolItem || !toolItem.iid) {
    console.error("[CourseHome] Tool item or iid missing", toolItem)
    return
  }

  // Send the updated values to the server
  await courseService.updateToolOrder(toolItem, newIndex, course.value.id, session.value?.id)
}

const isAllowedToEdit = ref(false)
const showCourseSequence = computed(() => {
  return platformConfigStore.getSetting("course.resource_sequence_show_dependency_in_course_intro") === "true"
})

onMounted(async () => {
  isAllowedToEdit.value = await checkIsAllowedToEdit()
  if ("true" === platformConfigStore.getSetting("editor.translate_html")) {
    setTimeout(() => {
      translateHtml()
    }, 1000)
  }

  await courseSettingsStore.loadCourseSettings(course.value.id, session.value?.id)
  documentAutoLaunch.value = parseInt(courseSettingsStore.getSetting("enable_document_auto_launch"), 10) || 0
  exerciseAutoLaunch.value = parseInt(courseSettingsStore.getSetting("enable_exercise_auto_launch"), 10) || 0
  lpAutoLaunch.value = parseInt(courseSettingsStore.getSetting("enable_lp_auto_launch"), 10) || 0
  forumAutoLaunch.value = parseInt(courseSettingsStore.getSetting("enable_forum_auto_launch"), 10) || 0
})

const onStudentViewChanged = async () => {
  isAllowedToEdit.value = await checkIsAllowedToEdit()

  await loadCourseTools(false)
}

const allowEditToolVisibilityInSession = computed(() => {
  const isInASession = session.value?.id

  return isInASession ? "true" === getSetting.value("session.allow_edit_tool_visibility_in_session") : true
})
</script>
