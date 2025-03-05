<script setup>
import { useI18n } from "vue-i18n"
import { computed, ref } from "vue"
import { useRouter } from "vue-router"
import { storeToRefs } from "pinia"
import EmptyState from "../EmptyState.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import Skeleton from "primevue/skeleton"
import { useCidReqStore } from "../../store/cidReq"
import cToolIntroService from "../../services/cToolIntroService"
import courseService from "../../services/courseService"

const { t } = useI18n()
const router = useRouter()

const cidReqStore = useCidReqStore()

const { course, session } = storeToRefs(cidReqStore)

const intro = ref(null)
const currentSessionId = session.value?.id
const hasMismatchedSidLinks = computed(() => {
  if (!intro.value?.introText || !currentSessionId) return false

  const regex = /sid=(\d+)/g
  const matches = intro.value.introText.match(regex)
  return matches?.some((match) => match !== `sid=${currentSessionId}`) || false
})

defineProps({
  isAllowedToEdit: {
    type: Boolean,
    required: true,
  },
})

courseService.loadHomeIntro(course.value.id, session.value?.id).then((data) => (intro.value = data))

async function updateIntroLinks() {
  if (!intro.value?.introText || !currentSessionId) return

  const updatedIntroText = intro.value.introText.replace(/sid=\d+/g, `sid=${currentSessionId}`)

  const payload = {
    introText: updatedIntroText,
    iid: intro.value.c_tool.iid,
    resourceLinkList: [
      {
        sid: currentSessionId,
        cid: course.value.id,
        introText: updatedIntroText,
        visibility: "published",
      },
    ],
    ...(intro.value.iid && { iid: intro.value.iid }),
  }

  try {
    const response = await cToolIntroService.addToolIntro(course.value.id, payload)

    if (intro.value.iid) {
      alert(t("Introduction updated successfully!"))
    } else {
      intro.value.iid = response.data.iid
      alert(t("Introduction created successfully!"))
    }

    intro.value.introText = updatedIntroText
  } catch (error) {
    console.error("Error updating or creating the introduction:", error)
    alert(t("An error occurred."))
  }
}

const goToIntroCreate = () => {
  router.push({
    name: "ToolIntroCreate",
    params: {
      courseTool: intro.value.c_tool.iid,
    },
    query: {
      cid: course.value.id,
      sid: session.value?.id,
      parentResourceNodeId: course.value.resourceNode.id,
      ctoolIntroId: intro.value.iid,
    },
  })
}

const goToIntroUpdate = () => {
  router.push({
    name: "ToolIntroUpdate",
    params: {
      id: `/api/c_tool_intros/${intro.value.iid}`,
    },
    query: {
      cid: course.value.id,
      sid: session.value?.id,
      ctoolintroIid: intro.value.iid,
      ctoolId: intro.value.c_tool.iid,
      parentResourceNodeId: course.value.resourceNode.id,
      id: `/api/c_tool_intros/${intro.value.iid}`,
    },
  })
}

const goToCreateOrUpdate = () => {
  if (intro.value.createInSession) {
    goToIntroCreate()

    return
  }

  goToIntroUpdate()
}

defineExpose({
  introduction: intro,
  goToCreateOrUpdate,
})
</script>

<template>
  <div
    v-if="intro"
    class="mb-4"
  >
    <div v-if="intro.introText">
      <div v-html="intro.introText" />
      <BaseButton
        v-if="isAllowedToEdit && hasMismatchedSidLinks"
        :label="t('Update introduction links')"
        class="mt-2"
        icon="refresh"
        type="primary"
        @click="updateIntroLinks"
      />
    </div>
    <div v-else-if="isAllowedToEdit">
      <EmptyState
        :detail="t('Add a course introduction to display to your students.')"
        :summary="t('You don\'t have any course content yet.')"
        icon="courses"
      >
        <BaseButton
          :label="t('Course introduction')"
          class="mt-4"
          icon="plus"
          type="primary"
          @click="goToIntroCreate"
        />
      </EmptyState>
    </div>
  </div>
  <Skeleton
    v-else
    class="mb-4"
    height="21.5rem"
  />
</template>
