<template>
  <div class="flex w-full flex-col gap-6">
    <SectionHeader
      :show-student-view-button="collection.canManage"
      :title="t('AI Toolbox')"
    >
      <BaseButton
        v-if="collection.canCreate"
        :label="t('Create')"
        :route="createRoute"
        icon="plus"
        only-icon
        type="primary"
      />
    </SectionHeader>

    <div
      v-if="loading"
      class="grid grid-cols-1 gap-4 lg:grid-cols-2"
    >
      <div
        v-for="index in 2"
        :key="index"
        class="animate-pulse rounded-2xl border border-gray-25 bg-white p-5"
      >
        <div class="mb-4 h-6 w-2/3 rounded bg-gray-15" />
        <div class="mb-2 h-4 rounded bg-gray-15" />
        <div class="h-4 w-3/4 rounded bg-gray-15" />
      </div>
    </div>

    <div
      v-else-if="loadError"
      class="rounded-lg border border-danger/30 bg-danger/10 p-4 text-danger"
    >
      {{ t("An error occurred") }}
    </div>

    <div
      v-else-if="!collection.enabled"
      class="rounded-lg border border-warning/30 bg-warning/10 p-4 text-warning-dark"
    >
      {{ t("AI Toolbox") }}: {{ t("Disabled") }}
    </div>

    <EmptyState
      v-else-if="collection.items.length === 0"
      :detail="collection.canManage ? t('Generate interactive educational applications with AI and track learner progress with SCORM.') : t('No published applications are available yet.')"
      :summary="t('No results found')"
      icon="robot"
    >
      <BaseButton
        v-if="collection.canCreate"
        :label="t('Create')"
        class="mt-4"
        icon="plus"
        :route="createRoute"
        type="primary"
      />
    </EmptyState>

    <template v-else>
      <div class="rounded-2xl border border-gray-25 bg-white p-5">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 class="text-lg font-semibold text-gray-90">{{ t("Interactive applications") }}</h2>
            <p class="mt-1 text-sm text-gray-50">
              {{ collection.canManage ? t("Generate, improve, publish and review versions from one place.") : t("Open an application to continue from your latest progress.") }}
            </p>
          </div>
          <div class="inline-flex w-fit items-center gap-2 rounded-full border border-gray-25 bg-gray-15 px-3 py-1 text-xs text-gray-60">
            <BaseIcon icon="robot" />
            <span>{{ collection.totalItems }}</span>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
        <article
          v-for="item in collection.items"
          :key="item.id"
          class="group flex min-h-56 flex-col overflow-hidden rounded-2xl border border-gray-25 bg-white"
        >
          <div class="flex flex-1 flex-col gap-4 p-5">
            <div class="flex items-start justify-between gap-4">
              <div class="flex min-w-0 items-start gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-primary/20 bg-primary/10 text-primary">
                  <BaseIcon icon="robot" />
                </div>
                <div class="min-w-0">
                  <h2 class="truncate text-lg font-semibold text-gray-90">{{ item.title }}</h2>
                  <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-50">
                    <span>{{ t("Version") }} {{ item.currentVersion }}</span>
                    <span>·</span>
                    <span>{{ item.versionCount }} {{ t("Versions") }}</span>
                  </div>
                </div>
              </div>

              <button
                v-if="collection.canManage"
                type="button"
                class="inline-flex shrink-0 items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold transition-opacity disabled:cursor-not-allowed disabled:opacity-60"
                :class="item.visible ? 'bg-success/10 text-success' : 'bg-gray-15 text-gray-60'"
                :disabled="visibilityBusy === item.id || (!item.launchUrl && !item.visible)"
                :title="item.visible ? t('Hide') : t('Publish')"
                :aria-label="item.visible ? t('Hide') : t('Publish')"
                :aria-pressed="item.visible"
                @click.stop="toggleVisibility(item)"
              >
                <BaseIcon :icon="item.visible ? 'eye-on' : 'eye-off'" />
                {{ item.visible ? t("Published") : t("Draft") }}
              </button>
            </div>

            <p class="text-sm leading-6 text-gray-60">
              {{ item.description || t("Interactive learning application") }}
            </p>

            <div v-if="!collection.canManage" class="mt-auto grid grid-cols-1 gap-3 sm:grid-cols-2">
              <div class="rounded-xl border border-gray-20 bg-gray-10 p-3">
                <div class="mb-2 flex items-center justify-between text-xs text-gray-50">
                  <span>{{ t("Progress") }}</span>
                  <strong class="text-gray-80">{{ item.progress }}%</strong>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-gray-15">
                  <div
                    class="h-full rounded-full bg-primary transition-all"
                    :style="{ width: `${Math.max(0, Math.min(100, Number(item.progress || 0)))}%` }"
                  />
                </div>
              </div>
              <div class="rounded-xl border border-gray-20 bg-gray-10 p-3">
                <div class="text-xs text-gray-50">{{ t("Score") }}</div>
                <div class="mt-1 text-lg font-semibold text-gray-90">{{ formatScore(item.score) }}</div>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-between gap-3 border-t border-gray-20 bg-gray-10 px-5 py-3">
            <span class="text-xs text-gray-50">{{ formatDate(item.updatedAt || item.createdAt) }}</span>

            <div class="flex items-center gap-2">
              <BaseButton
                v-if="item.canEdit && item.reportingUrl"
                :label="t('Reporting')"
                icon="tracking"
                only-icon
                type="black"
                @click="openUrl(item.reportingUrl)"
              />
              <BaseButton
                v-if="item.canEdit"
                :label="t('Settings')"
                :route="detailRoute(item.id)"
                icon="settings"
                only-icon
                type="black"
              />
              <BaseButton
                v-if="!collection.canManage && item.canRestart"
                :is-loading="attemptBusy === item.id"
                :label="t('Try again')"
                icon="restart"
                only-icon
                type="black"
                @click="restartAttempt(item)"
              />
              <BaseButton
                v-if="item.canEdit ? item.previewUrl : item.launchUrl"
                :label="item.canEdit ? t('Preview') : t('Open')"
                :icon="item.canEdit ? 'preview' : 'play-box-outline'"
                :only-icon="item.canEdit"
                type="primary"
                @click="openUrl(item.canEdit ? item.previewUrl : item.launchUrl)"
              />
            </div>
          </div>
        </article>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue"
import { storeToRefs } from "pinia"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import { useCidReqStore } from "../../store/cidReq"
import toolboxService from "../../services/toolboxService"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import EmptyState from "../../components/EmptyState.vue"
import { useNotification } from "../../composables/notification"

const { t } = useI18n()
const route = useRoute()
const { course, session } = storeToRefs(useCidReqStore())
const { showSuccessNotification, showErrorNotification } = useNotification()

const loading = ref(true)
const loadError = ref(false)
const visibilityBusy = ref(0)
const attemptBusy = ref(0)
const collection = reactive({
  enabled: false,
  canCreate: false,
  canManage: false,
  providers: [],
  totalItems: 0,
  items: [],
})

const context = computed(() => ({
  cid: course.value?.id ?? Number(route.query.cid ?? 0),
  sid: session.value?.id ?? Number(route.query.sid ?? 0),
  gid: Number(route.query.gid ?? 0),
}))

const createRoute = computed(() => ({
  name: "ToolboxCreate",
  params: { node: route.params.node },
  query: route.query,
}))

function detailRoute(itemId) {
  return { name: "ToolboxDetail", params: { node: route.params.node, itemId }, query: route.query }
}

onMounted(load)

async function load() {
  loading.value = true
  loadError.value = false
  try {
    const data = await toolboxService.getItems(context.value)
    collection.enabled = data?.enabled === true
    collection.canCreate = data?.canCreate === true
    collection.canManage = data?.canManage === true
    collection.providers = Array.isArray(data?.providers) ? data.providers : []
    collection.totalItems = Number(data?.totalItems || 0)
    collection.items = Array.isArray(data?.items) ? data.items : []
  } catch (error) {
    loadError.value = true
    showErrorNotification(error)
  } finally {
    loading.value = false
  }
}

async function toggleVisibility(item) {
  if (!item?.id) return

  const nextVisible = !item.visible
  visibilityBusy.value = Number(item.id)
  try {
    await toolboxService.setVisibility(item.id, context.value, nextVisible)
    showSuccessNotification(nextVisible ? t("Published") : t("Hidden"))
    await load()
  } catch (error) {
    showErrorNotification(error)
  } finally {
    visibilityBusy.value = 0
  }
}

async function restartAttempt(item) {
  if (!item?.id || !item?.learningPathId || !item?.launchUrl) return

  attemptBusy.value = Number(item.id)
  try {
    await toolboxService.restartAttempt(item.learningPathId, context.value)
    window.location.assign(item.launchUrl)
  } catch (error) {
    showErrorNotification(error)
  } finally {
    attemptBusy.value = 0
  }
}

function openUrl(url) {
  if (url) window.location.assign(url)
}

function formatScore(value) {
  if (value === null || value === undefined || Number.isNaN(Number(value))) {
    return "-"
  }

  const score = Number(value)
  return `${score.toFixed(Number.isInteger(score) ? 0 : 1)}%`
}

function formatDate(value) {
  if (!value) return ""
  try {
    return new Intl.DateTimeFormat(undefined, { dateStyle: "medium" }).format(new Date(value))
  } catch {
    return value
  }
}
</script>
