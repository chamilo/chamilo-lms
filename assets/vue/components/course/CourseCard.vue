<template>
  <Card class="course-card">
    <template #header>
      <img
        v-if="isLocked"
        :alt="course.title"
        :src="course.illustrationUrl || '/img/session_default.svg'"
      />
      <BaseAppLink
        v-else
        :to="{ name: 'CourseHome', params: { id: course._id }, query: { sid: sessionId } }"
        class="course-card__home-link"
      >
        <img
          :alt="course.title"
          :src="course.illustrationUrl || '/img/session_default.svg'"
        />
      </BaseAppLink>
    </template>
    <template #title>
      <div class="course-card__title flex items-center gap-2">
        <div v-if="isLocked">
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

        <BaseButton
          v-if="isLocked && hasRequirements"
          icon="shield-check"
          type="black"
          onlyIcon
          size="large"
          class="!bg-support-1 !text-support-3 !rounded-md !shadow-sm hover:!bg-support-2"
          @click="openRequirementsModal"
        />
      </div>

      <div
        v-if="sessionDisplayDate"
        class="session__display-date"
        v-text="sessionDisplayDate"
      />
    </template>
    <template #footer>
      <BaseAvatarList :users="teachers" />
    </template>
  </Card>

  <CatalogueRequirementModal
    v-model="showDependenciesModal"
    :course-id="course.id"
    :session-id="sessionId"
    :requirements="requirementList"
    :graph-image="graphImage"
  />
</template>

<script setup>
import Card from "primevue/card"
import BaseAvatarList from "../basecomponents/BaseAvatarList.vue"
import { computed, onMounted, ref } from "vue"
import { useFormatDate } from "../../composables/formatDate"
import { usePlatformConfig } from "../../store/platformConfig"
import { useI18n } from "vue-i18n"
import { useCourseRequirementStatus } from "../../composables/course/useCourseRequirementStatus"
import BaseButton from "../basecomponents/BaseButton.vue"
import CatalogueRequirementModal from "./CatalogueRequirementModal.vue"

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

const { t } = useI18n()
const platformConfigStore = usePlatformConfig()
const showRemainingDays = computed(
  () => platformConfigStore.getSetting("session.session_list_view_remaining_days") === "true",
)

const daysRemainingText = computed(() => {
  if (!showRemainingDays.value || !props.session?.displayEndDate) return null

  const endDate = new Date(props.session.displayEndDate)
  if (isNaN(endDate)) return null

  const today = new Date()
  const diff = Math.floor((endDate - today) / (1000 * 60 * 60 * 24))

  if (diff > 1) return `${diff} days remaining`
  if (diff === 1) return t("Ends tomorrow")
  if (diff === 0) return t("Ends today")
  return t("Expired")
})

const showCourseDuration = computed(() => platformConfigStore.getSetting("course.show_course_duration") === "true")

const teachers = computed(() => {
  if (props.session?.courseCoachesSubscriptions) {
    return props.session.courseCoachesSubscriptions
      .filter((srcru) => srcru.course["@id"] === props.course["@id"])
      .map((srcru) => srcru.user)
  }

  if (props.course.users?.edges) {
    return props.course.users.edges.map((edge) => ({
      id: edge.node.id,
      ...edge.node.user,
    }))
  }

  return []
})

const sessionDisplayDate = computed(() => {
  if (daysRemainingText.value) return daysRemainingText.value

  const parts = []
  if (props.session?.displayStartDate) parts.push(abbreviatedDatetime(props.session.displayStartDate))
  if (props.session?.displayEndDate) parts.push(abbreviatedDatetime(props.session.displayEndDate))

  return parts.join(" — ")
})

const internalLocked = ref(false)
const showDependenciesModal = ref(false)

const { hasRequirements, requirementList, graphImage, fetchStatus } = useCourseRequirementStatus(
  props.course.id,
  props.sessionId,
  (locked) => {
    internalLocked.value = locked
  },
)

const isLocked = computed(() => props.disabled || internalLocked.value)

onMounted(() => {
  if (props.course?.id) {
    fetchStatus()
  }
})
function openRequirementsModal() {
  showDependenciesModal.value = true
}
</script>
