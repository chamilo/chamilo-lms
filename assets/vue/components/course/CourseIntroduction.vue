<script setup>
import { useI18n } from "vue-i18n"
import { computed, ref, watch } from "vue"
import { useRouter } from "vue-router"
import { storeToRefs } from "pinia"
import EmptyState from "../EmptyState.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import Skeleton from "primevue/skeleton"
import { useCidReqStore } from "../../store/cidReq"
import { usePlatformConfig } from "../../store/platformConfig"
import cToolIntroService from "../../services/cToolIntroService"
import courseService from "../../services/courseService"
import { filterTranslatedHtml } from "../../../js/translatehtml.js"

const props = defineProps({
  isAllowedToEdit: {
    type: Boolean,
    required: true,
  },
  tool: {
    type: String,
    default: "course_homepage",
  },
  compact: {
    type: Boolean,
    default: false,
  },
  emptySummary: {
    type: String,
    default: "",
  },
  emptyDetail: {
    type: String,
    default: "",
  },
})

const { t } = useI18n()
const router = useRouter()
const cidReqStore = useCidReqStore()
const { course, session } = storeToRefs(cidReqStore)
const platformConfigStore = usePlatformConfig()

const intro = ref(null)
const isLoading = ref(false)

const isCourseHomepage = computed(() => props.tool === "course_homepage")
const isEnabled = computed(() => {
  if (isCourseHomepage.value) {
    return true
  }

  const value = platformConfigStore.getSetting("course.enable_tool_introduction")

  return value === true || value === "true" || value === 1 || value === "1"
})

const currentSessionId = computed(() => session.value?.id || 0)

const hasIntroContent = computed(() => {
  const html = String(intro.value?.introText || "").trim()

  if (!html) {
    return false
  }

  const plainText = html
    .replace(/<style[\s\S]*?<\/style>/gi, "")
    .replace(/<script[\s\S]*?<\/script>/gi, "")
    .replace(/<[^>]*>/g, "")
    .replace(/&nbsp;/gi, "")
    .trim()

  return plainText.length > 0 || /<(img|video|audio|iframe|embed|object)\b/i.test(html)
})

const displayedIntroText = computed(() => {
  const text = intro.value?.introText

  if (!text || !hasIntroContent.value) {
    return null
  }

  if (platformConfigStore.getSetting("editor.translate_html") === "true") {
    return filterTranslatedHtml(text, window.user?.locale)
  }

  return text
})

function normalizeIntroResponse(response) {
  let data = response?.data || response || {}

  if (typeof data === "string") {
    try {
      data = JSON.parse(data)
    } catch (error) {
      console.error("Invalid tool introduction response:", data)
      data = {}
    }
  }

  return {
    ...data,
    c_tool: data.c_tool || {
      iid: data.cToolId || null,
      title: props.tool,
    },
  }
}

function getCourseToolId() {
  return intro.value?.c_tool?.iid || intro.value?.cToolId || null
}

async function loadIntro() {
  if (!isEnabled.value || !course.value?.id) {
    intro.value = null
    return
  }

  isLoading.value = true

  try {
    let data = null

    if (props.tool === "course_homepage") {
      data = await courseService.loadHomeIntro(course.value.id, currentSessionId.value)
    } else {
      data = await cToolIntroService.findCourseHomeInro(course.value.id, {
        sid: currentSessionId.value,
        tool: props.tool,
      })
    }

    intro.value = normalizeIntroResponse(data)
  } catch (error) {
    console.error("Error loading tool introduction:", error)
    intro.value = null
  } finally {
    isLoading.value = false
  }
}

async function createEmptyIntroIfNeeded() {
  if (intro.value?.iid && getCourseToolId()) {
    return
  }

  const response = await cToolIntroService.addToolIntro(course.value.id, {
    tool: props.tool,
    introText: intro.value?.introText || "",
    sid: currentSessionId.value || 0,
    resourceLinkList: [
      {
        sid: currentSessionId.value || 0,
        cid: course.value.id,
        visibility: "published",
      },
    ],
  })

  intro.value = normalizeIntroResponse(response)
}

async function openEditor() {
  await createEmptyIntroIfNeeded()

  const courseToolId = getCourseToolId()

  if (!intro.value?.iid || !courseToolId) {
    console.error("Cannot open tool introduction editor.", intro.value)
    return
  }

  router.push({
    name: "ToolIntroUpdate",
    params: {
      id: `/api/c_tool_intros/${intro.value.iid}`,
    },
    query: {
      cid: course.value.id,
      sid: currentSessionId.value || undefined,
      tool: props.tool,
      ctoolintroIid: intro.value.iid,
      ctoolId: courseToolId,
      parentResourceNodeId: course.value.resourceNode.id,
      id: `/api/c_tool_intros/${intro.value.iid}`,
    },
  })
}

watch(
  () => [isEnabled.value, course.value?.id, currentSessionId.value, props.tool],
  () => loadIntro(),
  { immediate: true },
)
</script>

<template>
  <div v-if="isEnabled">
    <template v-if="compact">
      <div
        v-if="!isLoading && isAllowedToEdit"
        class="mb-2 flex justify-end items-center"
      >
        <BaseButton
          :label="hasIntroContent ? t('Edit introduction') : t('Add introduction')"
          :icon="hasIntroContent ? 'pencil' : 'plus'"
          type="success"
          only-icon
          size="small"
          :title="hasIntroContent ? t('Edit introduction') : t('Add introduction')"
          @click="openEditor"
        />
      </div>

      <div
        v-if="!isLoading && hasIntroContent"
        class="mb-4"
        v-html="displayedIntroText"
      />
    </template>

    <template v-else>
      <div
        v-if="!isLoading && hasIntroContent"
        class="mb-4"
      >
        <div
          v-if="isAllowedToEdit"
          class="mb-2 flex justify-end items-center"
        >
          <BaseButton
            :label="t('Edit introduction')"
            icon="pencil"
            type="success"
            only-icon
            size="small"
            :title="t('Edit introduction')"
            @click="openEditor"
          />
        </div>

        <div v-html="displayedIntroText" />
      </div>

      <EmptyState
        v-else-if="!isLoading && isAllowedToEdit"
        :detail="emptyDetail || t('Add a course introduction to display to your students.')"
        :summary="emptySummary || t('You don\'t have any course content yet.')"
        icon="courses"
      >
        <BaseButton
          :label="t('Course introduction')"
          class="mt-4"
          icon="plus"
          type="success"
          @click="openEditor"
        />
      </EmptyState>

      <Skeleton
        v-else-if="isLoading"
        class="mb-4"
        height="21.5rem"
      />
    </template>
  </div>
</template>
