<template>
  <div>
    <div class="flex items-center justify-between">
      <BaseIcon
        icon="back"
        size="big"
        @click="goBack"
        :title="t('Back')"
      />
    </div>

    <hr />
    <h1 class="text-2xl font-bold">{{ t("Add Users") }} - {{ publicationTitle }}</h1>

    <div class="m-4">
      <h2 class="text-xl font-semibold mb-2">{{ t("Users added") }}</h2>
      <div
        v-if="isLoadingAdded"
        class="text-center text-gray-500 py-4"
      >
        <span class="animate-pulse"></span>
      </div>
      <div v-else-if="addedUsers.length">
        <div
          v-for="user in addedUsers"
          :key="user.iid"
          class="flex items-center justify-between bg-gray-100 p-2 rounded mb-2"
        >
          <span>{{ formatUser(user.user) }}</span>
          <BaseButton
            type="danger"
            size="small"
            :label="t('Delete')"
            icon="delete"
            @click="removeUser(user.iid)"
          />
        </div>
      </div>
      <p
        v-else
        class="text-gray-500"
      >
        {{ t("No users added yet.") }}
      </p>
    </div>

    <div>
      <h2 class="text-xl font-semibold mb-2">{{ t("Users to add") }}</h2>

      <div class="mb-2 flex items-center gap-2">
        <input
          v-model="search"
          type="text"
          :placeholder="t('Search users by username')"
          class="border p-1 rounded w-64"
          @input="debouncedSearch"
        />
      </div>
      <div
        v-if="isLoading"
        class="text-center text-gray-500 py-4"
      >
        <span class="animate-pulse"></span>
      </div>

      <div v-else-if="availableUsers.length">
        <div
          v-for="user in availableUsers"
          :key="user['@id']"
          class="flex items-center justify-between bg-white border p-2 rounded mb-2"
        >
          <span>{{ formatUser(user.user) }}</span>
          <BaseButton
            type="primary"
            size="small"
            :label="t('Add')"
            icon="plus-box"
            @click="addUser(extractIdFromIri(user.user['@id']))"
          />
        </div>

        <div class="flex justify-between items-center mt-4">
          <BaseButton
            :disabled="currentPage === 1"
            @click="prevPage"
            :label="t('Previous')"
          />
          <span>{{ t("Page") }} {{ currentPage }}</span>
          <BaseButton
            :disabled="!hasNextPage"
            @click="nextPage"
            :label="t('Next')"
          />
        </div>
      </div>
      <p
        v-else
        class="text-gray-500"
      >
        {{ t("No available users.") }}
      </p>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted, watch } from "vue"
import axios from "axios"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import { useNotification } from "../../composables/notification"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import { ENTRYPOINT } from "../../config/entrypoint"
import { useCidReq } from "../../composables/cidReq"
import debounce from "lodash/debounce"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"

const { t } = useI18n()
const route = useRoute()
const { cid, sid } = useCidReq()
const notification = useNotification()
const router = useRouter()

const publicationId = parseInt(route.params.id)
const publicationTitle = ref("")
const addedUsers = ref([])
const availableUsers = ref([])
const search = ref("")
const currentPage = ref(1)
const itemsPerPage = 20
const hasNextPage = ref(false)
const isLoading = ref(false)
const isLoadingAdded = ref(false)

function extractIdFromIri(iri) {
  return parseInt(iri?.split("/").pop())
}

function formatUser(user) {
  return `${user.fullName} (${user.username})`
}

function prevPage() {
  if (currentPage.value > 1) {
    currentPage.value--
    loadAvailableUsers()
  }
}

function nextPage() {
  currentPage.value++
  loadAvailableUsers()
}

async function loadPublication() {
  try {
    const response = await axios.get(`${ENTRYPOINT}c_student_publications/${publicationId}`, {
      params: { cid, ...(sid && { sid }) },
    })
    publicationTitle.value = response.data.title
  } catch (e) {
    console.error("Error loading publication", e)
  }
}

async function loadAddedUsers() {
  isLoadingAdded.value = true
  try {
    const response = await axios.get(`${ENTRYPOINT}c_student_publication_rel_users`, {
      params: {
        publication: `/api/c_student_publications/${publicationId}`,
      },
    })
    addedUsers.value = response.data["hydra:member"]
  } catch (e) {
    console.error("Error loading added users", e)
  } finally {
    isLoadingAdded.value = false
  }
}

async function loadAvailableUsers() {
  isLoading.value = true
  try {
    const params = {
      status: 5,
      page: currentPage.value,
      itemsPerPage,
      ...(search.value && { search: search.value }),
    }

    if (sid > 0) {
      params.session = sid
      params.course = cid
      const response = await axios.get(`${ENTRYPOINT}session_rel_course_rel_users`, { params })
      const currentUserIds = new Set(addedUsers.value.map((u) => u.user["@id"]))

      const userMap = new Map()
      for (const u of response.data["hydra:member"]) {
        const id = u.user["@id"]
        if (!currentUserIds.has(id) && !userMap.has(id)) {
          userMap.set(id, { user: u.user })
        }
      }

      availableUsers.value = Array.from(userMap.values())
      hasNextPage.value = !!response.data["hydra:view"]?.["hydra:next"]
    } else if (cid > 0) {
      params.course = cid
      const response = await axios.get(`${ENTRYPOINT}course_rel_users`, { params })
      const currentUserIds = new Set(addedUsers.value.map((u) => u.user["@id"]))

      const userMap = new Map()
      for (const u of response.data["hydra:member"]) {
        const id = u.user["@id"]
        if (!currentUserIds.has(id) && !userMap.has(id)) {
          userMap.set(id, u)
        }
      }

      availableUsers.value = Array.from(userMap.values())
      hasNextPage.value = !!response.data["hydra:view"]?.["hydra:next"]
    } else {
      availableUsers.value = []
      hasNextPage.value = false
    }
  } catch (e) {
    console.error("Error loading available users", e)
  } finally {
    isLoading.value = false
  }
}

const debouncedSearch = debounce(() => {
  currentPage.value = 1
  loadAvailableUsers()
}, 500)

async function addUser(userId) {
  try {
    await axios.post(`${ENTRYPOINT}c_student_publication_rel_users`, {
      publication: `/api/c_student_publications/${publicationId}`,
      user: `/api/users/${userId}`,
    })
    notification.showSuccessNotification(t("User added"))
    await loadAddedUsers()
    await loadAvailableUsers()
  } catch (e) {
    notification.showErrorNotification(t("Error adding user"))
  }
}

async function removeUser(relId) {
  try {
    await axios.delete(`${ENTRYPOINT}c_student_publication_rel_users/${relId}`)
    notification.showSuccessNotification(t("User removed"))
    await loadAddedUsers()
    await loadAvailableUsers()
  } catch (e) {
    notification.showErrorNotification(t("Error removing user"))
  }
}

function goBack() {
  router.push({
    name: "AssignmentDetail",
    params: { id: publicationId },
    query: route.query,
  })
}

onMounted(() => {
  loadPublication()
  loadAddedUsers()
  loadAvailableUsers()
})
</script>
