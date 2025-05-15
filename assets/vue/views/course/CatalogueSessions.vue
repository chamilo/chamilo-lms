<template>
  <div class="catalogue-sessions p-4">
    <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
      <div>
        <strong>{{ $t("Total number of sessions") }}:</strong>
        {{ sessions?.length || 0 }}<br />
        <strong>{{ $t("Matching sessions") }}:</strong>
        {{ filteredSessions.length }}
      </div>
      <div class="flex gap-3">
        <Button
          :label="$t('Clear filter results')"
          class="p-button-outlined"
          icon="pi pi-filter-slash"
          @click="clearFilter()"
        />
        <span class="p-input-icon-left">
          <i class="pi pi-search" />
          <InputText
            v-model="filters['global'].value"
            :placeholder="$t('Search')"
            class="w-64"
          />
        </span>
      </div>
    </div>

    <div
      v-if="status"
      class="text-center text-gray-500 py-6"
    >
      {{ $t("Loading sessions. Please wait.") }}
    </div>

    <div
      v-else-if="!filteredSessions.length"
      class="text-center text-gray-500 py-6"
    >
      {{ $t("No session available") }}
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 px-4">
      <CatalogueSessionCard
        v-for="session in visibleSessions"
        :key="session.id"
        :session="session"
        @rate="onRatingChange"
        @subscribed="onSessionSubscribed"
      />
    </div>

    <div
      v-if="loadingMore"
      class="text-center text-gray-400 py-4"
    >
      {{ $t("Loading more sessions...") }}
    </div>
  </div>
</template>
<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from "vue"
import InputText from "primevue/inputtext"
import Button from "primevue/button"
import { FilterMatchMode } from "primevue/api"
import axios from "axios"
import CatalogueSessionCard from "../../components/session/CatalogueSessionCard.vue"
import { useSecurityStore } from "../../store/securityStore"
import * as userRelCourseVoteService from "../../services/userRelCourseVoteService"
import { useRouter } from "vue-router"

const router = useRouter()
const securityStore = useSecurityStore()

if (!securityStore.user?.id) {
  router.push({ name: "Login" })
  throw new Error("No active session. Redirecting to login.")
}

const currentUserId = securityStore.user.id
const urlId = window.access_url_id

const status = ref(false)
const sessions = ref([])
const filters = ref({ global: { value: null, matchMode: FilterMatchMode.CONTAINS } })

const rowsPerScroll = 9
const visibleCount = ref(rowsPerScroll)
const loadingMore = ref(false)

const saveOrUpdateVote = async (session, value) => {
  try {
    const sessionId = session.id
    const allVotes = await userRelCourseVoteService.getUserVotes({
      userId: currentUserId,
      urlId,
    })

    const existingVote = allVotes.find(
      (v) =>
        v.session &&
        parseInt(v.session.split("/").pop()) === sessionId &&
        (v.course === null || v.course === undefined),
    )

    if (existingVote?.["@id"]) {
      const updated = await userRelCourseVoteService.updateVote({
        iri: existingVote["@id"],
        vote: value,
        sessionId,
        urlId,
      })
      session.userVote = { ...existingVote, vote: updated.vote }
    } else {
      session.userVote = await userRelCourseVoteService.saveVote({
        courseIri: null,
        userId: currentUserId,
        vote: value,
        sessionId,
        urlId,
      })
    }
  } catch (e) {
    console.error("Error saving/updating vote:", e)
  }
}

const onRatingChange = ({ value, session }) => {
  if (value > 0) {
    saveOrUpdateVote(session, value)
  }
}

const onSessionSubscribed = (sessionId) => {
  const session = sessions.value.find((s) => s.id === sessionId)
  if (session) {
    session.isSubscribed = true
  }
}

const load = async () => {
  status.value = true
  try {
    const response = await axios.get("/catalogue/sessions-list")
    sessions.value = response.data.map((s) => ({
      ...s,
      userVote: null,
    }))

    const votes = await userRelCourseVoteService.getUserVotes({
      userId: currentUserId,
      urlId,
    })

    for (const vote of votes) {
      const sessionId = vote.session?.id ?? parseInt(vote.session?.split("/")?.pop())
      const session = sessions.value.find((s) => s.id === sessionId)
      if (session) {
        session.userVote = vote
      }
    }

    const sessionSubs = await axios.get(`/api/session_rel_users?user=${currentUserId}`)

    for (const sub of sessionSubs.data["hydra:member"]) {
      const sessionId = sub.session?.id ?? parseInt(sub.session?.split("/")?.pop())
      const session = sessions.value.find((s) => s.id === sessionId)
      if (session) {
        session.isSubscribed = true
      }
    }
  } catch (error) {
    console.log(error)
  } finally {
    status.value = false
  }
}

onMounted(() => {
  window.addEventListener("scroll", handleScroll)
  load()
})

onUnmounted(() => {
  window.removeEventListener("scroll", handleScroll)
})

const clearFilter = () => {
  filters.value.global.value = null
  visibleCount.value = rowsPerScroll
}

const filteredSessions = computed(() => {
  const keyword = filters.value.global.value?.toLowerCase()
  if (!keyword) return sessions.value
  return sessions.value.filter((session) => {
    return (
      session.title?.toLowerCase().includes(keyword) ||
      session.description?.toLowerCase().includes(keyword) ||
      session.category?.title?.toLowerCase().includes(keyword) ||
      session.courses?.some((sc) => sc.courseLanguage?.toLowerCase().includes(keyword))
    )
  })
})

const visibleSessions = computed(() => {
  return filteredSessions.value.slice(0, visibleCount.value)
})

const handleScroll = () => {
  if (loadingMore.value) return

  const threshold = 150
  const scrollTop = window.scrollY
  const viewportHeight = window.innerHeight
  const fullHeight = document.documentElement.scrollHeight

  if (scrollTop + viewportHeight + threshold >= fullHeight) {
    if (visibleCount.value < filteredSessions.value.length) {
      loadingMore.value = true
      setTimeout(() => {
        visibleCount.value += rowsPerScroll
        loadingMore.value = false
      }, 400)
    }
  }
}

watch(
  () => filters.value.global.value,
  () => {
    visibleCount.value = rowsPerScroll
  },
)
</script>
<style scoped>
.catalogue-sessions {
  width: 100%;
}
</style>
