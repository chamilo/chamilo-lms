<template>
  <div
    class="course-card relative hover:shadow-lg transition duration-300 rounded-2xl overflow-hidden border border-gray-300 bg-white flex flex-col"
  >
    <div
      v-if="!hasThumbnail"
      class="w-full h-40 bg-gray-30 flex items-center justify-center"
    >
      <i class="pi pi-calendar text-5xl text-gray-400" />
    </div>
    <img
      v-else
      :src="thumbnail"
      :alt="session.title"
      class="w-full h-40 object-cover"
      @error="onImageError"
    />
    <Button
      v-if="allowDescription"
      icon="pi pi-info-circle"
      @click="showDescriptionDialog = true"
      class="absolute top-2 left-2 z-20"
      size="small"
      text
      aria-label="Session info"
    />
    <span
      v-if="languages.length"
      class="absolute top-0 right-0 bg-primary text-white text-xs px-2 py-1 font-semibold rounded-bl-lg z-10"
    >
      {{ languages.length === 1 ? languages[0] : $t("Multilingual") }}
    </span>
    <div class="p-4 flex flex-col flex-grow gap-2">
      <h3 class="text-xl font-semibold text-gray-800">{{ session.title }}</h3>
      <div class="text-sm text-gray-700">
        <strong>{{ $t("Duration") }}:</strong> {{ duration }}
      </div>

      <div class="text-sm text-gray-700">
        <strong>{{ $t("Start date") }}:</strong> {{ formattedStartDate }}
      </div>

      <div class="text-sm text-gray-700">
        <strong>{{ $t("End date") }}:</strong> {{ formattedEndDate }}
      </div>

      <div
        class="text-sm text-blue-600 cursor-pointer underline"
        @click="showCourseDialog = true"
      >
        {{ courseCount }} {{ $t("Course") }}<span v-if="courseCount !== 1">s</span>
      </div>

      <div
        v-if="session.category"
        class="text-sm text-gray-700"
      >
        <strong>{{ $t("Category") }}:</strong> {{ session.category.title }}
      </div>

      <div
        v-if="teachers.length"
        class="text-sm text-gray-700"
      >
        <strong>{{ $t("Teachers") }}:</strong> {{ teachers.join(", ") }}
      </div>

      <Rating
        :model-value="session.userVote?.vote || 0"
        :stars="5"
        :cancel="false"
        @change="emitRating"
        class="mt-2"
      />
      <div class="text-xs text-gray-600 mt-1">
        {{ session.popularity || 0 }} Vote<span v-if="session.popularity !== 1">s</span>
        |
        {{ session.nbVisits || 0 }} Visite<span v-if="session.nbVisits !== 1">s</span>
        <span v-if="session.userVote?.vote">
          |
          {{ $t("Your vote") }} [{{ session.userVote.vote }}]
        </span>
      </div>

      <div class="mt-auto pt-2">
        <Button
          v-if="requirementStatusLoading"
          :label="$t('Loading...')"
          icon="pi pi-spin pi-spinner"
          class="w-full"
          disabled
        />

        <Button
          v-else-if="isPast"
          :label="$t('Not available')"
          icon="pi pi-lock"
          class="w-full"
          disabled
        />

        <template v-else-if="session.buyButtonHtml">
          <div
            v-html="session.buyButtonHtml"
            class="w-full text-center mb-2"
          ></div>
        </template>

        <Button
          v-else-if="isSessionLocked"
          :label="$t('Check requirements')"
          icon="mdi mdi-shield-check"
          class="w-full p-button-warning"
          @click="openSessionRequirementModal"
        />

        <Button
          v-else-if="allowAutoSubscription && !session.isSubscribed"
          :label="isLoading ? $t('Subscribing...') : $t('Subscribe')"
          :icon="isLoading ? 'pi pi-spin pi-spinner' : 'pi pi-user-plus'"
          class="w-full p-button-success"
          :disabled="isLoading"
          @click="subscribeToSession"
        />

        <Button
          v-else-if="session.isSubscribed && isFuture"
          :label="$t('Registered')"
          icon="pi pi-check"
          class="w-full p-button-outlined"
          disabled
        />

        <Button
          v-else-if="session.isSubscribed"
          :label="$t('Go to the session')"
          icon="pi pi-external-link"
          class="w-full"
          @click="showGoDialog = true"
        />

        <Button
          v-else
          :label="$t('Not available')"
          icon="pi pi-lock"
          class="w-full"
          disabled
        />
      </div>
    </div>

    <Dialog
      v-model:visible="showCourseDialog"
      :header="$t('Courses')"
      modal
      class="w-96"
    >
      <ul class="list-disc pl-5 text-sm text-gray-700">
        <template v-if="validCourses.length">
          <li
            v-for="item in validCourses"
            :key="item.id"
          >
            {{ item.title }}
          </li>
        </template>
        <template v-else>
          <li class="text-gray-500 italic">{{ $t("No courses available") }}</li>
        </template>
      </ul>
    </Dialog>
    <Dialog
      v-model:visible="showGoDialog"
      :header="$t('Select a course')"
      modal
      class="w-96"
    >
      <ul class="pl-2 text-sm text-gray-800 space-y-3">
        <template v-if="validCourses.length">
          <li
            v-for="item in validCourses"
            :key="item.id"
            class="flex justify-between items-center border-b pb-1"
          >
            <span class="w-2/3 truncate">{{ item.title }}</span>
            <a
              :href="`/course/${item.id}/home?sid=${session.id}`"
              target="_blank"
            >
              <Button
                icon="pi pi-sign-in"
                :label="$t('Go')"
                size="small"
                class="p-button-sm p-button-text"
              />
            </a>
          </li>
        </template>
        <template v-else>
          <li class="text-gray-500 italic">{{ $t("No courses available") }}</li>
        </template>
      </ul>
    </Dialog>
  </div>
  <Dialog
    v-model:visible="showDescriptionDialog"
    :header="session.title"
    modal
    class="w-96"
  >
    <p
      class="text-sm text-gray-700 whitespace-pre-line"
      v-html="session.description || $t('No description available')"
    />
  </Dialog>
  <CatalogueRequirementModal
    v-if="!requirementStatusLoading && isSessionLocked"
    v-model="showRequirementModal"
    :course-id="null"
    :session-id="props.session.id"
    :requirements="[
      ...(sessionRequirementStatus.requirementList.value || []),
      ...(sessionRequirementStatus.dependencyList.value || []),
    ]"
    :graph-image="sessionRequirementStatus.graphImage.value"
  />
</template>
<script setup>
import { ref, computed, watchEffect, onMounted } from "vue"
import Rating from "primevue/rating"
import Button from "primevue/button"
import Dialog from "primevue/dialog"
import axios from "axios"
import { useSecurityStore } from "../../store/securityStore"
import { usePlatformConfig } from "../../store/platformConfig"
import { useLocale } from "../../composables/locale"
import CatalogueRequirementModal from "../course/CatalogueRequirementModal.vue"
import { useSessionRequirementStatus } from "../../composables/session/useSessionRequirementStatus"

const props = defineProps({
  session: Object,
})

const showDescriptionDialog = ref(false)
const showCourseDialog = ref(false)
const showRequirementModal = ref(false)
const fallback = ref(false)

const platformConfigStore = usePlatformConfig()
const allowDescription = computed(
  () => platformConfigStore.getSetting("catalog.show_courses_descriptions_in_catalog") !== "false",
)
const { getOriginalLanguageName } = useLocale()

const showGoDialog = ref(false)
const isLoading = ref(false)

const securityStore = useSecurityStore()

const thumbnail = computed(() => (!fallback.value ? props.session.imageUrl || null : null))
const hasThumbnail = computed(() => !!props.session.imageUrl && !fallback.value)

const now = new Date()
const isPast = computed(() => {
  if (!props.session.endDate) return false
  const date = new Date(props.session.endDate)
  return !isNaN(date) && date < now
})

const isFuture = computed(() => {
  if (!props.session.startDate) return false
  const date = new Date(props.session.startDate)
  return !isNaN(date) && date > now
})

const onImageError = () => {
  fallback.value = true
}

watchEffect(() => {
  console.log("session.courses =", props.session.courses)
  if (props.session.courses) {
    props.session.courses.forEach((c, i) => console.log(`Course[${i}]`, c.course?.title))
  }
})

const validCourses = computed(() => {
  return (props.session.courses || []).filter((item) => item.title)
})

const courseCount = computed(() => validCourses.value.length)

const formattedStartDate = computed(() => {
  const d = new Date(props.session.startDate)
  return isNaN(d) ? "-" : d.toLocaleDateString()
})

const formattedEndDate = computed(() => {
  if (!props.session.endDate) return "-"
  const d = new Date(props.session.endDate)
  return isNaN(d.getTime()) ? "-" : d.toLocaleDateString()
})

const duration = computed(() => {
  const durations = (props.session.courses || []).map((item) => item.duration).filter((d) => typeof d === "number")

  const total = durations.reduce((a, b) => a + b, 0)
  const inHours = total / 3600
  const hasMissing = (props.session.courses || []).some((item) => item.duration == null)
  return hasMissing ? `${inHours.toFixed(2)}+ h` : `${inHours.toFixed(2)} h`
})

const languages = computed(() => {
  const langs = new Set()
  for (const item of props.session.courses || []) {
    const lang = item.courseLanguage
    if (lang) langs.add(getOriginalLanguageName(lang))
  }
  return [...langs]
})

const teachers = computed(() => {
  const all = []
  for (const item of props.session.courses || []) {
    for (const t of item.teachers || []) {
      if (t.fullName) all.push(t.fullName)
    }
  }
  return [...new Set(all)]
})

const requirementStatusLoading = ref(true)
const sessionRequirementStatus = useSessionRequirementStatus(props.session.id)

onMounted(async () => {
  requirementStatusLoading.value = true
  await sessionRequirementStatus.fetchStatus()
  requirementStatusLoading.value = false
})

const isSessionLocked = computed(() => {
  return !requirementStatusLoading.value && !sessionRequirementStatus.allowSubscription.value
})

const emit = defineEmits(["rate", "subscribed"])
const emitRating = (event) => {
  emit("rate", { value: event.value, session: props.session })
}

const allowAutoSubscription = computed(
  () => platformConfigStore.getSetting("catalog.allow_session_auto_subscription") === "true",
)

const subscribeToSession = async () => {
  isLoading.value = true
  try {
    const userId = securityStore.user.id
    const sessionId = props.session.id

    await axios.post("/api/session_rel_users", {
      user: `/api/users/${userId}`,
      session: `/api/sessions/${sessionId}`,
      relationType: 0,
      duration: 0,
    })

    const promises = (props.session.courses || []).map((c) =>
      axios.post("/api/session_rel_course_rel_users", {
        user: `/api/users/${userId}`,
        session: `/api/sessions/${sessionId}`,
        course: `/api/courses/${c.id}`,
        status: 0,
        visibility: 1,
        legalAgreement: 0,
        progress: 0,
      }),
    )

    await Promise.all(promises)
    emit("subscribed", props.session.id)
  } catch (error) {
    console.error("Error subscribing to session:", error)
    alert("There was an error subscribing. Please try again.")
  } finally {
    isLoading.value = false
  }
}

function openSessionRequirementModal() {
  showRequirementModal.value = true
}
</script>
