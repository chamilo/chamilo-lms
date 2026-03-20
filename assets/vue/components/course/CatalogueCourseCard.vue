<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import Button from "primevue/button"
import Dialog from "primevue/dialog"
import Card from "primevue/card"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseTag from "../basecomponents/BaseTag.vue"
import BaseAvatarList from "../basecomponents/BaseAvatarList.vue"
import BaseRating from "../basecomponents/BaseRating.vue"
import { useNotification } from "../../composables/notification"
import { usePlatformConfig } from "../../store/platformConfig"
import CatalogueRequirementModal from "./CatalogueRequirementModal.vue"
import courseRelUserService from "../../services/courseRelUserService"
import { useCourseRequirementStatus } from "../../composables/course/useCourseRequirementStatus"
import { useLocale } from "../../composables/locale"

const { t } = useI18n()
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

const localCourse = ref(JSON.parse(JSON.stringify(props.course || {})))
const localVote = ref(props.course?.userVote?.vote || 0)

localCourse.value.ratingAvg = Number(localCourse.value.ratingAvg ?? 0)
localCourse.value.ratingCount = Number(
  localCourse.value.ratingCount ?? props.course?.count ?? props.course?.ratingCount ?? 0,
)

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
    localCourse.value.ratingAvg = Number(data.average ?? data.avg ?? data.ratingAvg ?? 0)
    localCourse.value.ratingCount = Number(data.count ?? data.countVotes ?? localCourse.value.ratingCount ?? 0)
  } catch (e) {
    console.error("fetchRating error", e)
  }
}

onMounted(() => {
  fetchRating()
})

watch(
  localVote,
  (newVote, oldVote) => {
    if (newVote === oldVote) return

    const prevVote = Number(oldVote ?? localCourse.value.userVote?.vote ?? props.course?.userVote?.vote ?? 0)

    if (localCourse.value.popularity === undefined && props.course?.popularity !== undefined) {
      localCourse.value.popularity = props.course.popularity
    }

    const oldAvg = Number(localCourse.value.ratingAvg ?? 0)
    let oldCount = Number(localCourse.value.ratingCount ?? 0)
    let newCount = oldCount
    let newAvg = oldAvg

    if (prevVote === 0 && newVote > 0) {
      newCount = oldCount + 1
      newAvg = newCount > 0 ? (oldAvg * oldCount + newVote) / newCount : newVote
    } else if (prevVote > 0 && newVote === 0) {
      newCount = Math.max(oldCount - 1, 0)
      if (newCount === 0) {
        newAvg = 0
      } else {
        newAvg = (oldAvg * oldCount - prevVote) / newCount
      }
    } else if (prevVote > 0 && newVote > 0) {
      newCount = oldCount
      newAvg = newCount > 0 ? (oldAvg * oldCount - prevVote + newVote) / newCount : newVote
    }

    localCourse.value.ratingAvg = Number((isFinite(newAvg) ? newAvg : 0).toFixed(2))
    localCourse.value.ratingCount = Math.max(0, Math.round(newCount))

    if (prevVote === 0 && newVote > 0) {
      localCourse.value.popularity = (localCourse.value.popularity || 0) + 1
    } else if (prevVote > 0 && newVote === 0) {
      localCourse.value.popularity = Math.max((localCourse.value.popularity || 1) - 1, 0)
    }

    localCourse.value.userVote = { vote: Number(newVote || 0) }
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

const formattedRatingAvg = computed(() => {
  const v = Number(displayRatingAvg.value ?? 0)

  if (!isFinite(v)) {
    return "0.0"
  }

  return v.toFixed(1)
})

const onUserRate = (val) => {
  localVote.value = Number(val ?? localVote.value)
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

function resolveLinkSetting(value) {
  if (value === "course_home") {
    if (routeExists("CourseHome")) {
      return { type: "route", to: { name: "CourseHome", params: { id: localCourse.value.id } } }
    }
  } else if (value === "course_about") {
    return { type: "url", to: `/course/${localCourse.value.id}/about` }
  } else if (value === "course_description_popup") {
    return { type: "popup" }
  }
  return null
}

const imageLinkResolved = computed(() => resolveLinkSetting(linkSettings.value.image_url))
const titleLinkResolved = computed(() => resolveLinkSetting(linkSettings.value.title_url))
const infoLinkResolved = computed(() => resolveLinkSetting(linkSettings.value.info_url))

const imageLink = computed(() => {
  const r = imageLinkResolved.value
  return r && r.type !== "popup" ? r.to : null
})

const imageOpensPopup = computed(() => imageLinkResolved.value?.type === "popup")

const titleLink = computed(() => {
  const r = titleLinkResolved.value
  return r && r.type !== "popup" ? r.to : null
})

const titleOpensPopup = computed(() => titleLinkResolved.value?.type === "popup")

const infoLink = computed(() => {
  const r = infoLinkResolved.value
  return r && r.type !== "popup" ? r.to : null
})

const infoOpensPopup = computed(() => infoLinkResolved.value?.type === "popup")

const showInfoButton = computed(() => !!infoLinkResolved.value)

const catalogueDescriptions = computed(() => {
  return Array.isArray(localCourse.value?.catalogueDescriptions) ? localCourse.value.catalogueDescriptions : []
})

const hasCatalogueDescription = computed(() => {
  return catalogueDescriptions.value.length > 0
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
  <Card class="course-card">
    <template #header>
      <div class="course-card__header">
        <BaseAppLink
          v-if="imageLink && typeof imageLink === 'string'"
          :url="imageLink"
          aria-label="Open course"
        >
          <img
            :alt="localCourse.title"
            :src="localCourse.illustrationUrl"
            loading="lazy"
            referrerpolicy="no-referrer"
          />
        </BaseAppLink>

        <BaseAppLink
          v-else-if="imageLink && typeof imageLink === 'object'"
          :to="imageLink"
          aria-label="Open course"
        >
          <img
            :alt="localCourse.title"
            :src="localCourse.illustrationUrl"
            loading="lazy"
            referrerpolicy="no-referrer"
          />
        </BaseAppLink>

        <a
          v-else-if="imageOpensPopup && allowDescription && hasCatalogueDescription"
          class="cursor-pointer"
          @click="showDescriptionDialog = true"
        >
          <img
            :alt="localCourse.title"
            :src="localCourse.illustrationUrl"
            loading="lazy"
            referrerpolicy="no-referrer"
          />
        </a>

        <img
          v-else
          :alt="localCourse.title"
          :src="localCourse.illustrationUrl"
        />

        <BaseAppLink
          v-if="allowDescription && showInfoButton && infoLink && typeof infoLink === 'string'"
          :url="infoLink"
          class="absolute bottom-0 left-0"
        >
          <BaseButton
            :label="t('Show description')"
            class="rounded-none"
            icon="information"
            only-icon
            size="small"
          />
        </BaseAppLink>

        <BaseAppLink
          v-else-if="allowDescription && showInfoButton && infoLink && typeof infoLink === 'object'"
          :to="infoLink"
          class="absolute bottom-0 left-0"
        >
          <BaseButton
            :label="t('Show description')"
            class="rounded-none"
            icon="information"
            only-icon
            size="small"
          />
        </BaseAppLink>

        <BaseButton
          v-else-if="allowDescription && infoOpensPopup && hasCatalogueDescription"
          :label="t('Show description')"
          class="absolute bottom-0 left-0 rounded-none"
          icon="information"
          only-icon
          size="small"
          @click="showDescriptionDialog = true"
        />
      </div>

      <div
        v-if="localCourse.categories?.length"
        class="course-card__category-list"
      >
        <BaseTag
          v-for="cat in localCourse.categories"
          :key="cat.id"
          :label="cat.title"
          type="secondary"
        />
      </div>

      <div class="course-card__language">
        <BaseTag
          v-if="localCourse.courseLanguage"
          :label="getOriginalLanguageName(localCourse.courseLanguage)"
          type="info"
        />
      </div>
    </template>

    <template #title>
      <div class="course-card__title">
        <BaseAppLink
          v-if="showTitle && titleLink && typeof titleLink === 'string'"
          :url="titleLink"
        >
          {{ localCourse.title }}
        </BaseAppLink>
        <BaseAppLink
          v-else-if="showTitle && titleLink && typeof titleLink === 'object'"
          :to="titleLink"
        >
          {{ localCourse.title }}
        </BaseAppLink>
        <a
          v-else-if="showTitle && titleOpensPopup && allowDescription && hasCatalogueDescription"
          class="cursor-pointer"
          @click="showDescriptionDialog = true"
        >
          {{ localCourse.title }}
        </a>
        <template v-else>{{ localCourse.title }}</template>
      </div>
    </template>

    <template
      #subtitle
      v-if="localCourse.duration || localCourse.dependencies?.length || localCourse.price"
    >
      <div v-if="localCourse.duration">
        <strong>{{ t("Duration") }}:</strong> {{ durationInHours }}
      </div>

      <div v-if="localCourse.dependencies?.length">
        <strong>{{ t("Dependencies") }}:</strong>
        {{ localCourse.dependencies.map((dep) => dep.title).join(", ") }}
      </div>

      <div v-if="localCourse.price !== undefined">
        <strong>{{ t("Price") }}:</strong>
        {{ localCourse.price > 0 ? "S/. " + localCourse.price.toFixed(2) : t("Free") }}
      </div>
    </template>

    <template #content>
      <BaseAvatarList :users="localCourse.teachers.map((cru) => cru.user)" />

      <div class="flex gap-2">
        <div
          v-if="displayRatingAvg !== null"
          class="text-caption"
        >
          {{ formattedRatingAvg }}
        </div>

        <BaseRating
          v-if="props.currentUserId"
          v-model="displayRatingAvg"
          @change="onUserRate($event.value)"
        />
      </div>

      <div
        v-if="localCourse.popularity || localVote"
        class="text-caption"
      >
        {{ localCourse.popularity || 0 }} Vote<span v-if="localCourse.popularity !== 1">s</span>
        |
        {{ localCourse.nbVisits || 0 }} Visite<span v-if="localCourse.nbVisits !== 1">s</span>
        <span v-if="localVote">
          |
          {{ t("Your vote") }} [{{ localVote }}]
        </span>
      </div>
    </template>

    <template #footer>
      <template
        v-for="extraField in localCourse.extra_fields"
        :key="extraField.variable"
      >
        <div
          v-if="extraField.value"
          class="text-caption space-x-2"
        >
          <strong v-text="extraField.text" />
          <span v-text="extraField.value" />
        </div>
      </template>

      <BaseAppLink
        v-if="localCourse.subscribed"
        :to="{ name: 'CourseHome', params: { id: localCourse.id } }"
      >
        <Button
          :label="t('Go to the course')"
          class="w-full"
          icon="mdi mdi-open-in-new"
        />
      </BaseAppLink>

      <Button
        v-else-if="isLocked && hasRequirements"
        :label="t('Check requirements')"
        class="w-full p-button-warning"
        icon="mdi mdi-shield-check"
        @click="showDependenciesModal = true"
      />

      <Button
        v-else-if="localCourse.subscribe && props.currentUserId && allowSelfSignup"
        :label="t('Subscribe')"
        class="w-full"
        icon="mdi mdi-login"
        @click="subscribeToCourse"
      />

      <Button
        v-else-if="props.currentUserId && !allowSelfSignup"
        :label="t('Subscription not allowed')"
        class="w-full"
        disabled
        icon="mdi mdi-cancel"
      />

      <Button
        v-else-if="localCourse.visibility === 1"
        :label="t('Private course')"
        class="w-full"
        disabled
        icon="mdi mdi-lock"
      />

      <Button
        v-else
        :label="t('Not available')"
        class="w-full"
        disabled
        icon="mdi mdi-eye-off"
      />
    </template>
  </Card>

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
    class="w-[95vw] md:w-[70vw] lg:w-[60vw]"
    modal
  >
    <div
      v-if="hasCatalogueDescription"
      class="space-y-6"
    >
      <section
        v-for="item in catalogueDescriptions"
        :key="item.iid ?? `${item.title}-${item.descriptionType}-${item.progress}`"
        class="space-y-3"
      >
        <h3
          v-if="item.title"
          class="text-lg font-semibold"
        >
          {{ item.title }}
        </h3>

        <div
          v-if="item.content"
          class="rich-html-content"
          v-html="item.content"
        />
      </section>
    </div>

    <p
      v-else
      class="whitespace-pre-line"
    >
      {{ t("No description available") }}
    </p>
  </Dialog>
</template>
<style scoped>
.rich-html-content :deep(img) {
  max-width: 100%;
  height: auto;
}
.rich-html-content :deep(video),
.rich-html-content :deep(iframe) {
  max-width: 100%;
}
.rich-html-content :deep(table) {
  width: 100%;
}
</style>
