<template>
  <Card class="course-card">
    <template #header>
      <img
        v-if="disabled"
        :src="course.illustrationUrl"
        :alt="course.title"
      />
      <BaseAppLink
        v-else
        :to="{ name: 'CourseHome', params: { id: course._id }, query: { sid: sessionId } }"
        class="course-card__home-link"
      >
        <img
          :src="course.illustrationUrl"
          :alt="course.title"
        />
      </BaseAppLink>
    </template>
    <template #title>
      <div class="course-card__title">
        <div v-if="disabled">
          <div
            v-if="session"
            class="session__title"
            v-text="session.title"
          />
          {{ course.title }}
          <span v-if="showCourseDuration && course.duration">
            ({{ (course.duration / 60 / 60).toFixed(2) }} hours)
          </span>
        </div>
        <BaseAppLink
          v-else
          :to="{ name: 'CourseHome', params: { id: course._id }, query: { sid: sessionId } }"
          class="course-card__home-link"
        >
          <div
            v-if="session"
            class="session__title"
            v-text="session.title"
          />
          {{ course.title }}
        </BaseAppLink>

        <div
          v-if="sessionDisplayDate"
          class="session__display-date"
          v-text="sessionDisplayDate"
        />
      </div>
    </template>
    <template #footer>
      <BaseAvatarList :users="teachers" />
    </template>
  </Card>
</template>

<script setup>
import Card from "primevue/card"
import BaseAvatarList from "../basecomponents/BaseAvatarList.vue"
import { computed } from "vue"
import { isEmpty } from "lodash"
import { useFormatDate } from "../../composables/formatDate"
import BaseAppLink from "../basecomponents/BaseAppLink.vue"
import { usePlatformConfig } from "../../store/platformConfig"

const { abbreviatedDatetime } = useFormatDate()

const props = defineProps({
  course: {
    type: Object,
    required: true,
  },
  session: {
    type: Object,
    required: false,
    default: null,
  },
  sessionId: {
    type: Number,
    required: false,
    default: 0,
  },
  disabled: {
    type: Boolean,
    required: false,
    default: false,
  },
})

const platformConfigStore = usePlatformConfig()
const showCourseDuration = computed(() => "true" === platformConfigStore.getSetting("course.show_course_duration"))

const teachers = computed(() => {
  if (props.session?.courseCoachesSubscriptions) {
    return props.session.courseCoachesSubscriptions
      .filter((srcru) => srcru.course["@id"] === props.course["@id"])
      .map((srcru) => srcru.user)
  }

  if (props.course.users && props.course.users.edges) {
    return props.course.users.edges.map((edge) => ({
      id: edge.node.id,
      ...edge.node.user,
    }))
  }

  return []
})

const sessionDisplayDate = computed(() => {
  const dateString = []

  if (props.session) {
    if (!isEmpty(props.session.displayStartDate)) {
      dateString.push(abbreviatedDatetime(props.session.displayStartDate))
    }

    if (!isEmpty(props.session.displayEndDate)) {
      dateString.push(abbreviatedDatetime(props.session.displayEndDate))
    }
  }

  return dateString.join(" — ")
})
</script>
