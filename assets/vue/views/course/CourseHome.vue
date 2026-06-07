<template>
  <div
    v-if="course"
    id="course-home"
    class="course-home"
  >
    <div
      v-if="courseHomeNotifyVisible"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
      role="dialog"
      aria-modal="true"
      :aria-label="courseHomeNotify.title"
    >
      <div class="max-h-[85vh] w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-xl">
        <div class="flex items-center justify-between gap-4 border-b border-gray-25 px-6 py-4">
          <h2 class="text-lg font-semibold text-gray-90">
            {{ courseHomeNotify.title }}
          </h2>

          <button
            type="button"
            class="inline-flex h-8 w-8 items-center justify-center rounded-full text-gray-50 hover:bg-gray-10 hover:text-gray-90"
            :aria-label="t('Close')"
            @click="closeCourseHomeNotify"
          >
            <span class="mdi mdi-close" />
          </button>
        </div>

        <div
          class="max-h-[55vh] overflow-y-auto px-6 py-4 text-gray-90"
          v-html="courseHomeNotify.content"
        />

        <div
          v-if="courseHomeNotify.requiresLink && courseHomeNotify.contentUrl"
          class="flex justify-end border-t border-gray-25 px-6 py-4"
        >
          <a
            :href="courseHomeNotify.contentUrl"
            class="btn btn--primary"
            target="_blank"
            rel="noopener noreferrer"
            @click="closeCourseHomeNotify"
          >
            <span class="mdi mdi-open-in-new ch-tool-icon" />
            {{ courseHomeNotify.linkLabel }}
          </a>
        </div>
      </div>
    </div>

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
      <SectionHeader :title="course.title">
        <BaseButton
          v-if="isAllowedToEdit && courseIntroEl?.introduction?.iid"
          :label="t('Edit introduction')"
          class="grow-0"
          icon="edit"
          type="secondary"
          @click="courseIntroEl.goToCreateOrUpdate()"
        />

        <BaseButton
          v-if="isAllowedToEdit"
          :label="t('Reporting')"
          :to-url="reportingUrl"
          icon="tracking"
          only-icon
          type="black"
          @click="courseIntroEl.goToCreateOrUpdate()"
        />

        <template v-if="hasCourseTMenuItems">
          <BaseButton
            :label="t('More actions')"
            icon="cog"
            only-icon
            popup-identifier="course-tmenu"
            type="secondary"
            @click="toggleCourseTMenu"
          />

          <BaseMenu
            id="course-tmenu"
            ref="courseTMenu"
            :model="courseItems"
          />
        </template>
      </SectionHeader>

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

      <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="min-w-0">
          <PluginRegion region="footer_left" />
        </div>
        <div class="min-w-0">
          <PluginRegion region="footer_center" />
        </div>
        <div class="min-w-0">
          <PluginRegion region="footer_right" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, provide, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import CourseTool from "../../components/course/CourseTool"
import ShortCutList from "../../components/course/ShortCutList.vue"
import Skeleton from "primevue/skeleton"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseMenu from "../../components/basecomponents/BaseMenu.vue"
import BaseToggleButton from "../../components/basecomponents/BaseToggleButton.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import Sortable from "sortablejs"
import { useIsAllowedToEdit } from "../../composables/userPermissions"
import { useCidReqStore } from "../../store/cidReq"
import { storeToRefs } from "pinia"
import courseService from "../../services/courseService"
import CourseIntroduction from "../../components/course/CourseIntroduction.vue"
import { usePlatformConfig } from "../../store/platformConfig"
import { useSecurityStore } from "../../store/securityStore"
import { useCourseSettings } from "../../store/courseSettingStore"
import NextCourseSequence from "../../components/course/NextCourseSequence.vue"
import CourseThematicProgress from "../../components/course/CourseThematicProgress.vue"
import PluginRegion from "../../components/layout/PluginRegion.vue"

const { t } = useI18n()
const cidReqStore = useCidReqStore()
const platformConfigStore = usePlatformConfig()
const securityStore = useSecurityStore()
const { course, session } = storeToRefs(cidReqStore)
const { getSetting } = storeToRefs(platformConfigStore)

const tools = ref([])
const shortcuts = ref([])
const courseHomeNotify = ref({})
const courseHomeNotifyVisible = ref(false)

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

function isLearningPathTool(tool) {
  return tool?.title === "learnpath" || tool?.tool?.title === "learnpath"
}

function shouldShowInvisibleLearningPathTool(tool) {
  return isLearningPathTool(tool) && "true" === getSetting.value("lp.show_invisible_lp_in_course_home")
}

const toolsForDisplay = computed(() => {
  // Teachers/admins can see all tools (even hidden) to manage them.
  if (isAllowedToEdit.value) {
    return tools.value
  }

  // Learners must not see hidden tools, except the learning path tool when enabled by platform setting.
  return tools.value.filter((tool) => {
    if (getToolVisibility(tool) === TOOL_VISIBILITY_VISIBLE) {
      return true
    }

    return shouldShowInvisibleLearningPathTool(tool)
  })
})

const reportingUrl = computed(() => {
  const cid = course.value?.id
  if (!cid) return null
  const sid = session.value?.id || 0
  return `/main/tracking/courseLog.php?cid=${cid}&sid=${sid}&gid=0`
})

const aiCourseAnalyzerUrl = computed(() => {
  const cid = course.value?.id
  if (!cid) return null

  const sid = session.value?.id || 0

  return `/ai/course/${cid}/analyzer?sid=${sid}`
})

function isSettingEnabled(value) {
  return value === true || value === "true" || value === 1 || value === "1"
}

const isAiCourseAnalyzerEnabled = computed(() => {
  return (
    isSettingEnabled(getSetting.value("ai_helpers.enable_ai_helpers")) &&
    isSettingEnabled(getSetting.value("ai_helpers.course_analyser"))
  )
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
      tool.isEnabled =
        tool.resourceNode?.resourceLinks?.[0]?.visibility === 2 || shouldShowInvisibleLearningPathTool(tool)

      return tool
    })

    const adminMenuItems = []
    const regularTools = []

    normalizedTools.forEach((tool) => {
      if (tool.title === "tracking") {
        // Tracking/Reporting is shown as a dedicated icon in the header, not in the tools grid.
      } else if (tool.tool?.category === "admin") {
        adminMenuItems.push({
          label: t(tool.tool.titleToShow),
          url: tool.url,
        })
      } else {
        regularTools.push(tool)
      }
    })

    if (isAllowedToEdit.value && isAiCourseAnalyzerEnabled.value && aiCourseAnalyzerUrl.value) {
      adminMenuItems.push({
        label: t("AI analyzer"),
        icon: "mdi mdi-robot-outline",
        url: aiCourseAnalyzerUrl.value,
        target: "_blank",
      })
    }

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

const hasCourseTMenuItems = computed(() => {
  return isAllowedToEdit.value && Array.isArray(courseItems.value) && courseItems.value.length > 0
})

const toggleCourseTMenu = (event) => {
  if (!courseTMenu.value) {
    return
  }
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
      "/r/course_tool/links/" +
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
    .post(`/r/course_tool/links/change_visibility/show?cid=${course.value.id}&sid=${session.value?.id}`)
    .then(() => {
      tools.value.forEach((tool) => setToolVisibility(tool, 2))
    })
    .catch((error) => console.log(error))
}

function onClickHideAll() {
  axios
    .post(`/r/course_tool/links/change_visibility/hide?cid=${course.value.id}&sid=${session.value?.id}`)
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

const { isAllowedToEdit } = useIsAllowedToEdit()


async function enforceCourseLegalAgreement() {
  if (!course.value?.id) {
    return
  }

  try {
    const data = await courseService.checkCourseLegalPlugin(course.value.id, session.value?.id || 0)

    if (data?.required && !data?.accepted && data?.url) {
      window.location.href = data.url
    }
  } catch (error) {
    console.error("[CourseLegal] Failed to check course legal agreement", error)
  }
}

async function loadCourseHomeNotification() {
  if (!course.value?.id) {
    return
  }

  try {
    const data = await courseService.getCourseHomeNotification(course.value.id, session.value?.id || 0)

    if (!data?.show) {
      courseHomeNotify.value = {}
      courseHomeNotifyVisible.value = false

      return
    }

    courseHomeNotify.value = data
    courseHomeNotifyVisible.value = true
  } catch (error) {
    console.error("[CourseHomeNotify] Failed to load course notification", error)
    courseHomeNotify.value = {}
    courseHomeNotifyVisible.value = false
  }
}

function closeCourseHomeNotify() {
  courseHomeNotifyVisible.value = false
}

const showCourseSequence = computed(() => {
  return platformConfigStore.getSetting("course.resource_sequence_show_dependency_in_course_intro") === "true"
})

onMounted(() => {
  enforceCourseLegalAgreement()
  loadCourseHomeNotification()

  documentAutoLaunch.value = parseInt(courseSettingsStore.getSetting("enable_document_auto_launch"), 10) || 0
  exerciseAutoLaunch.value = parseInt(courseSettingsStore.getSetting("enable_exercise_auto_launch"), 10) || 0
  lpAutoLaunch.value = parseInt(courseSettingsStore.getSetting("enable_lp_auto_launch"), 10) || 0
  forumAutoLaunch.value = parseInt(courseSettingsStore.getSetting("enable_forum_auto_launch"), 10) || 0
})

watch(
  () => platformConfigStore.isStudentViewActive,
  () => loadCourseTools(false),
)

const allowEditToolVisibilityInSession = computed(() => {
  const isInASession = session.value?.id

  return isInASession ? "true" === getSetting.value("session.allow_edit_tool_visibility_in_session") : true
})
</script>
