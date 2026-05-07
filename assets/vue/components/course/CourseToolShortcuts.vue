<script setup>
import { computed, ref, watch } from "vue"
import { useRoute } from "vue-router"
import { storeToRefs } from "pinia"
import { useCidReqStore } from "../../store/cidReq"
import { usePlatformConfig } from "../../store/platformConfig"
import courseService from "../../services/courseService"

const route = useRoute()
const cidReqStore = useCidReqStore()
const { course, session, group } = storeToRefs(cidReqStore)

const platformConfigStore = usePlatformConfig()

const tools = ref([])
const isLoading = ref(false)

const isEnabled = computed(() => {
  const value = platformConfigStore.getSetting("course.show_toolshortcuts")

  return value === true || value === "true" || value === 1 || value === "1"
})

const isCourseHome = computed(() => {
  return /^\/course\/\d+\/home\/?$/.test(route.path)
})

const shouldShowShortcuts = computed(() => {
  return isEnabled.value && !isCourseHome.value
})

const visibleTools = computed(() => {
  return tools.value.filter((courseTool) => {
    if (!courseTool?.url) {
      return false
    }

    return (
      courseTool.visibility === true ||
      courseTool.visibility === 1 ||
      courseTool.visibility === 2 ||
      courseTool.visibility === "true" ||
      courseTool.visibility === "1" ||
      courseTool.visibility === "2"
    )
  })
})

function getToolTitle(courseTool) {
  return courseTool?.tool?.titleToShow || courseTool?.title || courseTool?.tool?.title || ""
}

function getToolIconClass(courseTool) {
  const icon = courseTool?.tool?.icon || "mdi-tools"

  if (icon.startsWith("mdi ")) {
    return icon
  }

  if (icon.startsWith("mdi-")) {
    return `mdi ${icon}`
  }

  return `mdi mdi-${icon}`
}

function getInternalUrl(url) {
  if (!url) {
    return null
  }

  try {
    const parsedUrl = new URL(url, window.location.origin)

    if (parsedUrl.origin !== window.location.origin) {
      return null
    }

    return parsedUrl
  } catch (error) {
    return null
  }
}

function getToolUrl(courseTool) {
  const parsedUrl = getInternalUrl(courseTool?.url)

  if (!parsedUrl) {
    return "#"
  }

  if (session.value?.id && !parsedUrl.searchParams.has("sid")) {
    parsedUrl.searchParams.set("sid", session.value.id)
  }

  if (group.value?.id && !parsedUrl.searchParams.has("gid")) {
    parsedUrl.searchParams.set("gid", group.value.id)
  }

  return parsedUrl.pathname + parsedUrl.search + parsedUrl.hash
}

function isCurrentTool(courseTool) {
  try {
    const toolUrl = new URL(getToolUrl(courseTool), window.location.origin)

    return toolUrl.pathname === window.location.pathname
  } catch (error) {
    return false
  }
}

async function loadCourseTools() {
  if (!shouldShowShortcuts.value || !course.value?.id) {
    tools.value = []
    return
  }

  isLoading.value = true

  try {
    const response = await courseService.loadCTools(course.value.id, session.value?.id || 0)
    const loadedTools = response?.data || response || []

    tools.value = Array.isArray(loadedTools) ? loadedTools : []
  } catch (error) {
    console.error("Error loading course tool shortcuts:", error)
    tools.value = []
  } finally {
    isLoading.value = false
  }
}

watch(
  () => [shouldShowShortcuts.value, course.value?.id, session.value?.id, route.path],
  () => loadCourseTools(),
  { immediate: true },
)
</script>

<template>
  <nav
    v-if="shouldShowShortcuts && !isLoading && visibleTools.length"
    class="mb-4 flex flex-wrap items-center gap-2 rounded-lg border border-gray-25 bg-white p-2 shadow-sm"
    aria-label="Course tool shortcuts"
  >
    <a
      v-for="courseTool in visibleTools"
      :key="courseTool.iid"
      :href="getToolUrl(courseTool)"
      :class="[
        'inline-flex h-10 w-10 items-center justify-center rounded-md text-gray-70 transition hover:bg-primary hover:text-white',
        { 'bg-primary text-white': isCurrentTool(courseTool) },
      ]"
      :title="getToolTitle(courseTool)"
      :aria-label="getToolTitle(courseTool)"
    >
      <i
        :class="getToolIconClass(courseTool)"
        class="text-xl"
        aria-hidden="true"
      />
    </a>
  </nav>
</template>
