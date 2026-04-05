<template>
  <BaseCard
    plain
    class="overflow-hidden bg-white"
  >
    <template #header>
      <div class="border-b border-gray-25 bg-gray-15 px-4 py-3">
        <h2 class="text-xl font-semibold text-gray-90">{{ t("My communities") }}</h2>
      </div>
    </template>

    <div class="space-y-4 px-4 py-4">
      <div
        v-if="!isValidGlobalForumsCourse && isCurrentUser"
        class="flex items-center"
      >
        <input
          v-model="searchQuery"
          :placeholder="t('Search')"
          type="search"
          class="h-11 min-w-0 flex-grow rounded-l-xl border-gray-25 bg-white px-3 text-body-2 text-gray-90 placeholder:text-gray-50 focus:border-primary focus:ring-primary"
          @keyup.enter="search"
        />
        <button
          type="button"
          class="flex h-11 w-11 items-center justify-center rounded-r-xl border border-l-0 border-gray-25 bg-gray-15 text-gray-90 transition hover:bg-gray-20"
          @click="search"
        >
          <i
            class="mdi mdi-magnify text-lg"
            aria-hidden="true"
          ></i>
        </button>
      </div>

      <div
        v-if="isValidGlobalForumsCourse"
        class="text-center"
      >
        <a
          :href="goToUrl"
          class="inline-flex w-full items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-body-2 font-semibold text-primary-button-text transition hover:opacity-90"
        >
          {{ t("See all communities") }}
        </a>
      </div>

      <ul
        v-if="groups.length > 0"
        class="space-y-2"
      >
        <li
          v-for="group in groups"
          :key="group.id"
        >
          <a
            v-if="group.url"
            :href="group.url"
            class="group flex items-center gap-3 rounded-2xl border border-gray-25 bg-white px-3 py-3 transition hover:bg-support-2"
          >
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gray-15 text-primary">
              <i
                class="mdi mdi-account-group-outline text-xl"
                aria-hidden="true"
              ></i>
            </span>

            <span class="min-w-0 flex-1 truncate text-body-2 font-medium text-gray-90">
              {{ group.name }}
            </span>

            <i
              class="mdi mdi-chevron-right text-gray-50 transition group-hover:text-gray-90"
              aria-hidden="true"
            ></i>
          </a>

          <div
            v-else
            class="flex items-center gap-3 rounded-2xl border border-gray-25 bg-gray-15 px-3 py-3"
          >
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-primary">
              <i
                class="mdi mdi-account-group-outline text-xl"
                aria-hidden="true"
              ></i>
            </span>

            <span class="min-w-0 flex-1 truncate text-body-2 font-medium text-gray-90">
              {{ group.name }}
            </span>
          </div>
        </li>
      </ul>

      <div
        v-else
        class="rounded-2xl border border-dashed border-gray-25 bg-gray-15 px-4 py-6 text-center"
      >
        <i
          class="mdi mdi-account-group-outline text-3xl text-gray-50"
          aria-hidden="true"
        ></i>
        <p class="mt-2 text-body-2 text-gray-50">{{ t("No communities") }}</p>
      </div>
    </div>
  </BaseCard>
</template>

<script setup>
import BaseCard from "../basecomponents/BaseCard.vue"
import { useI18n } from "vue-i18n"
import { computed, inject, ref, watchEffect } from "vue"
import axios from "axios"
import { usePlatformConfig } from "../../store/platformConfig"
import { useRouter } from "vue-router"

const { t } = useI18n()
const searchQuery = ref("")
const groups = ref([])
const goToUrl = ref("")
const user = inject("social-user")
const isCurrentUser = inject("is-current-user")
const platformConfigStore = usePlatformConfig()
const globalForumsCourse = computed(() => platformConfigStore.getSetting("forum.global_forums_course_id"))
const router = useRouter()

const isValidGlobalForumsCourse = computed(() => {
  const courseId = globalForumsCourse.value
  return courseId !== null && courseId !== undefined && courseId > 0
})

function search() {
  router.push({ name: "UserGroupSearch", query: { q: searchQuery.value } })
}

async function fetchGroups(userId) {
  try {
    const response = await axios.get(`/social-network/groups/${userId}`)

    if (response.data) {
      groups.value = response.data.items || []
      goToUrl.value = response.data.go_to || ""
    }
  } catch (error) {
    groups.value = []
    goToUrl.value = ""
  }
}

watchEffect(() => {
  if (user.value && user.value.id) {
    fetchGroups(user.value.id)
  }
})
</script>
