<template>
  <section class="space-y-6">
    <BaseToolbar class="border-b border-gray-25 bg-white">
      <template #start>
        <BaseButton
          icon="home"
          :label="t('Home')"
          only-icon
          size="large"
          type="primary-text"
          class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
          :route="getWikiRoute('index')"
        />
      </template>

      <template #end>
        <BaseButton
          v-if="wikiPage.legacyUrl"
          icon="link-external"
          :label="t('Wiki')"
          only-icon
          size="large"
          type="primary-text"
          class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
          :to-url="wikiPage.legacyUrl"
        />
      </template>
    </BaseToolbar>

    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600 shadow-sm"
      role="status"
    >
      {{ t("Loading...") }}
    </div>

    <div
      v-else-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      role="alert"
    >
      {{ errorMessage }}
    </div>

    <template v-else>
      <BaseCard>
        <template #title>
          <div class="flex min-w-0 items-center gap-2">
            <BaseIcon
              v-if="Number(wikiPage.assignment) === 1"
              icon="human-male-board"
              size="small"
              :tooltip="t('Assignment')"
            />
            <BaseIcon
              v-else-if="Number(wikiPage.assignment) === 2"
              icon="account"
              size="small"
              :tooltip="t('Learner')"
            />
            <BaseIcon
              v-if="wikiPage.hasTask"
              icon="file-text"
              size="small"
              :tooltip="t('Task')"
            />
            <h1 class="min-w-0 flex-1 break-words text-xl font-semibold text-gray-90">
              {{ wikiPage.title || t("Wiki") }}
            </h1>
            <BaseIcon
              v-if="wikiPage.editLocked"
              icon="lock"
              size="small"
              :tooltip="t('Locked')"
            />
            <BaseIcon
              v-if="wikiPage.exists && !wikiPage.visible"
              icon="eye-off"
              size="small"
              :tooltip="t('Invisible')"
            />
          </div>
        </template>

        <div
          v-if="wikiPage.exists && wikiPage.content"
          class="break-words text-gray-90 [&_a]:font-medium [&_img]:max-w-full [&_table]:max-w-full"
          @click="handleContentClick"
          v-html="wikiPage.content"
        ></div>

        <div
          v-else
          class="py-8 text-center"
        >
          <BaseIcon
            class="mb-3 text-gray-500"
            icon="information"
            size="big"
          />
          <p class="text-sm italic text-gray-500">
            {{ t("No content") }}
          </p>
        </div>

        <template #footer>
          <div
            v-if="wikiPage.exists"
            class="flex flex-wrap items-center gap-x-6 gap-y-2 border-t border-gray-20 pt-4 text-sm text-gray-600"
          >
            <span>{{ t("Progress") }}: {{ wikiPage.progress }}%</span>
            <span>{{ t("Rating") }}: {{ wikiPage.score ?? 0 }}</span>
            <span>{{ t("Words") }}: {{ wikiPage.wordCount }}</span>
            <span v-if="wikiPage.version">{{ t("Version") }}: {{ wikiPage.version }}</span>
            <span v-if="wikiPage.authorName">{{ t("Author") }}: {{ wikiPage.authorName }}</span>
            <span v-if="formattedUpdatedAt">{{ t("Updated at") }}: {{ formattedUpdatedAt }}</span>
          </div>
        </template>
      </BaseCard>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import wikiService from "../../services/wikiService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const isLoading = ref(false)
const errorMessage = ref("")
const wikiPage = reactive(createEmptyPage())

const formattedUpdatedAt = computed(() => {
  if (!wikiPage.updatedAt) {
    return ""
  }

  const date = new Date(wikiPage.updatedAt)

  return Number.isNaN(date.getTime()) ? "" : date.toLocaleString()
})

function createEmptyPage() {
  return {
    reflink: "index",
    exists: false,
    title: "",
    content: "",
    assignment: 0,
    hasTask: false,
    progress: 0,
    score: null,
    wordCount: 0,
    version: null,
    authorName: "",
    updatedAt: null,
    visible: true,
    editLocked: false,
    isInheritedFromCourse: false,
    legacyUrl: "",
  }
}

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function getContextParams() {
  const params = {
    cid: Number(getQueryValue(route.query.cid) || 0),
    node: Number(route.params.node || 0),
    title: String(getQueryValue(route.query.title) || "index"),
  }
  const sid = Number(getQueryValue(route.query.sid) || 0)
  const gid = Number(getQueryValue(route.query.gid) || 0)

  if (sid > 0) {
    params.sid = sid
  }

  if (gid > 0) {
    params.gid = gid
  }

  if (Object.prototype.hasOwnProperty.call(route.query, "isStudentView")) {
    params.isStudentView = getQueryValue(route.query.isStudentView)
  }

  return params
}

function getWikiRoute(reflink) {
  const query = {
    cid: getQueryValue(route.query.cid),
    title: reflink,
  }
  const sid = Number(getQueryValue(route.query.sid) || 0)
  const gid = Number(getQueryValue(route.query.gid) || 0)

  if (sid > 0) {
    query.sid = sid
  }

  if (gid > 0) {
    query.gid = gid
  }

  if (Object.prototype.hasOwnProperty.call(route.query, "isStudentView")) {
    query.isStudentView = getQueryValue(route.query.isStudentView)
  }

  return {
    name: "WikiPage",
    params: { node: route.params.node },
    query,
  }
}

function handleContentClick(event) {
  const target = event.target instanceof Element ? event.target.closest("a[data-wiki-reflink]") : null

  if (!(target instanceof HTMLAnchorElement)) {
    return
  }

  const reflink = target.dataset.wikiReflink

  if (!reflink) {
    return
  }

  event.preventDefault()
  router.push(getWikiRoute(reflink))
}

async function loadPage() {
  isLoading.value = true
  errorMessage.value = ""

  try {
    const response = await wikiService.getPage(getContextParams())
    Object.assign(wikiPage, createEmptyPage(), response)
  } catch (error) {
    console.error("Error loading Wiki page", error)
    errorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    isLoading.value = false
  }
}

onMounted(loadPage)

watch(
  () => [
    route.params.node,
    route.query.cid,
    route.query.sid,
    route.query.gid,
    route.query.title,
    route.query.isStudentView,
  ],
  loadPage,
)
</script>
