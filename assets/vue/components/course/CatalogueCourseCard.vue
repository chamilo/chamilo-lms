<template>
  <div
    class="course-card relative hover:shadow-lg transition duration-300 rounded-2xl overflow-hidden border border-gray-300 bg-white flex flex-col"
  >
    <div
      v-if="localCourse.categories?.length"
      class="absolute top-2 left-2 flex flex-wrap gap-1 z-30"
    >
     <span
       v-for="cat in localCourse.categories"
       :key="cat.id"
       class="bg-support-5 text-white text-xs font-bold px-2 py-0.5 rounded"
     >
       {{ cat.title }}
     </span>
    </div>
    <span
      v-if="localCourse.courseLanguage"
      class="absolute top-0 right-0 bg-support-4 text-white text-xs px-2 py-0.5 font-semibold rounded-bl-lg z-20"
    >
     {{ getOriginalLanguageName(localCourse.courseLanguage) }}
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
        :src="localCourse.illustrationUrl"
        :alt="localCourse.title"
        class="w-full object-cover"
      />
    </router-link>
    <img
      v-else
      :src="localCourse.illustrationUrl"
      :alt="localCourse.title"
      class="w-full object-cover"
    />
    <div class="p-4 flex flex-col flex-grow gap-2">
      <router-link
        v-if="showTitle && titleLink"
        :to="titleLink"
        class="text-xl font-semibold"
      >
        {{ localCourse.title }}
      </router-link>
      <h3
        v-else-if="showTitle"
        class="text-xl font-semibold"
      >
        {{ localCourse.title }}
      </h3>
      <div
        v-if="localCourse.duration"
        class="text-sm text-gray-700"
      >
        <strong>{{ $t("Duration") }}:</strong> {{ durationInHours }}
      </div>

      <div
        v-if="localCourse.dependencies?.length"
        class="text-sm text-gray-700"
      >
        <strong>{{ $t("Dependencies") }}:</strong>
        {{ localCourse.dependencies.map((dep) => dep.title).join(", ") }}
      </div>

      <div
        v-if="localCourse.price !== undefined"
        class="text-sm text-gray-700"
      >
        <strong>{{ $t("Price") }}:</strong>
        {{ localCourse.price > 0 ? "S/. " + localCourse.price.toFixed(2) : $t("Free") }}
      </div>
      <div
        v-if="localCourse.teachers?.length"
        class="text-sm text-gray-700"
      >
        <strong>{{ $t("Teachers") }}:</strong>
        {{ localCourse.teachers.map((t) => t.user.fullName).join(", ") }}
      </div>
      <div class="mt-2 flex items-center">
        <Rating
          v-if="props.currentUserId"
          :key="`rating-${localCourse.id}-${ratingResetKey}`"
          :modelValue="displayRatingAvg"
          :stars="5"
          :cancel="true"
          class="mt-2"
          @update:modelValue="onUserRate"
        />
      </div>
      <div
        class="text-xs text-gray-600 mt-1"
        v-if="localCourse.popularity || localVote"
      >
        {{ localCourse.popularity || 0 }} Vote<span v-if="localCourse.popularity !== 1">s</span>
        |
        {{ localCourse.nbVisits || 0 }} Visite<span v-if="localCourse.nbVisits !== 1">s</span>
        <span v-if="localVote">
         |
         {{ $t("Your vote") }} [{{ localVote }}]
       </span>
      </div>

      <div
        v-for="field in cardExtraFields"
        :key="field.variable"
        class="text-sm text-gray-700"
      >
        <strong>{{ field.display_text }}:</strong>
        {{ localCourse.extra_fields?.[field.variable] ?? "-" }}
      </div>

      <div class="mt-auto pt-2">
        <router-link
          v-if="localCourse.subscribed"
          :to="{ name: 'CourseHome', params: { id: localCourse.id } }"
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
          v-else-if="localCourse.subscribe && props.currentUserId && allowSelfSignup"
          :label="$t('Subscribe')"
          icon="pi pi-sign-in"
          class="w-full"
          @click="subscribeToCourse"
        />

        <Button
          v-else-if="props.currentUserId && !allowSelfSignup"
          :label="$t('Subscription not allowed')"
          icon="pi pi-ban"
          disabled
          class="w-full"
        />

        <Button
          v-else-if="localCourse.visibility === 1"
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
    :course-id="localCourse.id"
    :session-id="localCourse.sessionId || 0"
    :requirements="requirementList"
    :graph-image="graphImage"
  />
  <Dialog v-model:visible="showDescriptionDialog" :header="localCourse.title" modal class="w-96">
    <p class="text-sm text-gray-700 whitespace-pre-line">
      {{ localCourse.description || $t("No description available") }}
    </p>
  </Dialog>
</template>
<script setup>
import Rating from "primevue/rating"
import Button from "primevue/button"
import Dialog from "primevue/dialog"
import { computed, ref, onMounted, watch } from "vue"
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
const ratingResetKey = ref(0)


// local copy for display / optimistic updates
const localCourse = ref(JSON.parse(JSON.stringify(props.course || {})))
// local reference for the vote
const localVote = ref(props.course?.userVote?.vote || 0)
// ensure numeric placeholders
localCourse.value.ratingAvg = Number(localCourse.value.ratingAvg ?? 0)

// --- fetch rating ---
// adjust fetchRating to tolerate multiple formats returned by the API
const fetchRating = async () => {
  if (!localCourse.value?.id) return
  try {
    const sessionQuery = localCourse.value?.sessionId ? `?session=${localCourse.value.sessionId}` : ''
    const res = await fetch(`/catalogue/api/courses/${localCourse.value.id}/rating${sessionQuery}`, {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
    })
    if (!res.ok) return
    const data = await res.json()
    // robust fallback: average, avg, ratingAvg, etc.
    localCourse.value.ratingAvg = Number(data.average ?? data.avg ?? data.ratingAvg ?? 0)
  } catch (e) {
    console.error('fetchRating error', e)
  }
}

// call on mount
onMounted(() => {
  fetchRating()
})

// watcher on localVote: apply update + emit but do not alter the local average
watch(
  localVote,
  (newVote, oldVote) => {
    if (newVote === oldVote) return


    const prevVote = props.course?.userVote?.vote ?? oldVote ?? 0


    if ((localCourse.value.popularity === undefined) && props.course?.popularity !== undefined) {
      localCourse.value.popularity = props.course.popularity
    }


    if (prevVote === 0 && newVote > 0) {
      localCourse.value.popularity = (localCourse.value.popularity || 0) + 1
    } else if (prevVote > 0 && newVote === 0) {
      localCourse.value.popularity = Math.max((localCourse.value.popularity || 1) - 1, 0)
    }


    localCourse.value.userVote = { vote: newVote }


    // Emit to the parent to persist (the parent must call the API)
    emit("rate", { value: newVote, course: props.course })
  },
  { immediate: false },
)

const allowSelfSignup = computed(() => {
  if (localCourse.value?.allow_self_signup !== undefined) return Boolean(localCourse.value.allow_self_signup)
  if (localCourse.value?.allowSelfSignup !== undefined) return Boolean(localCourse.value.allowSelfSignup)
  return localCourse.value?.visibility === 0
})
localCourse.value.nbVisits = Number(localCourse.value.nbVisits ?? 0)

// fetch visits
const fetchVisits = async () => {
  if (!localCourse.value?.id) return
  try {
    const sessionQuery = localCourse.value?.sessionId ? `?session=${localCourse.value.sessionId}` : ''
    const res = await fetch(`/catalogue/api/courses/${localCourse.value.id}/visits${sessionQuery}`, {
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin',
    })
    if (!res.ok) return
    const data = await res.json()
    localCourse.value.nbVisits = Number(data.visits ?? 0)
  } catch (e) {
    console.error('fetchVisits error', e)
  }
}

onMounted(fetchVisits)



const allowDescription = computed(
  () => platformConfigStore.getSetting("catalog.show_courses_descriptions_in_catalog") !== "false",
)

const durationInHours = computed(() => {
  if (!localCourse.value.duration) return "-"
  const duration = localCourse.value.duration / 3600
  return localCourse.value.durationExtra ? `${duration.toFixed(2)}+ h` : `${duration.toFixed(2)} h`
})


// the display computed to prioritize the local value (optimistic/fetch)
const displayRatingAvg = computed(() => {
  return Number(
    localCourse.value?.ratingAvg ??
    props.course?.average ??
    props.course?.avg ??
    props.course?.avgRating ??
    localCourse.value?.avg ??
    0
  )
})

// computed used by the Rating component: shows the user's vote if available, otherwise the average
const onUserRate = (val) => {
  // updates localVote -> watcher handles popularity + emit("rate")
  localVote.value = Number(val || 0)
  // force the remount of the Rating component to return to displaying the average
  setTimeout(() => {
    ratingResetKey.value++
  }, 0)
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
      platformConfigStore.getSetting("catalog.course_subscription_in_user_s_session") === "true"

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
  const settings = platformConfigStore.getSetting("catalog.course_catalog_settings")
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
    return { name: routeName, params: { id: localCourse.value.id } }
  }

  return null
})

const titleLink = computed(() => {
  const routeName = linkSettings.value.title_url === "course_home" ? "CourseHome" : null

  if (routeName && routeExists(routeName)) {
    return { name: routeName, params: { id: localCourse.value.id } }
  }

  return null
})

const showInfoPopup = computed(() => {
  const allowed = ["course_description_popup"]
  const value = linkSettings.value.info_url
  return value && allowed.includes(value)
})

const { isLocked, hasRequirements, requirementList, graphImage, fetchStatus } = useCourseRequirementStatus(
  () => localCourse.value.id,
  () => localCourse.value.sessionId || 0,
)

onMounted(() => {
  fetchStatus()
})
</script>
