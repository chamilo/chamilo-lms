<template>
  <div
    class="course-card relative hover:shadow-lg transition duration-300 rounded-2xl overflow-hidden border border-gray-300 bg-white flex flex-col"
  >
    <div
      v-if="course.categories?.length"
      class="absolute top-2 left-2 flex flex-wrap gap-1 z-30"
    >
      <span
        v-for="cat in course.categories"
        :key="cat.id"
        class="bg-support-5 text-white text-xs font-bold px-2 py-0.5 rounded"
      >
        {{ cat.title }}
      </span>
    </div>
    <span
      v-if="course.courseLanguage"
      class="absolute top-0 right-0 bg-support-4 text-white text-xs px-2 py-0.5 font-semibold rounded-bl-lg z-20"
    >
      {{ getOriginalLanguageName(course.courseLanguage) }}
    </span>

    <Button
      v-if="allowDescription && showInfoPopup"
      icon="pi pi-info-circle"
      @click="showDescriptionDialog = true"
      class="absolute top-10 left-2 z-20"
      size="small"
      text
      aria-label="Course info"
    />
    <router-link
      v-if="imageLink"
      :to="imageLink"
    >
      <img
        :src="course.illustrationUrl"
        :alt="course.title"
        class="w-full object-cover"
      />
    </router-link>
    <img
      v-else
      :src="course.illustrationUrl"
      :alt="course.title"
      class="w-full object-cover"
    />
    <div class="p-4 flex flex-col flex-grow gap-2">
      <router-link
        v-if="showTitle && titleLink"
        :to="titleLink"
        class="text-xl font-semibold"
      >
        {{ course.title }}
      </router-link>
      <h3
        v-else-if="showTitle"
        class="text-xl font-semibold"
      >
        {{ course.title }}
      </h3>
      <div
        v-if="course.duration"
        class="text-sm text-gray-700"
      >
        <strong>{{ $t("Duration") }}:</strong> {{ durationInHours }}
      </div>

      <div
        v-if="course.dependencies?.length"
        class="text-sm text-gray-700"
      >
        <strong>{{ $t("Dependencies") }}:</strong>
        {{ course.dependencies.map((dep) => dep.title).join(", ") }}
      </div>

      <div
        v-if="course.price !== undefined"
        class="text-sm text-gray-700"
      >
        <strong>{{ $t("Price") }}:</strong>
        {{ course.price > 0 ? "S/. " + course.price.toFixed(2) : $t("Free") }}
      </div>
      <div
        v-if="course.teachers?.length"
        class="text-sm text-gray-700"
      >
        <strong>{{ $t("Teachers") }}:</strong>
        {{ course.teachers.map((t) => t.user.fullName).join(", ") }}
      </div>
      <Rating
        v-if="props.currentUserId"
        :model-value="course.userVote?.vote || 0"
        :stars="5"
        :cancel="false"
        @change="emitRating"
        class="mt-2"
      />
      <div
        class="text-xs text-gray-600 mt-1"
        v-if="course.popularity || course.userVote?.vote"
      >
        {{ course.popularity || 0 }} Vote<span v-if="course.popularity !== 1">s</span>
        |
        {{ course.nbVisits || 0 }} Visite<span v-if="course.nbVisits !== 1">s</span>
        <span v-if="course.userVote?.vote">
          |
          {{ $t("Your vote") }} [{{ course.userVote.vote }}]
        </span>
      </div>

      <div
        v-for="field in cardExtraFields"
        :key="field.variable"
        class="text-sm text-gray-700"
      >
        <strong>{{ field.display_text }}:</strong>
        {{ course.extra_fields?.[field.variable] ?? "-" }}
      </div>

      <div class="mt-auto pt-2">
        <router-link
          v-if="isUserInCourse && (course.visibility === 2 || course.visibility === 3)"
          :to="{ name: 'CourseHome', params: { id: course.id } }"
        >
          <Button
            :label="$t('Go to the course')"
            icon="pi pi-external-link"
            class="w-full"
          />
        </router-link>

        <Button
          v-else-if="isLocked && hasRequirements"
          :label="$t('Check requirements')"
          icon="mdi mdi-shield-check"
          class="w-full p-button-warning"
          @click="showDependenciesModal = true"
        />

        <Button
          v-else-if="course.subscribe && props.currentUserId"
          :label="$t('Subscribe')"
          icon="pi pi-sign-in"
          class="w-full"
          @click="subscribeToCourse"
        />

        <Button
          v-else-if="course.visibility === 2 && !course.subscribe && props.currentUserId"
          :label="$t('Subscription not allowed')"
          icon="pi pi-ban"
          disabled
          class="w-full"
        />

        <Button
          v-else-if="course.visibility === 1"
          :label="$t('Private course')"
          icon="pi pi-lock"
          disabled
          class="w-full"
        />

        <Button
          v-else
          :label="$t('Not available')"
          icon="pi pi-eye-slash"
          disabled
          class="w-full"
        />
      </div>
    </div>
  </div>
  <CatalogueRequirementModal
    v-model="showDependenciesModal"
    :course-id="course.id"
    :session-id="course.sessionId || 0"
    :requirements="requirementList"
    :graph-image="graphImage"
  />
  <Dialog
    v-model:visible="showDescriptionDialog"
    :header="course.title"
    modal
    class="w-96"
  >
    <p class="text-sm text-gray-700 whitespace-pre-line">
      {{ course.description || $t("No description available") }}
    </p>
  </Dialog>
</template>
<script setup>
import Rating from "primevue/rating"
import Button from "primevue/button"
import Dialog from "primevue/dialog"
import { computed, ref, onMounted } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useNotification } from "../../composables/notification"
import { usePlatformConfig } from "../../store/platformConfig"
import CatalogueRequirementModal from "./CatalogueRequirementModal.vue"
import courseRelUserService from "../../services/courseRelUserService"
import { useCourseRequirementStatus } from "../../composables/course/useCourseRequirementStatus"
import { useLocale } from "../../composables/locale"
const { getOriginalLanguageName } = useLocale()

const props = defineProps({
  course: Object,
  currentUserId: {
    type: Number,
    default: null,
  },
  showTitle: {
    type: Boolean,
    default: true,
  },
  cardExtraFields: { type: Array, default: () => [] },
})

const emit = defineEmits(["rate", "subscribed"])

const router = useRouter()
const route = useRoute()
const { showErrorNotification, showSuccessNotification } = useNotification()
const platformConfigStore = usePlatformConfig()

const showDescriptionDialog = ref(false)
const showDependenciesModal = ref(false)

const allowDescription = computed(
  () => platformConfigStore.getSetting("course.show_courses_descriptions_in_catalog") !== "false",
)

const isUserInCourse = computed(() => {
  if (!props.currentUserId) return false
  return props.course.users?.some((user) => user.user.id === props.currentUserId)
})

const durationInHours = computed(() => {
  if (!props.course.duration) return "-"
  const duration = props.course.duration / 3600
  return props.course.durationExtra ? `${duration.toFixed(2)}+ h` : `${duration.toFixed(2)} h`
})

const emitRating = (event) => {
  emit("rate", { value: event.value, course: props.course })
}

const subscribing = ref(false)
const subscribeToCourse = async () => {
  if (!props.currentUserId) {
    showErrorNotification("You must be logged in to subscribe to a course.")
    return
  }

  try {
    subscribing.value = true

    const useAutoSession =
      platformConfigStore.getSetting("session.catalog_course_subscription_in_user_s_session") === "true"

    let sessionId = null

    if (useAutoSession) {
      const response = await courseRelUserService.autoSubscribeCourse(props.course.id)
      sessionId = response?.sessionId

      if (!sessionId) {
        throw new Error("No session ID returned after subscription.")
      }
    } else {
      const response = await courseRelUserService.subscribe({
        userId: props.currentUserId,
        courseId: props.course.id,
      })

      const userIdFromResponse = response?.user?.["@id"]?.split("/")?.pop()

      emit("subscribed", {
        courseId: props.course.id,
        newUser: { user: { id: Number(userIdFromResponse) } },
      })
    }

    showSuccessNotification("You have successfully subscribed to this course.")

    await router.push({
      name: "CourseHome",
      params: {
        id: props.course.id,
      },
      query: sessionId ? { sid: sessionId } : {},
    })
  } catch (e) {
    console.error("Subscription error:", e)
    showErrorNotification("Failed to subscribe to the course.")
  } finally {
    subscribing.value = false
  }
}

function routeExists(name) {
  return router.getRoutes().some((route) => route.name === name)
}

const linkSettings = computed(() => {
  const settings = platformConfigStore.getSetting("course.course_catalog_settings")
  return settings?.link_settings ?? {}
})

const imageLink = computed(() => {
  const routeName =
    linkSettings.value.image_url === "course_home"
      ? "CourseHome"
      : linkSettings.value.image_url === "course_about"
        ? "CourseAbout"
        : null

  if (routeName && routeExists(routeName)) {
    return { name: routeName, params: { id: props.course.id } }
  }

  return null
})

const titleLink = computed(() => {
  const routeName = linkSettings.value.title_url === "course_home" ? "CourseHome" : null

  if (routeName && routeExists(routeName)) {
    return { name: routeName, params: { id: props.course.id } }
  }

  return null
})

const showInfoPopup = computed(() => {
  const allowed = ["course_description_popup"]
  const value = linkSettings.value.info_url
  return value && allowed.includes(value)
})

const { isLocked, hasRequirements, requirementList, graphImage, fetchStatus } = useCourseRequirementStatus(
  props.course.id,
  props.course.sessionId || 0,
)

onMounted(() => {
  fetchStatus()
})
</script>
