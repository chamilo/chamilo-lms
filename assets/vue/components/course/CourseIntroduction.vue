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
import { filterTranslatedHtml } from "../../../js/translatehtml.js"
import { useIsAllowedToEdit } from "../../composables/userPermissions"

const props = defineProps({
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
const { isAllowedToEdit } = useIsAllowedToEdit()

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

  if ([true, "true", 1, "1"].includes(platformConfigStore.getSetting("editor.translate_html"))) {
    return filterTranslatedHtml(text, window.user?.locale)
  }

  return text
})

async function loadIntro() {
  if (!isEnabled.value || !course.value?.id) {
    intro.value = null
    return
  }

  isLoading.value = true

  try {
    intro.value = await cToolIntroService.findCourseHomeInro(props.tool)
  } catch (error) {
    console.error("Error loading tool introduction:", error)
    intro.value = null
  } finally {
    isLoading.value = false
  }
}

async function createEmptyIntroIfNeeded() {
  // If an intro already exists for the CURRENT context, nothing to create. In a
  // session, an intro inherited from the base course (createInSession) must be
  // forked into a session-specific one before editing, so it does not short-circuit.
  if (intro.value?.iid && !intro.value?.createInSession) {
    return
  }

  intro.value = await cToolIntroService.addToolIntro(course.value.id, {
    toolName: props.tool,
    introText: intro.value?.introText || "",
  })
}

async function openEditor() {
  await createEmptyIntroIfNeeded()

  if (!intro.value?.iid) {
    console.error("Cannot open tool introduction editor.", intro.value)
    return
  }

  router.push({
    name: "ToolIntroUpdate",
    query: {
      cid: course.value.id,
      sid: currentSessionId.value || undefined,
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

      <div
        v-else-if="isLoading"
        aria-busy="true"
        class="mb-4 flex flex-col gap-2"
      >
        <Skeleton
          height="1rem"
          width="70%"
        />
        <Skeleton
          height="1rem"
          width="45%"
        />
      </div>
    </template>
  </div>
</template>
