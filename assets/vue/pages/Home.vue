<template>
  <div class="flex flex-col gap-4 items-center">
    <SystemAnnouncementCardList />
    <PageCardList class="grid gap-4 grid-cols-1" />

    <div
      v-if="showCatalogue && visibleCourses.length"
      class="w-full mt-8"
    >
      <h2 class="text-xl font-semibold mb-4">
        {{ $t("Featured courses") }}
      </h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <CatalogueCourseCard
          v-for="course in visibleCourses"
          :key="course.id"
          :course="course"
          :show-title="true"
          :current-user-id="currentUserId"
          :card-extra-fields="[]"
          @subscribed="onUserSubscribed"
        />
      </div>

      <div
        class="mt-6 text-center"
        v-if="visibleCourses.length < allCourses.length"
      >
        <Button
          :label="$t('Show more courses')"
          icon="pi pi-angle-down"
          class="p-button-outlined"
          @click="loadMore"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue"
import { useRouter } from "vue-router"
import { usePlatformConfig } from "../store/platformConfig"
import courseService from "../services/courseService"
import CatalogueCourseCard from "../components/course/CatalogueCourseCard.vue"
import SystemAnnouncementCardList from "../components/systemannouncement/SystemAnnouncementCardList.vue"
import PageCardList from "../components/page/PageCardList.vue"
import Button from "primevue/button"
import { useSecurityStore } from "../store/securityStore"
import * as userRelCourseVoteService from "../services/userRelCourseVoteService"

const router = useRouter()
const platformConfigStore = usePlatformConfig()
const redirectValue = platformConfigStore.getSetting("workflows.redirect_index_to_url_for_logged_users")

if (typeof redirectValue === "string" && redirectValue.trim() !== "") {
  router.replace(`/${redirectValue}`)
}

const showCatalogue = computed(
  () => platformConfigStore.getSetting("catalog.course_catalog_display_in_home") === "true",
)
const allCourses = ref([])
const visibleCourses = ref([])
const pageSize = 8
const securityStore = useSecurityStore()
const currentUserId = securityStore.user?.id ?? null

const loadMore = () => {
  const nextItems = allCourses.value.slice(visibleCourses.value.length, visibleCourses.value.length + pageSize)
  visibleCourses.value.push(...nextItems)
}

const onUserSubscribed = ({ courseId, newUser }) => {
  const course = allCourses.value.find((c) => c.id === courseId)
  if (course) {
    course.users.push(newUser)
  }
}

onMounted(async () => {
  if (showCatalogue.value) {
    try {
      const loaded = await courseService.loadCourseCatalogue()
      allCourses.value = loaded.items
      visibleCourses.value = loaded.items.slice(0, pageSize)

      if (currentUserId) {
        const votes = await userRelCourseVoteService.getUserVotes({
          userId: currentUserId,
          urlId: window.access_url_id,
        })

        for (const vote of votes) {
          let courseId
          if (typeof vote.course === "object" && vote.course !== null) {
            courseId = vote.course.id
          } else if (typeof vote.course === "string") {
            courseId = parseInt(vote.course.split("/").pop())
          }

          const course = allCourses.value.find((c) => c.id === courseId)
          if (course) {
            course.userVote = vote
          }
        }
      }
    } catch (e) {
      console.warn("Catalogue load failed:", e)
    }
  }
})
</script>
