<template>
  <div
    class="course-card hover:shadow-lg transition duration-300 rounded-2xl overflow-hidden border border-gray-300 bg-white flex flex-col"
  >
    <img
      :src="course.illustrationUrl"
      :alt="course.title"
      class="w-full h-40 object-cover"
    />
    <div class="p-4 flex flex-col flex-grow gap-2">
      <h3 class="text-xl font-semibold text-gray-800">{{ course.title }}</h3>
      <p class="text-sm text-gray-600 line-clamp-3">{{ course.description }}</p>

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
        v-if="course.categories?.length"
        class="flex flex-wrap gap-1"
      >
        <span
          v-for="cat in course.categories"
          :key="cat.id"
          class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full"
        >
          {{ cat.title }}
        </span>
      </div>

      <div class="text-sm text-gray-700">
        <strong>{{ $t("Language") }}:</strong> {{ course.courseLanguage }}
      </div>

      <div
        v-if="course.teachers?.length"
        class="text-sm text-gray-700"
      >
        <strong>{{ $t("Teachers") }}:</strong>
        {{ course.teachers.map((t) => t.user.fullName).join(", ") }}
      </div>

      <Rating
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

      <div class="mt-auto pt-2">
        <router-link
          v-if="course.visibility === 3 || (course.visibility === 2 && isUserInCourse)"
          :to="{ name: 'CourseHome', params: { id: course.id } }"
        >
          <Button
            :label="$t('Go to the course')"
            icon="pi pi-external-link"
            class="w-full"
          />
        </router-link>

        <Button
          v-else-if="course.visibility === 2 && course.subscribe && !isUserInCourse"
          :label="$t('Subscribe')"
          icon="pi pi-sign-in"
          class="w-full"
          @click="subscribeToCourse"
        />

        <Button
          v-else-if="course.visibility === 2 && !course.subscribe && !isUserInCourse"
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
</template>
<script setup>
import Rating from "primevue/rating"
import Button from "primevue/button"
import { computed, ref } from "vue"
import courseRelUserService from "../../services/courseRelUserService"
import { useRouter } from "vue-router"
import { useNotification } from "../../composables/notification"

const props = defineProps({
  course: Object,
  currentUserId: Number,
})

const emit = defineEmits(["rate", "subscribed"])

const router = useRouter()
const { showErrorNotification, showSuccessNotification } = useNotification()

const isUserInCourse = computed(() => {
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
  try {
    subscribing.value = true

    const response = await courseRelUserService.subscribe({
      userId: props.currentUserId,
      courseId: props.course.id,
    })

    emit("subscribed", { courseId: props.course.id, newUser: response })
    showSuccessNotification("You have successfully subscribed to this course.")
    router.push({ name: "CourseHome", params: { id: props.course.id } })
  } catch (e) {
    showErrorNotification("Failed to subscribe to the course.")
  } finally {
    subscribing.value = false
  }
}
</script>
