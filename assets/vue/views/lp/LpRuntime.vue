<template>
  <div
    id="learning_path_main"
    :class="[
      'lp-runtime-player',
      {
        'lp-view-collapsed': tocCollapsed,
        'lp-toc-hidden': runtime?.hideToc,
        'lp-runtime-impress-mode': isImpressMode,
      },
    ]"
  >
    <div
      v-if="isLoading && !runtime"
      class="lp-runtime-screen-state"
    >
      {{ t("Loading") }}…
    </div>

    <div
      v-else-if="errorMessage"
      class="lp-runtime-screen-state lp-runtime-screen-state--error"
    >
      {{ errorMessage }}
    </div>

    <template v-else-if="runtime">
      <nav
        v-if="!isImpressMode"
        :class="[
          'lp-runtime-menu',
          `lp-runtime-menu--${runtime.menuLocation || 'left'}`,
          { 'lp-runtime-menu--middle': runtime.navigationInTheMiddle },
        ]"
      >
        <BaseButton
          id="lp-runtime-menu-toggle"
          :label="t('Options')"
          icon="menu"
          only-icon
          type="plain"
          @click="menuOpen = !menuOpen"
        />

        <div
          v-show="menuOpen"
          class="lp-runtime-menu-panel"
        >
          <a
            v-if="runtime.showHome"
            :href="runtime.homeUrl"
            class="lp-runtime-menu-link"
          >
            <BaseIcon
              icon="home"
              size="small"
            />
            <span>{{ homeLabel }}</span>
          </a>

          <a
            v-if="runtime.canEdit && runtime.showReporting"
            :href="runtime.reportingUrl"
            class="lp-runtime-menu-link"
          >
            <BaseIcon
              icon="tracking"
              size="small"
            />
            <span>{{ t("Reporting") }}</span>
          </a>

          <router-link
            v-if="runtime.canEdit"
            :to="builderRoute"
            class="lp-runtime-menu-link"
          >
            <BaseIcon
              icon="edit"
              size="small"
            />
            <span>{{ t("Edit") }}</span>
          </router-link>

          <router-link
            v-if="runtime.canEdit"
            :to="settingsRoute"
            class="lp-runtime-menu-link"
          >
            <BaseIcon
              icon="settings"
              size="small"
            />
            <span>{{ t("Settings") }}</span>
          </router-link>
        </div>
      </nav>

      <aside
        v-if="!runtime.hideToc && !isImpressMode"
        id="learning_path_left_zone"
        :aria-hidden="tocCollapsed ? 'true' : 'false'"
        class="lp-runtime-sidebar"
      >
        <div class="lp-runtime-sidebar-inner">
          <div class="lp-runtime-info">
            <img
              v-if="shouldShowPreviewImage"
              :alt="runtime.title"
              :src="previewImageUrl"
              class="lp-runtime-preview"
              @error="handlePreviewImageError"
            />
            <div
              v-else
              :aria-label="runtime.title"
              class="lp-runtime-preview lp-runtime-preview-fallback"
              role="img"
            >
              <BaseIcon
                icon="learning-paths"
                size="large"
              />
            </div>

            <div
              v-if="runtime.author"
              class="lp-runtime-author"
            >
              {{ runtime.author }}
            </div>

            <progress
              :value="progressValue"
              class="lp-runtime-progress-track"
              max="100"
            />
            <div class="lp-runtime-progress-label">
              {{ progressValue }}% {{ t("Complete") }}
            </div>

            <div
              v-if="runtime.minimumTime > 0"
              class="lp-runtime-time"
            >
              <span>{{ t("Duration") }}</span>
              <strong>{{ formattedTotalTime }} / {{ formattedMinimumTime }}</strong>
            </div>
          </div>

          <div
            id="toc_id"
            class="lp-runtime-toc"
          >
            <div class="lp-runtime-toc-title">
              {{ runtime.title }}
            </div>

            <template
              v-for="item in visibleItems"
              :key="item.id"
            >
              <button
                v-if="item.isSection"
                :class="['lp-runtime-section', itemLevelClass(item.level)]"
                :disabled="!runtime.accordionToc || !item.hasChildren"
                type="button"
                @click="toggleSection(item.id)"
              >
                <span class="lp-runtime-item-main">
                  <BaseIcon
                    icon="folder-generic"
                    size="small"
                  />
                  <span class="lp-runtime-item-title">{{ item.title }}</span>
                </span>

                <BaseIcon
                  v-if="runtime.accordionToc && item.hasChildren"
                  :icon="sectionExpanded(item.id) ? 'arrow-up' : 'arrow-down'"
                  class="lp-runtime-section-chevron"
                  size="small"
                />
              </button>

              <button
                v-else
                :aria-current="Number(runtime.currentItemId) === Number(item.id) ? 'step' : undefined"
                :class="[
                  'lp-runtime-item',
                  itemLevelClass(item.level),
                  {
                    'lp-runtime-item--active': Number(runtime.currentItemId) === Number(item.id),
                    'lp-runtime-item--disabled': !item.available,
                  },
                ]"
                :disabled="!item.available || isChangingItem"
                type="button"
                @click="openItem(item.id)"
              >
                <span class="lp-runtime-item-main">
                  <BaseIcon
                    :icon="itemIcon(item.itemType)"
                    class="lp-runtime-item-type"
                    size="small"
                  />
                  <span class="lp-runtime-item-title">{{ item.title }}</span>
                </span>

                <BaseIcon
                  v-if="statusIcon(item)"
                  :icon="statusIcon(item)"
                  :class="statusClass(item)"
                  size="small"
                />
                <span
                  v-else
                  class="lp-runtime-status-placeholder"
                />
              </button>
            </template>
          </div>
        </div>

      </aside>

      <div
        v-if="!runtime.hideToc && !isImpressMode"
        :class="['lp-runtime-collapse', { 'lp-runtime-collapse--closed': tocCollapsed }]"
      >
        <BaseButton
          id="lp-runtime-collapse-button"
          :icon="tocCollapsed ? 'arrow-right' : 'arrow-left'"
          :label="tocCollapsed ? t('Expand') : t('Collapse')"
          only-icon
          type="plain"
          @click="toggleToc"
        />
      </div>

      <main
        id="learning_path_right_zone"
        :class="['lp-runtime-content', { 'lp-runtime-content--impress': isImpressMode }]"
      >
        <div
          v-if="!isImpressMode && !runtime.hideArrowNavigation"
          class="lp-runtime-navigation"
        >
          <BaseButton
            id="lp-runtime-navigation-previous"
            :disabled="!runtime.previousItemId || isChangingItem"
            :label="t('Previous')"
            icon="arrow-left"
            only-icon
            type="primary"
            @click="openItem(runtime.previousItemId)"
          />
          <BaseButton
            id="lp-runtime-navigation-next"
            :disabled="!runtime.nextItemId || isChangingItem"
            :label="t('Next')"
            icon="arrow-right"
            only-icon
            type="primary"
            @click="openItem(runtime.nextItemId)"
          />
        </div>

        <a
          v-if="!isImpressMode && progressValue >= 100 && runtime.nextLearningPathUrl"
          :href="runtime.nextLearningPathUrl"
          :title="runtime.nextLearningPathTitle"
          class="lp-runtime-next-learning-path"
        >
          {{ t("Next") }}
          <BaseIcon
            icon="arrow-right"
            size="small"
          />
        </a>

        <div
          v-if="runtime.audioUrl"
          class="lp-runtime-audio"
        >
          <audio
            :key="`${runtime.currentItemId}-${runtime.audioUrl}`"
            :aria-label="runtime.audioTitle || t('Audio')"
            :autoplay="Boolean(runtime.audioAutoplay)"
            controls
            preload="metadata"
            :src="runtime.audioUrl"
          />
        </div>

        <LpImpressRuntime
          v-if="isImpressMode"
          :iframe-loading="iframeLoading"
          :iframe-reload-key="iframeReloadKey"
          :is-changing-item="isChangingItem"
          :resume-at-current="Number(runtime.currentAttempt || 0) > 0"
          :runtime="runtime"
          @active-change="handleImpressActiveChange"
          @iframe-load="handleIframeLoad"
          @open-item="openItem"
        />

        <template v-else>
          <div
            v-if="isChangingItem || iframeLoading"
            class="lp-runtime-loader"
          >
            {{ t("Loading") }}…
          </div>

          <iframe
            v-if="runtime.contentUrl"
            ref="contentFrame"
            :key="
              `${runtime.currentItemId}-${runtime.scorm?.itemViewId || runtime.currentItemAttempt}-${iframeReloadKey}`
            "
            :src="runtime.contentUrl"
            :title="currentItem?.title || runtime.title"
            allowfullscreen
            class="lp-runtime-iframe"
            @load="handleIframeLoad"
          />
        </template>
      </main>
    </template>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import LpImpressRuntime from "../../components/lp/LpImpressRuntime.vue"
import { useNotification } from "../../composables/notification"
import lpService from "../../services/lpService"
import { createScormRuntimeApi } from "../../services/scormRuntimeApi"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { showErrorNotification } = useNotification()

const runtime = ref(null)
const contentFrame = ref(null)
const isLoading = ref(false)
const isChangingItem = ref(false)
const isSyncingRuntime = ref(false)
const iframeLoading = ref(false)
const iframeReloadKey = ref(0)
const previewImageFailed = ref(false)
const errorMessage = ref("")
const menuOpen = ref(false)
const tocCollapsed = ref(false)
const initialized = ref(false)
const impressActiveIsSection = ref(false)
const collapsedSections = ref(new Set())
const runtimeLoadedAt = ref(Date.now())
const clockTick = ref(Date.now())
let clockTimer = null
let refreshTimer = null
let trackingTimer = null
let lastBeaconAt = 0
let scormRuntimeContext = null
let scormRuntimeKey = ""

const lpId = computed(() => Number(route.params.lpId || 0))
const contextParams = computed(() => ({
  cid: Number(route.query.cid || 0),
  sid: Number(route.query.sid || 0),
  gid: Number(route.query.gid || 0),
  gradebook: Number(route.query.gradebook || 0),
  origin: String(route.query.origin || "learnpath"),
  isStudentView: String(route.query.isStudentView || "true"),
}))

function exposeLegacyCidContext() {
  const query = new URLSearchParams()

  if (contextParams.value.cid) {
    query.set("cid", contextParams.value.cid)
  }

  if (contextParams.value.sid) {
    query.set("sid", contextParams.value.sid)
  }

  if (contextParams.value.gid) {
    query.set("gid", contextParams.value.gid)
  }

  if (contextParams.value.gradebook) {
    query.set("gradebook", contextParams.value.gradebook)
  }

  const queryParams = query.toString()

  try {
    const currentCidReq = window.chamiloCidReq

    if (currentCidReq && typeof currentCidReq === "object") {
      currentCidReq.queryParams = queryParams

      return
    }

    Object.defineProperty(window, "chamiloCidReq", {
      configurable: true,
      enumerable: true,
      writable: true,
      value: {
        queryParams,
      },
    })
  } catch (error) {
    console.warn("Unable to expose legacy course context.", error)
  }
}
const currentItem = computed(
  () => runtime.value?.items?.find((item) => Number(item.id) === Number(runtime.value.currentItemId)) || null,
)
const previewImageUrl = computed(() => String(runtime.value?.previewImageUrl || ""))
const shouldShowPreviewImage = computed(() => {
  const url = previewImageUrl.value.trim()

  return url !== "" && !url.endsWith("/main/img/icons/128/unknown.png") && !previewImageFailed.value
})
const isImpressMode = computed(() => String(runtime.value?.displayMode || "") === "impress")
const itemsById = computed(() =>
  Object.fromEntries((runtime.value?.items || []).map((item) => [Number(item.id), item])),
)
const progressValue = computed(() => Math.min(100, Math.max(0, Number(runtime.value?.progress || 0))))
const elapsedSinceLoad = computed(() => Math.max(0, Math.floor((clockTick.value - runtimeLoadedAt.value) / 1000)))
const displayedTotalTime = computed(() => Math.max(0, Number(runtime.value?.totalTime || 0) + elapsedSinceLoad.value))
const formattedTotalTime = computed(() => formatDuration(displayedTotalTime.value))
const formattedMinimumTime = computed(() => formatDuration(Number(runtime.value?.minimumTime || 0)))
const visibleItems = computed(() =>
  (runtime.value?.items || []).filter((item) => !hasCollapsedAncestor(item)),
)
const homeLabel = computed(() => {
  const labels = {
    0: "Course home",
    1: "Learning paths",
    2: "My courses",
    3: "Home",
    4: "My sessions",
  }

  return t(labels[Number(runtime.value?.returnLink || 0)] || "Course home")
})
const editorQuery = computed(() =>
  Object.fromEntries(
    Object.entries({ ...route.query, isStudentView: "false" }).filter(([key]) => key !== "item_id"),
  ),
)
const builderRoute = computed(() => ({
  name: "LpBuilder",
  params: { node: route.params.node, lpId: route.params.lpId },
  query: editorQuery.value,
}))
const settingsRoute = computed(() => ({
  name: "LpSettings",
  params: { node: route.params.node, lpId: route.params.lpId },
  query: editorQuery.value,
}))

function handlePreviewImageError() {
  previewImageFailed.value = true
}

function formatDuration(value) {
  const seconds = Math.max(0, Number(value || 0))
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const remainingSeconds = Math.floor(seconds % 60)

  return [hours, minutes, remainingSeconds].map((part) => String(part).padStart(2, "0")).join(":")
}

function itemLevelClass(level) {
  return `lp-runtime-level-${Math.min(Math.max(Number(level) || 0, 0), 5)}`
}

function itemIcon(itemType) {
  const icons = {
    document: "file-text",
    video: "file-video",
    readout_text: "file-text",
    final_item: "file-text",
    quiz: "check",
    link: "link",
    student_publication: "file-upload",
    assignments: "file-upload",
    forum: "comment",
    thread: "comment",
    survey: "form-textarea",
    sco: "file-generic",
    asset: "file-generic",
  }

  return icons[String(itemType || "")] || "file-generic"
}

function normalizedStatus(status) {
  return String(status || "")
    .trim()
    .toLowerCase()
}

function statusIcon(item) {
  if (!item.available) {
    return "lock"
  }

  const value = normalizedStatus(item.status)
  if (["completed", "passed", "succeeded", "browsed"].includes(value)) {
    return "check"
  }
  if (value === "failed") {
    return "close"
  }

  return ""
}

function statusClass(item) {
  const value = normalizedStatus(item.status)
  if (!item.available) {
    return "lp-runtime-status lp-runtime-status--locked"
  }
  if (["completed", "passed", "succeeded", "browsed"].includes(value)) {
    return "lp-runtime-status lp-runtime-status--completed"
  }
  if (value === "failed") {
    return "lp-runtime-status lp-runtime-status--failed"
  }

  return "lp-runtime-status"
}

function sectionExpanded(itemId) {
  return !collapsedSections.value.has(Number(itemId))
}

function toggleSection(itemId) {
  if (!runtime.value?.accordionToc) {
    return
  }

  const next = new Set(collapsedSections.value)
  const id = Number(itemId)
  if (next.has(id)) {
    next.delete(id)
  } else {
    next.add(id)
  }
  collapsedSections.value = next
}

function hasCollapsedAncestor(item) {
  let parentId = Number(item.parentId || 0)
  const visited = new Set()
  while (parentId > 0 && !visited.has(parentId)) {
    if (collapsedSections.value.has(parentId)) {
      return true
    }
    visited.add(parentId)
    parentId = Number(itemsById.value[parentId]?.parentId || 0)
  }

  return false
}

function expandCurrentAncestors() {
  if (!runtime.value?.currentItemId) {
    return
  }

  const next = new Set(collapsedSections.value)
  let parentId = Number(itemsById.value[Number(runtime.value.currentItemId)]?.parentId || 0)
  const visited = new Set()
  while (parentId > 0 && !visited.has(parentId)) {
    next.delete(parentId)
    visited.add(parentId)
    parentId = Number(itemsById.value[parentId]?.parentId || 0)
  }
  collapsedSections.value = next
}

function toggleToc() {
  tocCollapsed.value = !tocCollapsed.value
}

function clearScormRuntime() {
  if (window.API === scormRuntimeContext?.api12) {
    delete window.API
  }
  if (window.api === scormRuntimeContext?.api12) {
    delete window.api
  }
  if (window.API_1484_11 === scormRuntimeContext?.api2004) {
    delete window.API_1484_11
  }

  scormRuntimeContext?.destroy()
  scormRuntimeContext = null
  scormRuntimeKey = ""
}

async function flushScormRuntime(reason = "flush") {
  if (!scormRuntimeContext) {
    return
  }

  await scormRuntimeContext.flush(reason)
}

function installScormRuntime(data, { forceRecreate = false } = {}) {
  const config = data?.scorm || {}
  const itemId = Number(data?.currentItemId || 0)
  const itemViewId = Number(config.itemViewId || 0)
  const version = String(config.version || "")
  const key = config.enabled && itemId > 0 && itemViewId > 0 ? `${lpId.value}:${itemId}:${itemViewId}:${version}` : ""

  if (!key) {
    clearScormRuntime()
    return
  }
  if (key === scormRuntimeKey && scormRuntimeContext && !forceRecreate) {
    return
  }

  clearScormRuntime()

  const csrfToken = String(data.csrfToken || "")
  scormRuntimeContext = createScormRuntimeApi({
    version,
    initialValues: config.values || {},
    forceCommit: Boolean(config.forceCommit),
    debug: Boolean(config.debug),
    commit: async (payload) => {
      await lpService.commitScormRuntime(lpId.value, itemId, contextParams.value, {
        ...payload,
        itemId,
        itemViewId,
        version,
        csrfToken,
      })
    },
    beacon: (payload) =>
      lpService.commitScormRuntimeBeacon(lpId.value, itemId, contextParams.value, {
        ...payload,
        itemId,
        itemViewId,
        version,
        csrfToken,
      }),
    onCommitted: scheduleRuntimeRefresh,
  })
  scormRuntimeKey = key

  if (version === "2004") {
    window.API_1484_11 = scormRuntimeContext.api2004
    delete window.API
  } else {
    window.API = scormRuntimeContext.api12
    window.api = scormRuntimeContext.api12
    delete window.API_1484_11
  }
}

function applyRuntime(data, { contentChanged = false } = {}) {
  if (!data.runtimeSupported && data.legacyFallbackUrl) {
    window.location.replace(data.legacyFallbackUrl)
    return false
  }

  installScormRuntime(data, { forceRecreate: contentChanged })
  runtime.value = data
  previewImageFailed.value = false
  runtimeLoadedAt.value = Date.now()
  clockTick.value = Date.now()

  if (!initialized.value) {
    menuOpen.value = Boolean(data.showToolbarByDefault)
    tocCollapsed.value = String(data.displayMode || "") === "embedframe"
    initialized.value = true
  }

  if (contentChanged) {
    if (data.contentUrl) {
      iframeReloadKey.value += 1
    }
    iframeLoading.value = Boolean(data.contentUrl)
  }

  nextTick(expandCurrentAncestors)

  return true
}

async function fetchRuntime(itemId = 0) {
  return await lpService.getRuntime(lpId.value, {
    ...contextParams.value,
    itemId: Number(itemId || 0) > 0 ? Number(itemId) : undefined,
  })
}

async function syncRuntimeState() {
  if (
    !runtime.value?.csrfToken ||
    isChangingItem.value ||
    isSyncingRuntime.value ||
    impressActiveIsSection.value ||
    !runtime.value.currentItemId
  ) {
    return
  }

  isSyncingRuntime.value = true

  try {
    await lpService.syncRuntime(lpId.value, contextParams.value, {
      itemId: Number(runtime.value.currentItemId),
      csrfToken: runtime.value.csrfToken,
    })
  } catch (error) {
    console.error("[LearningPathRuntime] Unable to synchronize runtime progress.", error)
  } finally {
    isSyncingRuntime.value = false
  }
}

async function refreshRuntimeState() {
  if (!runtime.value || isChangingItem.value) {
    return
  }

  try {
    await syncRuntimeState()
    const data = await fetchRuntime(runtime.value.currentItemId)
    applyRuntime(data)
  } catch (error) {
    console.error("[LearningPathRuntime] Unable to refresh runtime state.", error)
  }
}

function scheduleRuntimeRefresh() {
  window.clearTimeout(refreshTimer)
  refreshTimer = window.setTimeout(refreshRuntimeState, 300)
}

async function loadRuntime({ recordCurrent = true } = {}) {
  if (!lpId.value || !contextParams.value.cid) {
    errorMessage.value = t("An error occurred")
    return
  }

  isLoading.value = true
  errorMessage.value = ""

  try {
    const itemId = Number(route.query.item_id || 0)
    const data = await fetchRuntime(itemId)

    if (!applyRuntime(data, { contentChanged: true })) {
      return
    }

    if (recordCurrent && data.runtimeSupported && data.currentItemId > 0 && data.csrfToken) {
      await lpService.openRuntimeItem(lpId.value, contextParams.value, {
        itemId: data.currentItemId,
        allowNewAttempt: false,
        csrfToken: data.csrfToken,
      })
      const refreshedData = await fetchRuntime(data.currentItemId)
      const contentChanged =
        Number(refreshedData.currentItemId || 0) !== Number(data.currentItemId || 0) ||
        String(refreshedData.contentUrl || "") !== String(data.contentUrl || "")

      applyRuntime(refreshedData, { contentChanged })
    }
  } catch (error) {
    errorMessage.value = error?.response?.data?.["hydra:description"] || t("Loading failed.")
    showErrorNotification(error)
  } finally {
    isLoading.value = false
  }
}

async function openItem(itemId) {
  const id = Number(itemId || 0)
  if (!id || isChangingItem.value || !runtime.value?.csrfToken) {
    return
  }

  impressActiveIsSection.value = false
  isChangingItem.value = true
  iframeLoading.value = true

  try {
    await flushScormRuntime("navigation")
    await lpService.openRuntimeItem(lpId.value, contextParams.value, {
      itemId: id,
      allowNewAttempt: true,
      csrfToken: runtime.value.csrfToken,
    })
    await router.replace({
      name: "LpRuntime",
      params: route.params,
      query: { ...route.query, item_id: id },
    })
    const data = await fetchRuntime(id)
    applyRuntime(data, { contentChanged: true })
  } catch (error) {
    iframeLoading.value = false
    showErrorNotification(error)
  } finally {
    isChangingItem.value = false
  }
}

function handleIframeLoad(event) {
  contentFrame.value = event?.target || contentFrame.value
  iframeLoading.value = false
  scheduleRuntimeRefresh()
}

function handleImpressActiveChange(item) {
  const isSection = Boolean(item?.isSection)
  if (isSection && !impressActiveIsSection.value) {
    void flushScormRuntime("presentation-section")
    void syncRuntimeState()
  }

  impressActiveIsSection.value = isSection
  if (isSection) {
    contentFrame.value = null
    iframeLoading.value = false
  }
}

function handleRuntimeMessage(event) {
  if (event.source !== contentFrame.value?.contentWindow) {
    return
  }

  scheduleRuntimeRefresh()
}

function handleVisibilityChange() {
  if (document.hidden) {
    handlePageHide()
    return
  }

  scheduleRuntimeRefresh()
}

function handlePageHide() {
  const now = Date.now()
  scormRuntimeContext?.flushBeacon("pagehide")
  if (
    isSyncingRuntime.value ||
    now - lastBeaconAt < 1000 ||
    !runtime.value?.csrfToken ||
    !runtime.value.currentItemId
  ) {
    return
  }

  lastBeaconAt = now
  lpService.syncRuntimeBeacon(lpId.value, contextParams.value, {
    itemId: Number(runtime.value.currentItemId),
    csrfToken: runtime.value.csrfToken,
  })
}

onMounted(() => {
  exposeLegacyCidContext()
  document.documentElement.classList.add("lp-runtime-document")
  document.body.classList.add("lp-runtime-document")
  window.addEventListener("message", handleRuntimeMessage)
  window.addEventListener("focus", scheduleRuntimeRefresh)
  window.addEventListener("pagehide", handlePageHide)
  document.addEventListener("visibilitychange", handleVisibilityChange)
  clockTimer = window.setInterval(() => {
    clockTick.value = Date.now()
  }, 1000)
  trackingTimer = window.setInterval(() => {
    void syncRuntimeState()
  }, 30000)
  loadRuntime()
})

onBeforeUnmount(() => {
  document.documentElement.classList.remove("lp-runtime-document")
  document.body.classList.remove("lp-runtime-document")
  window.removeEventListener("message", handleRuntimeMessage)
  window.removeEventListener("focus", scheduleRuntimeRefresh)
  window.removeEventListener("pagehide", handlePageHide)
  document.removeEventListener("visibilitychange", handleVisibilityChange)
  window.clearInterval(clockTimer)
  window.clearInterval(trackingTimer)
  window.clearTimeout(refreshTimer)
  scormRuntimeContext?.flushBeacon("unmount")
  clearScormRuntime()
})
</script>

<style>
html.lp-runtime-document,
body.lp-runtime-document {
  width: 100%;
  height: 100%;
  margin: 0;
  overflow: hidden;
}
</style>

<style scoped>
.lp-runtime-player {
  --lp-sidebar-width: 300px;
  --lp-sidebar-collapsed-width: 64px;
  position: fixed;
  inset: 0;
  z-index: 100;
  display: flex;
  width: 100vw;
  height: 100vh;
  overflow: hidden;
  background: #ffffff;
  color: #333333;
}

.lp-runtime-screen-state {
  display: grid;
  width: 100%;
  height: 100%;
  place-content: center;
  background: #ffffff;
  color: #667085;
  font-size: 14px;
}

.lp-runtime-screen-state--error {
  color: rgb(var(--color-danger-base));
}

.lp-runtime-sidebar {
  position: relative;
  z-index: 20;
  flex: 0 0 var(--lp-sidebar-width);
  width: var(--lp-sidebar-width);
  min-width: var(--lp-sidebar-width);
  height: 100%;
  overflow: visible;
  border-right: 1px solid #e4e9ed;
  background: #ffffff;
  transition: width 180ms ease, min-width 180ms ease, flex-basis 180ms ease;
}

.lp-view-collapsed .lp-runtime-sidebar {
  flex-basis: var(--lp-sidebar-collapsed-width);
  width: var(--lp-sidebar-collapsed-width);
  min-width: var(--lp-sidebar-collapsed-width);
  overflow: visible;
}

/* Override the globally loaded legacy LP rules, which use ID selectors with !important. */
#learning_path_main.lp-runtime-player #learning_path_left_zone.lp-runtime-sidebar {
  flex: 0 0 var(--lp-sidebar-width) !important;
  width: var(--lp-sidebar-width) !important;
  min-width: var(--lp-sidebar-width) !important;
  height: 100% !important;
  margin-left: 0 !important;
  padding-top: 0 !important;
  overflow: visible !important;
}

#learning_path_main.lp-runtime-player.lp-view-collapsed #learning_path_left_zone.lp-runtime-sidebar {
  flex: 0 0 var(--lp-sidebar-collapsed-width) !important;
  width: var(--lp-sidebar-collapsed-width) !important;
  min-width: var(--lp-sidebar-collapsed-width) !important;
  margin-left: 0 !important;
  overflow: visible !important;
}

.lp-view-collapsed .lp-runtime-sidebar-inner {
  display: none;
}

.lp-runtime-sidebar-inner {
  display: flex;
  height: 100%;
  flex-direction: column;
  overflow: hidden;
}

.lp-runtime-info {
  flex: 0 0 auto;
  padding: 80px 28px 26px;
  border-bottom: 1px solid #e4e9ed;
  text-align: center;
}

.lp-runtime-preview {
  display: block;
  width: min(180px, 86%);
  max-width: 180px;
  height: auto;
  max-height: 150px;
  margin: 0 auto 14px;
  border-radius: 16px;
  object-fit: contain;
}

.lp-runtime-preview-fallback {
  display: flex;
  min-height: 92px;
  align-items: center;
  justify-content: center;
  border: 1px solid rgb(var(--color-primary-base) / 0.18);
  background: rgb(var(--color-primary-base) / 0.08);
  color: rgb(var(--color-primary-base));
}

.lp-runtime-preview-fallback :deep(.mdi) {
  font-size: 48px;
}

.lp-runtime-author {
  width: 100%;
  max-width: 240px;
  margin: 0 auto 14px;
  overflow-wrap: anywhere;
  color: #374151;
  font-size: 13px;
  line-height: 1.45;
  text-align: left;
}

.lp-runtime-progress-track {
  display: block;
  width: 100%;
  height: 2px;
  overflow: hidden;
  border: 0;
  background: #e4e9ed;
  appearance: none;
}

.lp-runtime-progress-track::-webkit-progress-bar {
  background: #e4e9ed;
}

.lp-runtime-progress-track::-webkit-progress-value {
  background: rgb(var(--color-primary-base));
}

.lp-runtime-progress-track::-moz-progress-bar {
  background: rgb(var(--color-primary-base));
}

.lp-runtime-progress-label {
  margin-top: 8px;
  color: rgb(var(--color-primary-base));
  font-size: 11px;
  font-weight: 600;
  text-align: right;
}

.lp-runtime-time {
  display: flex;
  justify-content: space-between;
  gap: 8px;
  margin-top: 12px;
  color: #667085;
  font-size: 11px;
}

.lp-runtime-time strong {
  color: #344054;
  font-weight: 600;
}

.lp-runtime-toc {
  flex: 1 1 auto;
  min-height: 0;
  overflow-y: auto;
  background: #ffffff;
}

.lp-runtime-toc-title {
  padding: 14px 18px;
  border-bottom: 1px solid #edf0f2;
  color: #222222;
  font-size: 14px;
  font-weight: 700;
  text-align: center;
}

.lp-runtime-section,
.lp-runtime-item {
  display: flex;
  width: 100%;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  border: 0;
  border-bottom: 1px solid #edf0f2;
  background: #ffffff;
  color: #333333;
  text-align: left;
}

.lp-runtime-section {
  min-height: 46px;
  padding: 10px 18px;
  color: #222222;
  font-size: 14px;
  font-weight: 700;
}

.lp-runtime-section:not(:disabled) {
  cursor: pointer;
}

.lp-runtime-section:disabled {
  opacity: 1;
}

.lp-runtime-section-chevron {
  color: #667085;
}

.lp-runtime-item {
  min-height: 44px;
  padding: 0 18px 0 20px;
  border-left: 4px solid transparent;
  transition: background 150ms ease, color 150ms ease, border-color 150ms ease;
}

.lp-runtime-item:not(:disabled) {
  cursor: pointer;
}

.lp-runtime-item:not(:disabled):hover,
.lp-runtime-item--active {
  border-left-color: rgb(var(--color-primary-base));
  background: rgb(var(--color-primary-base) / 0.1);
  color: rgb(var(--color-primary-base));
}

.lp-runtime-item--active {
  font-weight: 600;
}

.lp-runtime-item--disabled {
  cursor: not-allowed;
  color: #98a2b3;
}

.lp-runtime-item-main {
  display: flex;
  min-width: 0;
  flex: 1 1 auto;
  align-items: center;
  gap: 10px;
}

.lp-runtime-item-type {
  flex: 0 0 18px;
}

.lp-runtime-item-title {
  min-width: 0;
  overflow: hidden;
  font-size: 14px;
  line-height: 1.35;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.lp-runtime-status,
.lp-runtime-status-placeholder {
  display: inline-flex;
  width: 18px;
  min-width: 18px;
  align-items: center;
  justify-content: center;
}

.lp-runtime-status--completed {
  color: rgb(var(--color-success-base));
}

.lp-runtime-status--failed {
  color: rgb(var(--color-danger-base));
}

.lp-runtime-status--locked {
  color: #98a2b3;
}

.lp-runtime-level-1 {
  padding-left: 34px;
}

.lp-runtime-level-2 {
  padding-left: 48px;
}

.lp-runtime-level-3 {
  padding-left: 62px;
}

.lp-runtime-level-4 {
  padding-left: 76px;
}

.lp-runtime-level-5 {
  padding-left: 90px;
}

.lp-runtime-content {
  position: relative;
  min-width: 0;
  height: 100%;
  flex: 1 1 auto;
  overflow: hidden;
  padding: 20px 30px 20px 38px;
  background: #ffffff;
  box-sizing: border-box;
}

.lp-view-collapsed .lp-runtime-content {
  padding-left: 34px;
}

#learning_path_main.lp-runtime-player #learning_path_right_zone.lp-runtime-content {
  flex: 1 1 auto !important;
  width: auto !important;
  min-width: 0 !important;
  height: 100% !important;
  margin-left: 0 !important;
  padding: 20px 30px 20px 38px !important;
  overflow: hidden !important;
}

#learning_path_main.lp-runtime-player.lp-view-collapsed #learning_path_right_zone.lp-runtime-content {
  margin-left: 0 !important;
  padding-left: 34px !important;
}

.lp-runtime-content--impress,
#learning_path_main.lp-runtime-player #learning_path_right_zone.lp-runtime-content--impress {
  padding: 0 !important;
  background: #bebebe;
}

.lp-runtime-impress-mode .lp-runtime-menu {
  z-index: 180;
}

.lp-runtime-iframe {
  display: block;
  width: 100%;
  height: 100%;
  border: 0;
  background: #ffffff;
}

.lp-runtime-loader {
  position: absolute;
  top: 50%;
  left: 50%;
  z-index: 20;
  padding: 4px 8px;
  border: 1px solid #e6d95c;
  background: #fffbd2;
  color: #667085;
  font-size: 11px;
  transform: translate(-50%, -50%);
}

.lp-runtime-menu {
  position: fixed;
  top: 20px;
  z-index: 150;
}

.lp-runtime-menu--left {
  left: 14px;
}

.lp-runtime-menu--right {
  right: 14px;
}

.lp-runtime-menu--middle {
  top: 50%;
  transform: translateY(-50%);
}

:deep(#lp-runtime-menu-toggle.p-button) {
  width: 40px;
  height: 40px;
  border: 1px solid rgba(15, 23, 42, 0.06);
  border-radius: 10px;
  background: #ffffff;
  color: rgb(var(--color-primary-base));
  box-shadow: 0 2px 10px rgba(15, 23, 42, 0.08);
}

.lp-runtime-menu-panel {
  position: absolute;
  top: 48px;
  left: 0;
  width: 166px;
  overflow: hidden;
  border: 1px solid #e4e9ed;
  border-radius: 10px;
  background: #ffffff;
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.14);
}

.lp-runtime-menu--right .lp-runtime-menu-panel {
  right: 0;
  left: auto;
}

.lp-runtime-menu-link {
  display: flex;
  min-height: 40px;
  align-items: center;
  gap: 10px;
  padding: 0 16px;
  color: #333333;
  font-size: 13px;
  text-decoration: none;
}

.lp-runtime-menu-link:hover {
  background: #f8fafc;
  color: rgb(var(--color-primary-base));
}

.lp-runtime-collapse {
  position: fixed;
  top: 42px;
  left: var(--lp-sidebar-width);
  z-index: 1000;
  display: inline-flex;
  width: 40px;
  height: 40px;
  align-items: center;
  justify-content: center;
  margin: 0;
  padding: 0;
  pointer-events: auto;
  transform: translateX(-50%);
  transition: left 180ms ease;
}

.lp-view-collapsed .lp-runtime-collapse,
.lp-runtime-collapse--closed {
  left: var(--lp-sidebar-collapsed-width);
}

:deep(#lp-runtime-collapse-button.p-button) {
  position: relative;
  z-index: 1;
  display: inline-flex;
  width: 40px;
  min-width: 40px;
  height: 40px;
  padding: 0;
  overflow: visible;
  align-items: center;
  justify-content: center;
  border: 1px solid #e4e9ed;
  border-radius: 999px;
  background: #ffffff;
  color: rgb(var(--color-primary-base));
  box-shadow: 0 2px 10px rgba(15, 23, 42, 0.08);
  pointer-events: auto;
}

.lp-runtime-navigation {
  position: absolute;
  top: 20px;
  right: 22px;
  z-index: 120;
  display: inline-flex;
  overflow: hidden;
  border-radius: 10px;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.12);
}

:deep(#lp-runtime-navigation-previous.p-button),
:deep(#lp-runtime-navigation-next.p-button) {
  width: 40px;
  height: 40px;
  border-radius: 0;
  box-shadow: none;
}

:deep(#lp-runtime-navigation-previous.p-button) {
  border-right: 1px solid rgb(255 255 255 / 0.32);
}

.lp-runtime-audio {
  position: absolute;
  bottom: 16px;
  left: 50%;
  z-index: 130;
  width: min(520px, calc(100% - 64px));
  padding: 8px;
  border: 1px solid #e4e9ed;
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.96);
  box-shadow: 0 4px 16px rgba(15, 23, 42, 0.14);
  transform: translateX(-50%);
}

.lp-runtime-audio audio {
  display: block;
  width: 100%;
  height: 40px;
}

.lp-runtime-next-learning-path {
  position: absolute;
  top: 22px;
  left: 50%;
  z-index: 110;
  display: inline-flex;
  min-height: 36px;
  align-items: center;
  gap: 6px;
  padding: 0 14px;
  border-radius: 8px;
  background: rgb(var(--color-primary-base));
  color: #ffffff;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  transform: translateX(-50%);
}

@media (max-width: 767px) {
  .lp-runtime-player {
    --lp-sidebar-width: 100vw;
    --lp-sidebar-collapsed-width: 0px;
  }

  .lp-runtime-sidebar {
    position: absolute;
    z-index: 110;
  }

  .lp-view-collapsed .lp-runtime-sidebar {
    width: 0;
    min-width: 0;
    border-right: 0;
  }

  .lp-runtime-collapse,
  .lp-view-collapsed .lp-runtime-collapse,
  .lp-runtime-collapse--closed {
    top: 42px;
    left: auto;
    right: 12px;
    transform: none;
  }

  .lp-runtime-navigation {
    top: 16px;
    right: 16px;
  }

  .lp-runtime-audio {
    bottom: 12px;
    width: calc(100% - 24px);
  }

  .lp-runtime-content,
  .lp-view-collapsed .lp-runtime-content {
    padding: 12px;
  }
}
</style>
