<template>
  <Card class="course-card">
    <template #header>
      <div class="relative aspect-video w-full overflow-hidden rounded-t-2xl bg-gray-100">
        <img
          v-if="isLocked"
          :alt="course.title || 'Course illustration'"
          :src="imageUrl"
          class="absolute inset-0 h-full w-full object-cover"
          loading="lazy"
          referrerpolicy="no-referrer"
        />
        <BaseAppLink
          v-else
          :to="{ name: 'CourseHome', params: { id: course._id }, query: { sid: sessionId } }"
          aria-label="Open course"
          class="absolute inset-0 block"
        >
          <img
            :alt="course.title || 'Course illustration'"
            :src="imageUrl"
            class="h-full w-full object-cover"
            loading="lazy"
            referrerpolicy="no-referrer"
          />
        </BaseAppLink>
      </div>
    </template>
    <template #title>
      <div class="course-card__title">
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
          class="!bg-support-1 !text-support-3 !rounded-md !shadow-sm hover:!bg-support-2"
          icon="shield-check"
          onlyIcon
          size="large"
          type="black"
          @click="openRequirementsModal"
        />
      </div>

      <div
        v-if="showSessionDisplayDate && sessionDisplayDate"
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
    :graph-image="graphImage"
    :requirements="requirementList"
    :session-id="sessionId"
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
import { useUserSessionSubscription } from "../../composables/userPermissions"

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
  showSessionDisplayDate: {
    type: Boolean,
    required: false,
    default: true,
  },
})

const { t } = useI18n()
const platformConfigStore = usePlatformConfig()
const { isCoach } = useUserSessionSubscription(props.session, props.course)

const showRemainingDays = computed(() => {
  const v = platformConfigStore.getSetting("session.session_list_view_remaining_days")
  return v === true || v === "true" || v === 1 || v === "1"
})

const isDurationSession = computed(() => Number(props.session?.duration ?? 0) > 0)

const daysRemainingText = computed(() => {
  if (!showRemainingDays.value || isCoach.value || !isDurationSession.value) return null

  const daysLeft = Number(props.session?.daysLeft)
  if (!Number.isFinite(daysLeft)) return null

  if (daysLeft > 1) return `${daysLeft} days remaining`
  if (daysLeft === 1) return t("Ends tomorrow")
  if (daysLeft === 0) return t("Ends today")
  return t("Expired")
})

const sessionDurationText = computed(() => {
  if (!showRemainingDays.value || !isCoach.value || !isDurationSession.value) return null

  const d = Number(props.session?.duration ?? 0)
  if (!d) return null

  return d === 1 ? "1 day duration" : `${d} days duration`
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
  if (sessionDurationText.value) return sessionDurationText.value
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

const imageUrl = computed(
  () =>
    props.course?.illustrationUrl ||
    props.course?.image?.url ||
    props.course?.pictureUrl ||
    props.course?.thumbnail ||
    "/img/session_default.svg",
)

onMounted(() => {
  if (props.course?.id) {
    fetchStatus()
  }
})
function openRequirementsModal() {
  showDependenciesModal.value = true
}
</script>
