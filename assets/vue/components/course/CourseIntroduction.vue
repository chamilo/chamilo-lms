<script setup>
import { useI18n } from "vue-i18n"
import { ref } from "vue"
import { useRouter } from "vue-router"
import { storeToRefs } from "pinia"
import EmptyState from "../EmptyState.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import Skeleton from "primevue/skeleton"
import { useCidReqStore } from "../../store/cidReq"
import courseService from "../../services/courseService"

const { t } = useI18n()
const router = useRouter()

const cidReqStore = useCidReqStore()

const { course, session } = storeToRefs(cidReqStore)

const intro = ref(null)

defineProps({
  isAllowedToEdit: {
    type: Boolean,
    required: true,
  },
})

courseService.loadHomeIntro(course.value.id, session.value?.id).then((data) => (intro.value = data))

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
    <div
      v-if="intro.introText"
      v-html="intro.introText"
    />
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
