<template>
  <div class="lp-list">
    <SectionHeader :title="t('Learning paths')">
      <BaseButton
        v-if="canEdit"
        :label="t('More actions')"
        icon="dots-vertical"
        only-icon
        popup-identifier="lp-list-tmenu"
        type="black"
        @click="mLpList.toggle($event)"
      />
      <BaseMenu
        v-if="canEdit"
        id="lp-list-tmenu"
        ref="mLpList"
        :model="mItems"
      />
    </SectionHeader>

    <div class="flex flex-col gap-4 flex-1 min-h-0">
      <div
        v-if="loading"
        class="space-y-4 animate-pulse"
      >
        <div class="mt-4">
          <div class="h-36 bg-gray-15 rounded-2xl" />
        </div>
      </div>

      <EmptyState
        v-else-if="!hasAnyVisible"
        :detail="t('Create your first learning path to start organizing course content.')"
        :summary="t('You don\'t have any learning path.')"
        icon="learning-paths"
      >
        <BaseButton
          v-if="canEdit"
          :label="t('Create new learning path')"
          class="mt-4"
          icon="plus"
          @click="goCreateLp"
        />
      </EmptyState>

      <template v-else>
        <div
          v-if="uncatList.length || canReorder"
          class="min-w-0"
        >
          <Draggable
            :list="uncatList"
            :animation="180"
            :disabled="!canReorder || layoutBusy"
            :empty-insert-threshold="80"
            :fallback-on-body="true"
            :force-fallback="true"
            :group="{ name: 'learning-paths', pull: true, put: true }"
            chosen-class="chosen"
            class="min-h-[72px] space-y-6"
            data-category-id="0"
            drag-class="dragging"
            ghost-class="ghosting"
            handle=".drag-handle"
            item-key="iid"
            tag="div"
            @end="onLearningPathDragEnd"
          >
            <template #item="{ element }">
              <LpRowItem
                :buildDates="buildDates"
                :canAutoLaunch="canAutoLaunch"
                :canCopy="canCopy"
                :canCopyScorm="canCopyScorm"
                :canEdit="canEdit"
                :canReorder="canReorder"
                :canExportPdf="canExportPdf"
                :canExportChamilo="canExportChamilo"
                :canExportScorm="canExportScorm"
                :canSeriousGame="canSeriousGame"
                :csrf-token="actionToken"
                :legacyContext="legacyContext"
                :lp="element"
                :ringDash="ringDash"
                :ringValue="ringValue"
                @export-chamilo="onExportChamilo"
              @export-pdf="onExportPdf"
                @management-changed="load"
                @visibility-changed="load"
              />
            </template>
            <template #footer>
              <div
                v-if="canReorder && uncatList.length === 0"
                class="flex min-h-[72px] flex-col items-center justify-center rounded-2xl border border-dashed border-gray-30 bg-gray-10 px-4 py-3 text-sm text-gray-50"
              >
                <span class="font-semibold text-gray-70">{{ t("Without category") }}</span>
                <span>{{ t("Drag and drop an element here") }}</span>
              </div>
            </template>
          </Draggable>
        </div>

        <Draggable
          v-model="categoryLayout"
          :animation="180"
          :disabled="!canOrderCategories || layoutBusy"
          chosen-class="chosen"
          class="flex flex-col gap-4"
          drag-class="dragging"
          ghost-class="ghosting"
          handle=".category-drag-handle"
          item-key="iid"
          tag="div"
          @end="onCategoryDragEnd"
        >
          <template #item="{ element: group }">
            <LpCategorySection
              :buildDates="buildDates"
              :canAutoLaunch="canAutoLaunch"
              :canCopy="canCopy"
              :canCopyScorm="canCopyScorm"
              :canEdit="canEdit"
              :canReorder="canReorder"
              :can-order-category="canOrderCategories && group.category?.reorderable === true"
              :canExportPdf="canExportPdf"
              :canExportChamilo="canExportChamilo"
              :canExportScorm="canExportScorm"
              :canSeriousGame="canSeriousGame"
              :csrf-token="actionToken"
              :category="group.category"
              :isSessionCategory="group.isSessionCategory"
              :layout-busy="layoutBusy"
              :list="group.list"
              :ringDash="ringDash"
              :ringValue="ringValue"
              :title="group.category?.title"
              @export-pdf="onExportPdf"
              @layout-changed="onLearningPathDragEnd"
              @management-changed="load"
              @visibility-changed="load"
            />
          </template>
        </Draggable>
      </template>
    </div>

    <ExportPdfDialog
      v-if="showExportDialog && exportTarget"
      :cid="course?.id"
      :gid="legacyContext.gid"
      :lp-id="exportTarget.iid"
      :show="showExportDialog"
      :sid="session?.id"
      @close="onCloseExportDialog"
    />
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, ref, watch } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useCidReqStore } from "../../store/cidReq"
import { checkIsAllowedToEdit } from "../../composables/userPermissions"
import lpService from "../../services/lpService"
import { useSecurityStore } from "../../store/securityStore"
import { usePlatformConfig } from "../../store/platformConfig"
import Draggable from "vuedraggable"
import LpRowItem from "../../components/lp/LpRowItem.vue"
import LpCategorySection from "../../components/lp/LpCategorySection.vue"
import { useI18n } from "vue-i18n"
import { useCourseSettings } from "../../store/courseSettingStore"
import ExportPdfDialog from "../../components/lp/ExportPdfDialog.vue"
import { storeToRefs } from "pinia"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseMenu from "../../components/basecomponents/BaseMenu.vue"
import EmptyState from "../../components/EmptyState.vue"
import { LP_LIST_LOADED } from "../../constants/events"
import { useNotification } from "../../composables/notification"
import api from "../../config/api"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const cidReqStore = useCidReqStore()
const platformConfig = usePlatformConfig()
const courseSettingsStore = useCourseSettings()
const securityStore = useSecurityStore()

const { showErrorNotification, showSuccessNotification } = useNotification()

const loading = ref(true)
const layoutBusy = ref(false)
const layoutSaveQueued = ref(false)

const rawCanEdit = ref(false)
const allowChamiloExport = ref(false)
const routeStudentViewFlag = computed(() => {
  if (!Object.prototype.hasOwnProperty.call(route.query, "isStudentView")) {
    return null
  }

  const value = String(route.query?.isStudentView ?? "").toLowerCase()

  if (["1", "true", "yes", "on"].includes(value)) {
    return true
  }

  if (["0", "false", "no", "off"].includes(value)) {
    return false
  }

  return null
})
const isStudentView = computed(() => routeStudentViewFlag.value ?? platformConfig.isStudentViewActive)
const canEdit = computed(() => rawCanEdit.value && !isStudentView.value)

const mLpList = ref(null)
const mItems = computed(() => {
  const ctx = legacyContext.value

  if (!canEdit.value) {
    return []
  }

  return [
    { label: t("Create new learning path"), command: () => router.push({ name: "LpCreate", query: route.query }) },
    ...(canUseAi.value
      ? [
          {
            label: t("AI learning path generator"),
            command: () => router.push({ name: "LpAiGenerator", query: route.query }),
          },
        ]
      : []),
    { label: t("Import"), command: () => router.push({ name: "LpScormImport", query: route.query }) },
    ...(canAddCategory.value
      ? [{ label: t("Add category"), command: () => router.push({ name: "LpCategoryCreate", query: route.query }) }]
      : []),
  ]
})

const { course, session } = storeToRefs(cidReqStore)

const canAddCategory = computed(
  () =>
    canEdit.value &&
    (Number(session.value?.id ?? 0) === 0 || isTruthy(platformConfig.getSetting("lp.allow_session_lp_category"))),
)

const canReorder = computed(
  () =>
    canEdit.value &&
    Boolean(actionToken.value) &&
    Number(session.value?.id ?? 0) === 0 &&
    Number(route.query?.gid ?? 0) === 0,
)

const legacyContext = computed(() => {
  const node = Number(route.params?.node ?? 0) || undefined
  const gid = Number(route.query?.gid ?? 0)
  const gradebook = Number(route.query?.gradebook ?? 0)
  const origin = String(route.query?.origin ?? "")

  return {
    cid: course.value?.id,
    sid: session.value?.id ?? 0,
    node,
    gid,
    gradebook,
    origin,
  }
})

function exposeLegacyCidContext() {
  const query = new URLSearchParams()

  if (legacyContext.value.cid) {
    query.set("cid", legacyContext.value.cid)
  }

  if (legacyContext.value.sid) {
    query.set("sid", legacyContext.value.sid)
  }

  if (legacyContext.value.gid) {
    query.set("gid", legacyContext.value.gid)
  }

  if (legacyContext.value.gradebook) {
    query.set("gradebook", legacyContext.value.gradebook)
  }

  const queryParams = query.toString()

  try {
    const currentCidReq = window.chamiloCidReq

    if (currentCidReq && typeof currentCidReq === "object") {
      currentCidReq.queryParams = queryParams

      return
    }

    window.chamiloCidReq = {
      queryParams,
    }
  } catch (error) {
    console.warn("Unable to expose legacy course context.", error)
  }
}

const canCopy = computed(
  () =>
    canEdit.value &&
    Number(legacyContext.value.gid ?? 0) === 0 &&
    String(platformConfig.getSetting("lp.hide_scorm_copy_link")).toLowerCase() !== "true",
)

const canCopyScorm = computed(
  () => String(platformConfig.getSetting("lp.allow_import_scorm_package_in_course_builder")).toLowerCase() === "true",
)

const canUseAi = computed(
  () =>
    canEdit.value &&
    String(platformConfig.getSetting("ai_helpers.enable_ai_helpers")) === "true" &&
    courseSettingsStore.isSettingEnabled("learning_path_generator", "ai_helpers"),
)

const showExportDialog = ref(false)
const exportTarget = ref(null)

// Only original SCORM packages are downloadable. Generic LP-to-SCORM generation
// remains disabled because the legacy exporter is not compatible with current storage.
const canExportScorm = computed(() => {
  const hidden = String(platformConfig.getSetting("lp.hide_scorm_export_link")).toLowerCase() === "true"
  const allowedForStudents =
    String(platformConfig.getSetting("lp.lp_allow_export_to_students")).toLowerCase() === "true"

  return !hidden && (canEdit.value || allowedForStudents)
})

const canExportPdf = computed(() => !isTruthy(platformConfig.getSetting("lp.hide_scorm_pdf_link")))

const canExportChamilo = computed(
  () => canEdit.value && Number(legacyContext.value.gid ?? 0) === 0 && allowChamiloExport.value,
)

// Uses a click handler instead of :to-url so the URL (including cid/sid) is
// built at click time, when the course store is guaranteed to be populated.
// A static :to-url binding can render before the store resolves, producing a
// link without cid that the legacy controller rejects as "Not allowed".
const goCreateLp = () => {
  router.push({ name: "LpCreate", query: route.query })
}

const canAutoLaunch = computed(() => {
  if (!canEdit.value) {
    return false
  }

  const val = courseSettingsStore?.getSetting?.("enable_lp_auto_launch")

  return String(val) === "true" || Number(val) === 1
})

const canSeriousGame = computed(() => {
  const value = platformConfig.getSetting("workflows.gamification_mode")

  return String(value).toLowerCase() === "true" || Number(value) === 1
})

const items = ref([])
const categories = ref([])
const visibilityMap = ref({})
const actionToken = ref("")

function isTruthy(value) {
  return value === true || value === 1 || value === "1" || String(value).toLowerCase() === "true"
}

function isLpCurrentlyAvailable(lp) {
  const now = new Date()
  const publishedOn = lp.publishedOn ? new Date(lp.publishedOn) : null
  const expiredOn = lp.expiredOn ? new Date(lp.expiredOn) : null

  if (publishedOn && publishedOn > now) {
    return false
  }

  if (expiredOn && expiredOn < now) {
    return false
  }

  return true
}

function shouldShowUnavailableLp(lp) {
  const settingEnabled = isTruthy(platformConfig.getSetting("lp.lp_start_and_end_date_visible_in_student_view"))
  const displayNotAllowed = isTruthy(lp.displayNotAllowedLp ?? lp.display_not_allowed_lp ?? false)

  return settingEnabled && displayNotAllowed && !isLpCurrentlyAvailable(lp)
}

function hasVisibilityMapValue(lpId) {
  if (!visibilityMap.value || "object" !== typeof visibilityMap.value) {
    return false
  }

  return Object.prototype.hasOwnProperty.call(visibilityMap.value, String(lpId))
}

function isPublishedForStudent(lp) {
  const value = lp?.published ?? lp?.isPublished ?? lp?.publicationStatus

  if (typeof value === "undefined" || value === null || value === "") {
    return true
  }

  if (typeof value === "string") {
    return ["1", "true", "v", "visible", "published"].includes(value.toLowerCase())
  }

  return Boolean(value)
}

function isVisibleForStudent(lp) {
  const value = lp?.visible ?? lp?.visibility

  if (typeof value === "undefined" || value === null || value === "") {
    return true
  }

  if (typeof value === "string") {
    return ["1", "true", "v", "visible", "published"].includes(value.toLowerCase())
  }

  return Boolean(value)
}

function isLocallyVisibleForStudent(lp) {
  if (!isPublishedForStudent(lp) || !isVisibleForStudent(lp)) {
    return false
  }

  if (!isLpCurrentlyAvailable(lp)) {
    return shouldShowUnavailableLp(lp)
  }

  return true
}

function isVisibleInStudentView(lp) {
  if (hasVisibilityMapValue(lp.iid)) {
    return !!visibilityMap.value[String(lp.iid)] || shouldShowUnavailableLp(lp)
  }

  return isLocallyVisibleForStudent(lp)
}

const filteredItems = computed(() =>
  canEdit.value ? items.value : items.value.filter((lp) => isVisibleInStudentView(lp)),
)

const uncatList = ref([])
const categoryLayout = ref([])

function syncLayoutFromServer() {
  const learningPaths = filteredItems.value
  const byCategory = new Map()

  for (const learningPath of learningPaths) {
    const categoryId = Number(learningPath?.category?.iid ?? 0)

    if (categoryId > 0) {
      if (!byCategory.has(categoryId)) {
        byCategory.set(categoryId, [])
      }

      byCategory.get(categoryId).push(learningPath)
    }
  }

  uncatList.value = learningPaths.filter((learningPath) => !Number(learningPath?.category?.iid ?? 0))
  categoryLayout.value = (Array.isArray(categories.value) ? categories.value : [])
    .filter(Boolean)
    .map((category) => ({
      iid: Number(category.iid),
      category,
      isSessionCategory: hasSession(category),
      list: byCategory.get(Number(category.iid)) ?? [],
    }))
    .filter((group) => canEdit.value || group.list.length > 0)
}

watch([filteredItems, categories], () => {
  if (!layoutBusy.value) {
    syncLayoutFromServer()
  }
})

function syncStudentViewStateFromRoute() {
  if (null !== routeStudentViewFlag.value) {
    platformConfig.setStudentViewEnabled(routeStudentViewFlag.value)

    return
  }

  if (platformConfig.isStudentViewActive) {
    router.replace({
      name: route.name,
      params: route.params,
      query: { ...route.query, isStudentView: "true" },
    })
  }
}

onMounted(() => {
  syncStudentViewStateFromRoute()
})

watch(
  () => route.query?.isStudentView,
  () => {
    syncStudentViewStateFromRoute()
  },
)

const hasImageRF = (lp) => {
  const rfs = lp.resourceNode?.resourceFiles ?? lp.resourceFiles ?? []

  if (Array.isArray(rfs) && rfs.length) {
    return rfs.some((f) => f?.image === true)
  }

  return !!lp.firstResourceFile?.image
}

const onExportPdf = (lp) => {
  if (!canExportPdf.value) {
    return
  }

  exportTarget.value = lp
  showExportDialog.value = true
}

const onExportChamilo = async (lp) => {
  if (!canExportChamilo.value || !lp?.iid) {
    return
  }

  try {
    const { blob, filename } = await lpService.downloadChamiloBackup(lp.iid, {
      cid: legacyContext.value.cid || 0,
      sid: legacyContext.value.sid || 0,
      gid: legacyContext.value.gid || 0,
    })

    const url = window.URL.createObjectURL(blob)
    const link = document.createElement("a")
    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)

    showSuccessNotification(t("Download started"))
  } catch (error) {
    showErrorNotification(error?.message || t("An unexpected error occurred. Please try again later."))
  }
}
const onCloseExportDialog = () => {
  showExportDialog.value = false
  exportTarget.value = null
}

async function loadVisibilityFor(lpIds) {
  if (canEdit.value) {
    visibilityMap.value = {}
    return
  }

  if (!Array.isArray(lpIds) || lpIds.length === 0) {
    visibilityMap.value = {}
    return
  }

  const params = new URLSearchParams({
    a: "lp_visibility_map",
    lp_ids: lpIds.join(","),
    cid: legacyContext.value.cid,
  })

  if (legacyContext.value.sid) {
    params.append("sid", legacyContext.value.sid)
  }

  if (legacyContext.value.gid) {
    params.append("gid", legacyContext.value.gid)
  }

  const { data } = await api.get(`/main/inc/ajax/lp.ajax.php?${params.toString()}`).catch(() => ({ data: {} }))
  const map = data.map || {}
  visibilityMap.value = Object.fromEntries(Object.entries(map).map(([key, value]) => [String(key), value]))
}

const withCidSid = (url) => {
  if (!url) {
    return url
  }

  const [withoutHash, hash = ""] = url.split("#")
  const [path, rawQuery = ""] = withoutHash.split("?")
  const sp = new URLSearchParams(rawQuery)

  if (legacyContext.value.cid) {
    sp.set("cid", legacyContext.value.cid)
  }

  if (legacyContext.value.sid) {
    sp.set("sid", legacyContext.value.sid)
  }

  if (legacyContext.value.gid) {
    sp.set("gid", legacyContext.value.gid)
  }

  const qs = sp.toString()

  return path + (qs ? `?${qs}` : "") + (hash ? `#${hash}` : "")
}

const load = async (notifyOnError = true) => {
  loading.value = true
  let firstError = null

  try {
    let allowed = await checkIsAllowedToEdit(true, true, true, false)

    if (!allowed && securityStore.isAdmin) {
      allowed = true
    }

    rawCanEdit.value = !!allowed
    actionToken.value = ""
    allowChamiloExport.value = false

    if (rawCanEdit.value) {
      const tokenResult = await lpService.getActionToken({
        cid: legacyContext.value.cid,
        sid: legacyContext.value.sid ?? 0,
        gid: legacyContext.value.gid ?? 0,
      })
      actionToken.value = tokenResult?.token ?? ""
      allowChamiloExport.value = tokenResult?.allowChamiloExport === true
    }
  } catch (error) {
    firstError = error
  }

  try {
    categories.value = await lpService.getLpCategories({
      "resourceNode.parent": route.params?.node ?? 0,
      cid: legacyContext.value.cid,
      sid: legacyContext.value.sid ?? 0,
      gid: legacyContext.value.gid ?? 0,
      isStudentView: isStudentView.value ? "true" : "false",
    })

    const raw = await lpService.getLearningPaths({
      "resourceNode.parent": route.params?.node ?? 0,
      sid: legacyContext.value.sid ?? 0,
      gid: legacyContext.value.gid ?? 0,
      isStudentView: isStudentView.value ? "true" : "false",
      pagination: false,
    })

    items.value = raw.map((lp) => ({
      ...lp,
      coverUrl: hasImageRF(lp) && lp.contentUrl ? withCidSid(lp.contentUrl) : null,
    }))

    await loadVisibilityFor(items.value.map((lp) => lp.iid))
    syncLayoutFromServer()
  } catch (error) {
    firstError ??= error
  } finally {
    loading.value = false
  }

  if (firstError) {
    if (notifyOnError) {
      showErrorNotification(firstError)
    } else {
      console.error(firstError)
    }
  }
}

onMounted(async () => {
  exposeLegacyCidContext()
  await load()

  await nextTick()

  exposeLegacyCidContext()
  document.dispatchEvent(new CustomEvent(LP_LIST_LOADED))
})

watch(legacyContext, () => {
  exposeLegacyCidContext()
})

/**
 * @param {Object} cat
 * @returns {boolean}
 */
function hasSession(cat) {
  if (!cat?.resourceLinkListFromEntity) {
    return false
  }

  return (
    cat.resourceLinkListFromEntity.findIndex(
      (resourceLink) => resourceLink.session && resourceLink.session["@id"] === session.value?.["@id"],
    ) >= 0
  )
}

const canOrderCategories = computed(
  () =>
    canReorder.value &&
    categoryLayout.value.length > 1 &&
    categoryLayout.value.every((group) => Boolean(group.category?.reorderable)),
)

const hasAnyVisible = computed(() => {
  if (canEdit.value) {
    return items.value.length > 0 || categories.value.length > 0
  }

  return uncatList.value.length > 0 || categoryLayout.value.some((group) => group.list.length > 0)
})

function buildLayoutPayload() {
  return {
    uncategorized: uncatList.value.map((learningPath) => Number(learningPath.iid)),
    categories: categoryLayout.value.map((group) => ({
      id: Number(group.category.iid),
      learningPathIds: group.list.map((learningPath) => Number(learningPath.iid)),
    })),
    csrfToken: actionToken.value,
  }
}

function commitLocalLayout() {
  const orderedLearningPaths = []

  for (const learningPath of uncatList.value) {
    learningPath.category = null
    orderedLearningPaths.push(learningPath)
  }

  for (const group of categoryLayout.value) {
    for (const learningPath of group.list) {
      learningPath.category = group.category
      orderedLearningPaths.push(learningPath)
    }
  }

  items.value = orderedLearningPaths
  categories.value = categoryLayout.value.map((group) => group.category)
}

async function persistLayout() {
  if (!canReorder.value || layoutBusy.value) {
    return
  }

  layoutBusy.value = true

  try {
    await lpService.saveLayout(
      {
        cid: legacyContext.value.cid,
        sid: 0,
        gid: 0,
      },
      buildLayoutPayload(),
    )
    commitLocalLayout()
    showSuccessNotification(t("Updated"))
  } catch (error) {
    // HTTP failures are already reported by the global API handler. Keep a
    // local notification only for client-side errors and then restore the
    // persisted layout silently.
    if (!error?.response) {
      showErrorNotification(t("Could not save the new order."))
    }

    await load(false)
  } finally {
    layoutBusy.value = false
  }
}

async function queueLayoutSave() {
  if (layoutSaveQueued.value) {
    return
  }

  layoutSaveQueued.value = true

  try {
    await nextTick()
    await persistLayout()
  } finally {
    layoutSaveQueued.value = false
  }
}

async function onLearningPathDragEnd(event) {
  const didMove =
    event?.from !== event?.to ||
    Number(event?.oldIndex ?? -1) !== Number(event?.newIndex ?? -1)

  if (didMove) {
    await queueLayoutSave()
  }
}

async function onCategoryDragEnd(event) {
  const didMove = Number(event?.oldIndex ?? -1) !== Number(event?.newIndex ?? -1)

  if (didMove) {
    await queueLayoutSave()
  }
}

const fmt = new Intl.DateTimeFormat(undefined, { year: "numeric", month: "2-digit", day: "2-digit" })
const buildDates = (lp) => {
  const s = lp.publishedOn ? fmt.format(new Date(lp.publishedOn)) : ""
  const e = lp.expiredOn ? fmt.format(new Date(lp.expiredOn)) : ""

  if (!s && !e) {
    return t("No date")
  }

  if (s && e) {
    return `${s} - ${e}`
  }

  return s || e
}

const circumference = 2 * Math.PI * 16
const ringDash = (val) => {
  const n = Math.min(100, Math.max(0, Number(val || 0)))
  const d = (n / 100) * circumference
  return `${d} ${circumference}`
}
const ringValue = (val) => Math.round(Math.min(100, Math.max(0, Number(val || 0))))

watch(
  () => platformConfig.isStudentViewActive,
  async (val) => {
    const nextValue = val ? "true" : "false"

    if (String(route.query?.isStudentView ?? "") === nextValue) {
      return
    }

    await router.replace({
      name: route.name,
      params: route.params,
      query: { ...route.query, isStudentView: nextValue },
    })
  },
)

</script>
