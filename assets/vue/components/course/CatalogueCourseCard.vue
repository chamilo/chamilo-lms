<script setup>
import Rating from "primevue/rating"
import Button from "primevue/button"
import Dialog from "primevue/dialog"
import { computed, onMounted, ref, watch } from "vue"
import { useRouter } from "vue-router"
import BaseButton from "../basecomponents/BaseButton.vue"
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
// initialize rating count from props if available
localCourse.value.ratingCount = Number(
  localCourse.value.ratingCount ?? props.course?.count ?? props.course?.ratingCount ?? 0,
)

// --- fetch rating ---
// adjust fetchRating to tolerate multiple formats returned by the API
const fetchRating = async () => {
  if (!localCourse.value?.id) return
  try {
    const sessionQuery = localCourse.value?.sessionId ? `?session=${localCourse.value.sessionId}` : ""
    const res = await fetch(`/catalogue/api/courses/${localCourse.value.id}/rating${sessionQuery}`, {
      headers: { Accept: "application/json" },
      credentials: "same-origin",
    })
    if (!res.ok) return
    const data = await res.json()
    // robust fallback: average, avg, ratingAvg, etc.
    localCourse.value.ratingAvg = Number(data.average ?? data.avg ?? data.ratingAvg ?? 0)
    // get count if provided by API
    localCourse.value.ratingCount = Number(data.count ?? data.countVotes ?? localCourse.value.ratingCount ?? 0)
  } catch (e) {
    console.error("fetchRating error", e)
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

    // fallback sur la valeur locale stockée puis sur props (toujours numérique).
    const prevVote = Number(oldVote ?? localCourse.value.userVote?.vote ?? props.course?.userVote?.vote ?? 0)

    if (localCourse.value.popularity === undefined && props.course?.popularity !== undefined) {
      localCourse.value.popularity = props.course.popularity
    }

    // Local rating/count adjustment to show immediate feedback
    const oldAvg = Number(localCourse.value.ratingAvg ?? 0)
    let oldCount = Number(localCourse.value.ratingCount ?? 0)
    let newCount = oldCount
    let newAvg = oldAvg

    if (prevVote === 0 && newVote > 0) {
      // new vote added
      newCount = oldCount + 1
      newAvg = newCount > 0 ? (oldAvg * oldCount + newVote) / newCount : newVote
    } else if (prevVote > 0 && newVote === 0) {
      // vote removed
      newCount = Math.max(oldCount - 1, 0)
      if (newCount === 0) {
        newAvg = 0
      } else {
        newAvg = (oldAvg * oldCount - prevVote) / newCount
      }
    } else if (prevVote > 0 && newVote > 0) {
      // vote changed
      newCount = oldCount
      newAvg = newCount > 0 ? (oldAvg * oldCount - prevVote + newVote) / newCount : newVote
    }

    // round like backend (2 decimals)
    localCourse.value.ratingAvg = Number((isFinite(newAvg) ? newAvg : 0).toFixed(2))
    localCourse.value.ratingCount = Math.max(0, Math.round(newCount))

    if (prevVote === 0 && newVote > 0) {
      localCourse.value.popularity = (localCourse.value.popularity || 0) + 1
    } else if (prevVote > 0 && newVote === 0) {
      localCourse.value.popularity = Math.max((localCourse.value.popularity || 1) - 1, 0)
    }

    localCourse.value.userVote = { vote: Number(newVote || 0) }
    // Emit to the parent to persist (the parent must call the API)
    emit("rate", { value: Number(newVote || 0), course: props.course })
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
    const sessionQuery = localCourse.value?.sessionId ? `?session=${localCourse.value.sessionId}` : ""
    const res = await fetch(`/catalogue/api/courses/${localCourse.value.id}/visits${sessionQuery}`, {
      headers: { Accept: "application/json" },
      credentials: "same-origin",
    })
    if (!res.ok) return
    const data = await res.json()
    localCourse.value.nbVisits = Number(data.visits ?? 0)
  } catch (e) {
    console.error("fetchVisits error", e)
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
      0,
  )
})

// formatted string for the numeric average
const formattedRatingAvg = computed(() => {
  const v = Number(displayRatingAvg.value ?? 0)

  if (!isFinite(v)) {
    return "0.0"
  }

  return v.toFixed(1)
})

// computed used by the Rating component: shows the user's vote if available, otherwise the average
const onUserRate = (val) => {
  // local update of the vote — do not overwrite the value if `val` is null/undefined
  localVote.value = Number(val ?? localVote.value)
  // keeping the existing reset (optional)
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

    const useAutoSession = platformConfigStore.getSetting("catalog.course_subscription_in_user_s_session") === "true"

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
  localCourse.value.id,
  localCourse.value.sessionId || 0,
)

onMounted(() => {
  fetchStatus()
})
</script>

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

    <div class="relative">
      <span
        v-if="localCourse.courseLanguage"
        class="absolute top-0 right-0 bg-support-4 text-white text-xs px-2 py-0.5 font-semibold rounded-bl-lg z-20"
        v-text="getOriginalLanguageName(localCourse.courseLanguage)"
      />

      <router-link
        v-if="imageLink"
        :to="imageLink"
      >
        <img
          :alt="localCourse.title"
          :src="localCourse.illustrationUrl"
          class="w-full object-cover"
        />
      </router-link>
      <img
        v-else
        :alt="localCourse.title"
        :src="localCourse.illustrationUrl"
        class="w-full object-cover"
      />

      <BaseButton
        v-if="allowDescription && showInfoPopup"
        icon="information"
        only-icon
        size="small"
        type="black"
        @click="showDescriptionDialog = true"
        class="absolute bottom-0 left-0 rounded-none"
      />
    </div>

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
      <div class="my-1 flex items-baseline gap-2">
        <span
          v-if="displayRatingAvg !== null"
          aria-hidden="true"
          class="text-sm font-normal leading-none"
        >
          {{ formattedRatingAvg }}
        </span>

        <Rating
          v-if="props.currentUserId"
          :key="`rating-${localCourse.id}-${ratingResetKey}`"
          :cancel="false"
          :modelValue="displayRatingAvg"
          :stars="5"
          class="text-sm self-baseline inline-flex leading-none"
          @update:modelValue="onUserRate"
        />
      </div>
      <div
        v-if="localCourse.popularity || localVote"
        class="text-xs text-gray-600 mt-1"
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
            class="w-full"
            icon="pi pi-external-link"
          />
        </router-link>

        <Button
          v-else-if="isLocked && hasRequirements"
          :label="$t('Check requirements')"
          class="w-full p-button-warning"
          icon="mdi mdi-shield-check"
          @click="showDependenciesModal = true"
        />

        <Button
          v-else-if="localCourse.subscribe && props.currentUserId && allowSelfSignup"
          :label="$t('Subscribe')"
          class="w-full"
          icon="pi pi-sign-in"
          @click="subscribeToCourse"
        />

        <Button
          v-else-if="props.currentUserId && !allowSelfSignup"
          :label="$t('Subscription not allowed')"
          class="w-full"
          disabled
          icon="pi pi-ban"
        />

        <Button
          v-else-if="localCourse.visibility === 1"
          :label="$t('Private course')"
          class="w-full"
          disabled
          icon="pi pi-lock"
        />

        <Button
          v-else
          :label="$t('Not available')"
          class="w-full"
          disabled
          icon="pi pi-eye-slash"
        />
      </div>
    </div>
  </div>
  <CatalogueRequirementModal
    v-model="showDependenciesModal"
    :course-id="localCourse.id"
    :graph-image="graphImage"
    :requirements="requirementList"
    :session-id="localCourse.sessionId || 0"
  />
  <Dialog
    v-model:visible="showDescriptionDialog"
    :header="localCourse.title"
    class="w-96"
    modal
  >
    <p class="text-sm text-gray-700 whitespace-pre-line">
      {{ localCourse.description || $t("No description available") }}
    </p>
  </Dialog>
</template>
