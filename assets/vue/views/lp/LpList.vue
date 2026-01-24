<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-90">{{ t("Learning path") }}</h1>
      <div class="relative flex items-center gap-2">
        <StudentViewButton
          :key="route.query.isStudentView === 'true' ? 'sv-on' : 'sv-off'"
          @change="onStudentViewChange"
        />
        <BaseDropdownMenu
          v-if="canEdit"
          :dropdown-id="'top-menu'"
          class="relative flex items-center gap-2"
        >
          <template #button>
            <button
              :aria-label="t('More actions')"
              class="w-9 h-9 rounded-xl border border-gray-25 grid place-content-center hover:bg-gray-15"
            >
              <i
                aria-hidden="true"
                class="mdi mdi-dots-vertical text-lg"
              ></i>
            </button>
          </template>
          <template #menu>
            <div class="absolute right-0 z-50 w-56 bg-white border border-gray-25 rounded-xl shadow-md p-1">
              <button
                class="w-full text-left px-3 py-2 rounded hover:bg-gray-15"
                @click="handleTopMenu('new', $event)"
              >
                {{ t("Create new learning path") }}
              </button>
              <button
                v-if="canUseAi"
                class="w-full text-left px-3 py-2 rounded hover:bg-gray-15"
                @click="handleTopMenu('ai', $event)"
              >
                {{ t("AI learning path generator") }}
              </button>
              <button
                class="w-full text-left px-3 py-2 rounded hover:bg-gray-15"
                @click="handleTopMenu('import', $event)"
              >
                {{ t("Import") }}
              </button>
              <button
                class="w-full text-left px-3 py-2 rounded hover:bg-gray-15"
                @click="handleTopMenu('rapid', $event)"
              >
                {{ t("Chamilo RAPID") }}
              </button>
              <button
                class="w-full text-left px-3 py-2 rounded hover:bg-gray-15"
                @click="handleTopMenu('category', $event)"
              >
                {{ t("Add category") }}
              </button>
            </div>
          </template>
        </BaseDropdownMenu>
      </div>
    </div>

    <div
      v-if="loading"
      class="space-y-4 animate-pulse"
    >
      <div class="mt-4">
        <div class="h-36 bg-gray-15 rounded-2xl" />
      </div>
    </div>

    <div
      v-else-if="error"
      class="text-body-2 text-danger"
    >
      {{ t("Error loading learning paths.") }}
    </div>

    <div
      v-else-if="!hasAnyVisible"
      class="flex flex-col items-center justify-center py-20 text-center"
    >
      <div class="w-24 h-24 rounded-full bg-support-1 flex items-center justify-center mb-4 text-support-3">
        <svg
          fill="none"
          height="36"
          viewBox="0 0 24 24"
          width="36"
        >
          <path
            d="M4 17l6-6 4 4 6-6"
            stroke="currentColor"
            stroke-width="2"
          />
        </svg>
      </div>
      <h3 class="text-base font-semibold">{{ t("You don't have any learning path.") }}</h3>
      <p
        v-if="canEdit"
        class="text-body-2 text-gray-50 max-w-sm"
      >
        {{ t("Create your first learning path to start organizing course content.") }}
      </p>
      <button
        v-if="canEdit"
        class="mt-4 px-4 py-2 border border-gray-25 rounded-xl text-gray-90 hover:bg-gray-15"
        @click="handleTopMenu('new', $event)"
      >
        + {{ t("Create new learning path") }}
      </button>
    </div>

    <template v-else>
      <div v-if="uncatList.length">
        <Draggable
          v-model="uncatList"
          :animation="180"
          :disabled="!canEdit"
          chosen-class="chosen"
          class="space-y-3"
          drag-class="dragging"
          ghost-class="ghosting"
          handle=".drag-handle"
          item-key="iid"
          tag="div"
          @end="onEndUncat"
          @start="draggingUncat = true"
        >
          <template #item="{ element }">
            <LpRowItem
              :buildDates="buildDates"
              :canAutoLaunch="canAutoLaunch"
              :canEdit="canEdit"
              :canExportPdf="canExportPdf"
              :canExportScorm="canExportScorm"
              :lp="element"
              :ringDash="ringDash"
              :ringValue="ringValue"
              @build="onBuild"
              @delete="onDelete"
              @edit="goEdit"
              @open="openLegacy"
              @report="onReport"
              @settings="onSettings"
              @toggle-auto-launch="onToggleAutoLaunch"
              @toggle-visible="onToggleVisible"
              @toggle-publish="onTogglePublish"
              @export-scorm="onExportScorm"
              @export-pdf="onExportPdf"
              @update-scorm="onUpdateScorm"
            />
          </template>
        </Draggable>
      </div>
      <LpCategorySection
        v-for="group in categorizedGroups"
        :key="group?.[0]?.iid || group?.[0]?.title"
        :category="group[0]"
        :list="group[1]"
        :isSessionCategory="group[2]"
        :buildDates="buildDates"
        :canAutoLaunch="canAutoLaunch"
        :canEdit="canEdit"
        :canExportPdf="canExportPdf"
        :canExportScorm="canExportScorm"
        :ringDash="ringDash"
        :ringValue="ringValue"
        :title="group[0]?.title"
        @build="onBuild"
        @delete="onDelete"
        @edit="goEdit"
        @open="openLegacy"
        @reorder="(ids) => onReorderCategory(group[0], ids)"
        @report="onReport"
        @settings="onSettings"
        @toggle-auto-launch="onToggleAutoLaunch"
        @toggle-visible="onToggleVisible"
        @toggle-publish="onTogglePublish"
        @export-pdf="onExportPdf"
        @export-scorm="onExportScorm"
        @update-scorm="onUpdateScorm"
      />
    </template>
  </div>
  <ExportPdfDialog
    v-if="showExportDialog && exportTarget"
    :cid="course?.id"
    :lp-id="exportTarget.iid"
    :show="showExportDialog"
    :sid="session?.id"
    @close="onCloseExportDialog"
  />
</template>

<script setup>
import { computed, nextTick, onMounted, ref } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useCidReqStore } from "../../store/cidReq"
import { checkIsAllowedToEdit } from "../../composables/userPermissions"
import lpService from "../../services/lpService"
import { useSecurityStore } from "../../store/securityStore"
import { usePlatformConfig } from "../../store/platformConfig"
import Draggable from "vuedraggable"
import LpRowItem from "../../components/lp/LpRowItem.vue"
import LpCategorySection from "../../components/lp/LpCategorySection.vue"
import StudentViewButton from "../../components/StudentViewButton.vue"
import { useI18n } from "vue-i18n"
import BaseDropdownMenu from "../../components/basecomponents/BaseDropdownMenu.vue"
import { useCourseSettings } from "../../store/courseSettingStore"
import ExportPdfDialog from "../../components/lp/ExportPdfDialog.vue"
import { storeToRefs } from "pinia"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const cidReqStore = useCidReqStore()
const platformConfig = usePlatformConfig()
const courseSettingsStore = useCourseSettings()
const securityStore = useSecurityStore()

const loading = ref(true)
const error = ref(null)
const draggingUncat = ref(false)

const rawCanEdit = ref(false)
const isStudentView = computed(() => route.query?.isStudentView === "true")
const canEdit = computed(() => rawCanEdit.value && !isStudentView.value)

const { course, session } = storeToRefs(cidReqStore)

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

const aiHelpersEnabled = computed(() => {
  const v = String(platformConfig.getSetting("ai_helpers.enable_ai_helpers"))
  return v === "true"
})
const lpGeneratorEnabled = computed(() => {
  const v = String(courseSettingsStore?.getSetting?.("learning_path_generator"))
  return v === "true"
})
const canUseAi = computed(() => !!(canEdit.value && aiHelpersEnabled.value && lpGeneratorEnabled.value))

const showExportDialog = ref(false)
const exportTarget = ref(null)

const canExportScorm = computed(() => {
  const isScormEnabled = platformConfig.getSetting("lp.hide_scorm_export_link") !== "true"
  return canEdit.value && isScormEnabled
})

const canExportPdf = computed(() => {
  const hidden = platformConfig.getSetting("lp.hide_scorm_pdf_link") === "true"
  return !hidden
})

// --- Auto-launch enable (course setting) ---
const enableLpAutoLaunch = computed(() => {
  const val = courseSettingsStore?.getSetting?.("enable_lp_auto_launch")
  return String(val) === "true" || Number(val) === 1
})
const canAutoLaunch = computed(() => canEdit.value && enableLpAutoLaunch.value)
// Toggle Auto-launch (rocket)
const onToggleAutoLaunch = (lp) => {
  if (!canAutoLaunch.value || !lp?.iid) {
    return
  }

  const next = Number(lp.autolaunch) === 1 ? 0 : 1

  window.location.href = lpService.buildLegacyActionUrl(lp.iid, "auto_launch", {
    cid: legacyContext.value.cid,
    sid: legacyContext.value.sid,
    node: legacyContext.value.node,
    gid: legacyContext.value.gid,
    gradebook: legacyContext.value.gradebook,
    origin: legacyContext.value.origin,
    params: { status: next },
  })
}

const items = ref([])
const categories = ref([])
const uncatList = ref([])
const catLists = ref({})
const visibilityMap = ref({})

onMounted(() => {
  platformConfig.studentView = route.query?.isStudentView === "true"
})

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

  const res = await fetch(`/main/inc/ajax/lp.ajax.php?${params.toString()}`, {
    headers: { "X-Requested-With": "XMLHttpRequest" },
    credentials: "same-origin",
  })

  const data = await res.json().catch(() => ({}))
  visibilityMap.value = data.map || {}
}

function isVisibleForStudent(lp) {
  if (canEdit.value) {
    return true
  }

  return !!visibilityMap.value[lp.iid]
}

const withCidSid = (url) => {
  if (!url) {
    return url
  }

  try {
    const isAbs = url.startsWith("http://") || url.startsWith("https://")
    const abs = isAbs ? url : window.location.origin + url
    const u = new URL(abs)

    if (legacyContext.value.cid) {
      u.searchParams.set("cid", legacyContext.value.cid)
    }

    if (legacyContext.value.sid) {
      u.searchParams.set("sid", legacyContext.value.sid)
    }

    return isAbs ? u.toString() : u.pathname + u.search
  } catch {
    return url
  }
}

const load = async () => {
  loading.value = true
  error.value = null

  try {
    const node = Number(route.params.node)

    try {
      await courseSettingsStore.loadCourseSettings(legacyContext.value.cid, legacyContext.value.sid)
    } catch (err) {
      console.error("[LPList] loadCourseSettings FAILED:", err)
    }

    let allowed = await checkIsAllowedToEdit(true, true, true, false)
    const roles = securityStore.user?.roles ?? []

    if (!allowed && Array.isArray(roles) && (roles.includes("ROLE_ADMIN") || roles.includes("ROLE_GLOBAL_ADMIN"))) {
      allowed = true
    }

    rawCanEdit.value = !!allowed

    const catRes = await lpService.getLpCategories({
      cid: legacyContext.value.cid,
      sid: legacyContext.value.sid ?? 0,
    })

    const cats = catRes?.["hydra:member"] ?? catRes ?? []
    categories.value = Array.isArray(cats) ? cats : []

    const res = await lpService.getLearningPaths({
      "resourceNode.parent": node,
      sid: legacyContext.value.sid ?? 0,
      pagination: false,
    })

    const raw = res["hydra:member"] ?? res ?? []
    items.value = raw.map((lp) => ({
      ...lp,
      coverUrl: hasImageRF(lp) && lp.contentUrl ? withCidSid(lp.contentUrl) : null,
    }))

    await loadVisibilityFor(items.value.map((lp) => lp.iid))
    rebuildListsFromItems()
  } catch (e) {
    console.error(e)
    error.value = e
  } finally {
    loading.value = false
  }
}
onMounted(load)

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

const categorizedGroups = computed(() => {
  const cats = Array.isArray(categories.value) ? categories.value : []
  const rows = []

  for (const cat of cats) {
    if (!cat) continue

    const list = catLists.value && cat.iid ? (catLists.value[cat.iid] ?? []) : []
    const safeList = Array.isArray(list) ? list : []
    const isSessionCategory = hasSession(cat)

    if (canEdit.value || safeList.length) {
      rows.push([cat, safeList, isSessionCategory])
    }
  }

  return rows
})

function rebuildListsFromItems() {
  const source = canEdit.value ? items.value : items.value.filter(isVisibleForStudent)
  const uncat = []
  const byCat = {}

  for (const lp of source) {
    const catId = lp.category?.iid

    if (!catId) {
      uncat.push(lp)
    } else {
      if (!byCat[catId]) {
        byCat[catId] = []
      }
      byCat[catId].push(lp)
    }
  }

  uncatList.value = uncat
  catLists.value = byCat
}

const hasAnyVisible = computed(() => {
  if (canEdit.value) {
    return items.value.length > 0
  }

  const anyUncat = uncatList.value.length > 0
  const anyCat = Object.values(catLists.value).some((arr) => Array.isArray(arr) && arr.length > 0)

  return anyUncat || anyCat
})

function applyOrderWithinContext(predicate, orderedIds) {
  const originalIndex = new Map(items.value.map((it, i) => [it.iid, i]))
  const rank = new Map(orderedIds.map((id, i) => [id, i]))
  items.value = items.value.slice().sort((a, b) => {
    const aIn = !!predicate(a)
    const bIn = !!predicate(b)

    if (aIn && bIn) {
      return (rank.get(a.iid) ?? 0) - (rank.get(b.iid) ?? 0)
    }

    return originalIndex.get(a.iid) - originalIndex.get(b.iid)
  })
  rebuildListsFromItems()
}

async function sendReorder(orderedIds, { categoryId } = {}) {
  const payload = {
    courseId: legacyContext.value.cid,
    sessionId: legacyContext.value.sid,
    sid: legacyContext.value.sid,
    categoryId: categoryId ?? null,
    ids: orderedIds,
    order: orderedIds,
  }

  if (lpService?.reorder) {
    await lpService.reorder(payload)
    return
  }

  const resp = await fetch("/api/learning_paths/reorder", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  })

  if (!resp.ok) {
    const txt = await resp.text().catch(() => "")
    throw new Error(`Reorder failed: ${resp.status} ${txt}`)
  }
}

async function onReorderCategory(cat, ids) {
  await nextTick()

  applyOrderWithinContext((lp) => lp.category && lp.category.iid === cat.iid, ids)

  try {
    await sendReorder(ids, { categoryId: cat.iid })
  } catch (e) {
    console.error(e)
    await load()
    alert(t("Could not save the new category order."))
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

const onStudentViewChange = async (val) => {
  if (val) {
    await router.replace({
      name: route.name,
      params: route.params,
      query: { ...route.query, isStudentView: "true" },
    })
  } else {
    const q = new URLSearchParams(window.location.search)
    q.delete("isStudentView")

    const newUrl = window.location.pathname + (q.toString() ? "?" + q.toString() : "") + window.location.hash
    window.location.replace(newUrl)
  }
}

const openLegacy = (lp) => {
  window.location.href = lpService.buildLegacyViewUrl(lp.iid, {
    cid: legacyContext.value.cid || 0,
    sid: legacyContext.value.sid || 0,
    isStudentView: isStudentView.value ? "true" : "false",
  })
}
const goEdit = (lp) => {
  router.push({ name: "LpUpdate", params: { id: lp.iid }, query: route.query })
}

const handleTopMenu = (action, ev) => {
  ev?.preventDefault?.()
  ev?.stopPropagation?.()
  ev?.stopImmediatePropagation?.()

  if (!canEdit.value) {
    return
  }

  const url =
    action === "new"
      ? lpService.buildLegacyActionUrl("add_lp", { ...legacyContext.value })
      : action === "category"
        ? lpService.buildLegacyActionUrl("add_lp_category", { ...legacyContext.value })
        : action === "import"
          ? `/main/upload/index.php?${new URLSearchParams({
              cid: legacyContext.value.cid,
              sid: legacyContext.value.sid,
              tool: "learnpath",
              curdirpath: "/",
              node: legacyContext.value.node,
              gid: legacyContext.value.gid,
              gradebook: legacyContext.value.gradebook,
              origin: legacyContext.value.origin,
            }).toString()}`
          : action === "rapid"
            ? `/main/upload/upload_ppt.php?${new URLSearchParams({
                cid: legacyContext.value.cid,
                sid: legacyContext.value.sid,
                tool: "learnpath",
                curdirpath: "/",
                node: legacyContext.value.node,
                gid: legacyContext.value.gid,
                gradebook: legacyContext.value.gradebook,
                origin: legacyContext.value.origin,
              }).toString()}`
            : action === "ai"
              ? lpService.buildLegacyActionUrl("ai_helper", { ...legacyContext.value })
              : null

  if (url) {
    window.location.assign(url)
  }
}

const onReport = (lp) => {
  window.location.href = lpService.buildLegacyActionUrl(lp.iid, "report", { ...legacyContext.value })
}
const onSettings = (lp) => {
  window.location.href = lpService.buildLegacyActionUrl(lp.iid, "edit", { ...legacyContext.value })
}
const onBuild = (lp) => {
  window.location.href = lpService.buildLegacyActionUrl(lp.iid, "add_item", {
    ...legacyContext.value,
    params: { type: "step", isStudentView: "false" },
  })
}

function onUpdateScorm(lp) {
  const node = Number(route.params?.node ?? 0) || undefined
  const url = lpService.buildLegacyActionUrl("update_scorm", {
    cid: course.value?.id,
    sid: session.value?.id ?? 0,
    node,
    params: { lp_id: lp.iid },
  })
  window.location.assign(url)
}

const onToggleVisible = (lp) => {
  const newStatus = typeof lp.visible !== "undefined" ? (lp.visible ? 0 : 1) : 1

  window.location.href = lpService.buildLegacyActionUrl(lp.iid, "toggle_visible", {
    ...legacyContext.value,
    params: { new_status: newStatus },
  })
}
const onTogglePublish = (lp) => {
  const newStatus = lp.published === "v" ? "i" : "v"

  window.location.href = lpService.buildLegacyActionUrl(lp.iid, "toggle_publish", {
    ...legacyContext.value,
    params: { new_status: newStatus },
  })
}
const onDelete = (lp) => {
  const label = (lp.title || "").trim() || t("Learning path")
  const msg = `${t("Are you sure to delete")} ${label}?`

  if (confirm(msg)) {
    window.location.href = lpService.buildLegacyActionUrl(lp.iid, "delete", { ...legacyContext.value })
  }
}

async function onEndUncat() {
  await nextTick()
  if (!canEdit.value) {
    draggingUncat.value = false
    return
  }

  const ids = uncatList.value.map((it) => it.iid)
  applyOrderWithinContext((lp) => !lp.category || !lp.category.iid, ids)

  try {
    await sendReorder(ids, { categoryId: null })
  } catch (e) {
    console.error(e)
    await load()
    alert(t("Could not save the new order."))
  } finally {
    draggingUncat.value = false
  }
}

const onExportScorm = (lp) => {
  if (!canExportScorm.value) {
    return
  }

  const params = new URLSearchParams({
    action: "export",
    lp_id: lp.iid,
    cid: legacyContext.value.cid,
  })

  // include sid even if 0 is not needed here; only append if > 0
  if (legacyContext.value.sid) {
    params.append("sid", String(legacyContext.value.sid))
  }

  if (legacyContext.value.node) params.set("node", String(legacyContext.value.node))
  params.set("gid", String(legacyContext.value.gid || 0))
  params.set("gradebook", String(legacyContext.value.gradebook || 0))
  if (legacyContext.value.origin) params.set("origin", legacyContext.value.origin)

  window.location.href = `/main/lp/lp_controller.php?${params.toString()}`
}
</script>
