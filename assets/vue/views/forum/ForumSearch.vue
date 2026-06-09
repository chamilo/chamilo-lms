<template>
  <div>
    <SectionHeader :title="t('Search in the Forum')" />

    <BaseToolbar class="mb-4">
      <BaseButton
        :label="t('Back to forums')"
        :route="{ name: 'ForumList', params: { node: parentId }, query: route.query }"
        icon="back"
        only-icon
        size="small"
        type="plain"
      />
    </BaseToolbar>

    <form
      class="mb-4 rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
      novalidate
      @submit.prevent="performSearch"
    >
      <div class="flex flex-col gap-4 md:flex-row md:items-start">
        <div class="w-full md:max-w-xl">
          <BaseInputText
            id="forum-search-query"
            v-model="searchQuery"
            :error-text="t('Search term is too short')"
            :form-submitted="formSubmitted"
            :is-invalid="formSubmitted && !isSearchValid"
            :label="t('Search term')"
            class="w-full"
            name="forum_search_query"
          />
        </div>

        <div class="flex md:pt-2">
          <BaseButton
            :disabled="isLoading"
            :is-loading="isLoading"
            :is-submit="true"
            :label="t('Search')"
            icon="search"
            type="primary"
          />
        </div>
      </div>
    </form>

    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-4 text-sm text-gray-600"
    >
      {{ t("Loading") }}
    </div>

    <div
      v-else-if="hasSearched && !results.length"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600"
    >
      <BaseIcon
        class="mx-auto mb-2 text-gray-400"
        icon="search"
        size="big"
      />
      {{ t("No search results found") }}
    </div>

    <div
      v-else-if="results.length"
      class="flex flex-col gap-3"
    >
      <article
        v-for="result in results"
        :key="`${result.type}-${result.forumId || 0}-${result.threadId || 0}-${result.postId || 0}`"
        class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
      >
        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <BaseIcon
                :icon="getResultIcon(result)"
                size="normal"
              />
              <router-link
                :to="getResultRoute(result)"
                class="text-base font-semibold text-gray-90 hover:text-primary hover:underline"
              >
                {{ result.title }}
              </router-link>
              <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-700">
                {{ t(result.typeLabel || result.type) }}
              </span>
              <span
                v-if="result.hidden"
                class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-700"
              >
                {{ t("Hidden") }}
              </span>
              <span
                v-if="result.pending"
                class="rounded-full bg-yellow-100 px-2 py-0.5 text-xs text-yellow-700"
              >
                {{ t("Waiting for moderation") }}
              </span>
            </div>

            <div class="mt-2 flex flex-wrap gap-2 text-xs text-gray-500">
              <span v-if="result.forumTitle">{{ t("Forum") }}: {{ result.forumTitle }}</span>
              <span v-if="result.threadTitle && 'thread' !== result.type">{{ t("Thread") }}: {{ result.threadTitle }}</span>
              <span v-if="result.author">{{ t("By") }} {{ result.author }}</span>
              <span v-if="result.date">{{ formatDate(result.date) }}</span>
            </div>

            <p
              v-if="result.snippet"
              class="mt-3 text-sm leading-5 text-gray-700"
            >
              {{ result.snippet }}
            </p>
          </div>

          <BaseButton
            :label="t('Open')"
            :route="getResultRoute(result)"
            icon="arrow-right"
            only-icon
            size="small"
            type="primary-text"
          />
        </div>
      </article>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useNotification } from "../../composables/notification"
import forumService from "../../services/forumService"

const { t, d } = useI18n()
const route = useRoute()
const router = useRouter()
const notifications = useNotification()

const isLoading = ref(false)
const hasSearched = ref(false)
const formSubmitted = ref(false)
const searchQuery = ref(String(route.query.q || ""))
const results = ref([])
const minLength = ref(3)

const parentId = computed(() => Number(route.params.node || 0))
const cid = computed(() => Number(route.query.cid || 0))
const sid = computed(() => Number(route.query.sid || 0))
const gid = computed(() => Number(route.query.gid || 0))
const isSearchValid = computed(() => searchQuery.value.trim().length >= minLength.value)
const baseQuery = computed(() => ({
  "resourceNode.parent": parentId.value || null,
  cid: cid.value || null,
  sid: sid.value || null,
  gid: gid.value || null,
}))

function getResultIcon(result) {
  if ("forum" === result.type) {
    return "comment"
  }

  if ("thread" === result.type) {
    return "add-topic"
  }

  return "comment"
}

function getResultRoute(result) {
  if ("forum" === result.type) {
    return {
      name: "ForumThreadList",
      params: { node: parentId.value, forumId: result.forumId },
      query: route.query,
    }
  }

  return {
    name: "ForumPostList",
    params: { node: parentId.value, forumId: result.forumId, threadId: result.threadId },
    query: route.query,
    hash: result.postId ? `#post-${result.postId}` : "",
  }
}

function formatDate(value) {
  if (!value) {
    return ""
  }

  return d(new Date(value), "long")
}

async function search() {
  isLoading.value = true
  hasSearched.value = true

  try {
    const response = await forumService.searchForums({ ...baseQuery.value, q: searchQuery.value.trim() })
    minLength.value = Number(response.minLength || 3)
    results.value = response.items || []
  } catch (error) {
    console.error("Error searching forum:", error)
    notifications.showErrorNotification(t("Could not search forums"))
  } finally {
    isLoading.value = false
  }
}

async function performSearch() {
  formSubmitted.value = true

  if (!isSearchValid.value) {
    return
  }

  await router.replace({
    name: "ForumSearch",
    params: { node: parentId.value },
    query: { ...route.query, q: searchQuery.value.trim() },
  })
  await search()
}

onMounted(async () => {
  if (searchQuery.value.trim()) {
    formSubmitted.value = true
    if (isSearchValid.value) {
      await search()
    }
  }
})
</script>
