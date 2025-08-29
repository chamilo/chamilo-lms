<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-90">{{ t("Learning Path") }}</h1>
      <div class="relative flex items-center gap-2">
        <StudentViewButton
          :key="route.query.isStudentView === 'true' ? 'sv-on' : 'sv-off'"
          @change="onStudentViewChange"
        />
        <BaseDropdownMenu
          v-if="canEdit"
          class="relative flex items-center gap-2"
          :dropdown-id="'top-menu'"
        >
          <template #button>
            <button
              class="w-9 h-9 rounded-xl border border-gray-25 grid place-content-center hover:bg-gray-15"
              :aria-label="t('More actions')"
            >
              <i
                class="mdi mdi-dots-vertical text-lg"
                aria-hidden
              ></i>
            </button>
          </template>
          <template #menu>
            <div class="absolute right-0 z-50 w-56 bg-white border border-gray-25 rounded-xl shadow-md p-1">
              <button
                class="w-full text-left px-3 py-2 rounded hover:bg-gray-15"
                @click="handleTopMenu('new', $event)"
              >
                {{ t("New learning path") }}
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
      v-else-if="items.length === 0"
      class="flex flex-col items-center justify-center py-20 text-center"
    >
      <div class="w-24 h-24 rounded-full bg-support-1 flex items-center justify-center mb-4 text-support-3">
        <svg
          width="36"
          height="36"
          viewBox="0 0 24 24"
          fill="none"
        >
          <path
            d="M4 17l6-6 4 4 6-6"
            stroke="currentColor"
            stroke-width="2"
          />
        </svg>
      </div>
      <h3 class="text-base font-semibold">{{ t("You don't have learning paths.") }}</h3>
      <p class="text-body-2 text-gray-50 max-w-sm">
        {{ t("Create your first learning path to start organizing course content.") }}
      </p>
      <button
        class="mt-4 px-4 py-2 border border-gray-25 rounded-xl text-gray-90 hover:bg-gray-15"
        @click="handleTopMenu('new', $event)"
      >
        + {{ t("New Learning Path") }}
      </button>
    </div>

    <template v-else>
      <div v-if="uncatList.length">
        <Draggable
          v-model="uncatList"
          item-key="iid"
          :disabled="!canEdit"
          handle=".drag-handle"
          :animation="180"
          tag="div"
          class="space-y-3"
          ghost-class="ghosting"
          chosen-class="chosen"
          drag-class="dragging"
          @start="draggingUncat = true"
          @end="onEndUncat"
        >
          <template #item="{ element }">
            <LpRowItem
              :lp="element"
              :canEdit="canEdit"
              :buildDates="buildDates"
              :ringDash="ringDash"
              :ringValue="ringValue"
              :canExportScorm="canExportScorm"
              @open="openLegacy"
              @edit="goEdit"
              @report="onReport"
              @settings="onSettings"
              @build="onBuild"
              @toggle-visible="onToggleVisible"
              @toggle-publish="onTogglePublish"
              @delete="onDelete"
              @export-scorm="onExportScorm"
            />
          </template>
        </Draggable>
      </div>

      <LpCategorySection
        v-for="[cat, list] in categorizedGroups"
        :key="cat.iid || cat.title"
        :title="cat.title"
        :category="cat"
        :list="list"
        :canEdit="canEdit"
        :ringDash="ringDash"
        :ringValue="ringValue"
        :canExportScorm="canExportScorm"
        @open="openLegacy"
        @edit="goEdit"
        @report="onReport"
        @settings="onSettings"
        @build="onBuild"
        @toggle-visible="onToggleVisible"
        @toggle-publish="onTogglePublish"
        @delete="onDelete"
        @export-scorm="onExportScorm"
        @reorder="(ids) => onReorderCategory(cat, ids)"
      />
    </template>
  </div>
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

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const cidReq = useCidReqStore()
const platformConfig = usePlatformConfig()
const courseSettingsStore = useCourseSettings()
const securityStore = useSecurityStore()

const loading = ref(true)
const error = ref(null)
const draggingUncat = ref(false)

const rawCanEdit = ref(false)
const isStudentView = computed(() => route.query?.isStudentView === "true")
const canEdit = computed(() => rawCanEdit.value && !isStudentView.value)

const cid = computed(() => Number(route.query?.cid ?? 0))
const sid = computed(() => Number(route.query?.sid ?? 0) || 0)

const aiHelpersEnabled = computed(() => {
  const v = String(platformConfig.getSetting("ai_helpers.enable_ai_helpers"))
  return v === "true"
})
const lpGeneratorEnabled = computed(() => {
  const v = String(courseSettingsStore?.getSetting?.("learning_path_generator"))
  return v === "true"
})
const canUseAi = computed(() => {
  return !!(canEdit.value && aiHelpersEnabled.value && lpGeneratorEnabled.value)
})

const canExportScorm = computed(() => {
  const isScormEnabled = platformConfig.getSetting("hide_scorm_export_link") !== "true"
  return canEdit.value && isScormEnabled
})

const items = ref([])
const categories = ref([])
const uncatList = ref([])
const catLists = ref({})

onMounted(() => {
  platformConfig.studentView = route.query?.isStudentView === "true"
})

const hasImageRF = (lp) => {
  const rfs = lp.resourceNode?.resourceFiles ?? lp.resourceFiles ?? []
  if (Array.isArray(rfs) && rfs.length) return rfs.some((f) => f?.image === true)
  if (lp.firstResourceFile?.image) return true
  return false
}

const load = async () => {
  loading.value = true
  error.value = null
  try {
    const node = Number(route.params.node)
    const course = Number(route.query?.cid ?? 0)
    const sess = Number(route.query?.sid ?? 0) || 0

    await cidReq.setCourseAndSessionById(course, sess)

    try {
      await courseSettingsStore.loadCourseSettings(course, sess)
    } catch (err) {
      console.error("[LPList] loadCourseSettings FAILED:", err)
    }

    let allowed = await checkIsAllowedToEdit(true, true, true, false)
    const roles = securityStore.user?.roles ?? []
    if (!allowed && Array.isArray(roles) && (roles.includes("ROLE_ADMIN") || roles.includes("ROLE_SUPER_ADMIN"))) {
      allowed = true
    }
    rawCanEdit.value = !!allowed

    const cats = await lpService.getLpCategories({
      "resourceNode.parent": node,
      sid: sess || undefined,
      pagination: false,
    })
    categories.value = cats["hydra:member"] ?? cats ?? []

    const res = await lpService.getLearningPaths({
      "resourceNode.parent": node,
      sid: sess || undefined,
      pagination: false,
    })
    const raw = res["hydra:member"] ?? res ?? []
    items.value = raw.map((lp) => ({
      ...lp,
      coverUrl: hasImageRF(lp) && lp.contentUrl ? lp.contentUrl : null,
    }))

    rebuildListsFromItems()
  } catch (e) {
    console.error(e)
    error.value = e
  } finally {
    loading.value = false
  }
}
onMounted(load)

const categorizedGroups = computed(() => {
  const rows = []
  for (const cat of categories.value) {
    rows.push([cat, catLists.value[cat.iid] ?? []])
  }
  return rows
})

function rebuildListsFromItems() {
  const uncat = []
  const byCat = {}
  for (const lp of items.value) {
    const catId = lp.category?.iid
    if (!catId) {
      uncat.push(lp)
    } else {
      if (!byCat[catId]) byCat[catId] = []
      byCat[catId].push(lp)
    }
  }
  uncatList.value = uncat
  catLists.value = byCat
}

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
  const courseId = Number(cid.value)
  const sessionId = Number(sid.value) || null

  if (lpService?.reorder) {
    await lpService.reorder({ courseId, sessionId, categoryId: categoryId ?? null, ids: orderedIds })
  } else {
    const resp = await fetch("/api/learning_paths/reorder", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ courseId, sid: sessionId, categoryId: categoryId ?? null, order: orderedIds }),
    })
    if (!resp.ok) {
      const txt = await resp.text().catch(() => "")
      throw new Error(`Reorder failed: ${resp.status} ${txt}`)
    }
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
  if (!s && !e) return t("No date")
  if (s && e) return `${s} - ${e}`
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
    cid: Number(route.query?.cid ?? 0),
    sid: Number(route.query?.sid ?? 0) || 0,
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
  if (!canEdit.value) return

  const courseId = Number(route.query?.cid ?? 0) || undefined
  const sidQ = Number(route.query?.sid ?? 0) || 0
  const node = Number(route.params?.node ?? 0) || undefined
  const gid = Number(route.query?.gid ?? 0)
  const gradebook = Number(route.query?.gradebook ?? 0)
  const origin = String(route.query?.origin ?? "")

  const url =
    action === "new"
      ? lpService.buildLegacyActionUrl("add_lp", { cid: courseId, sid: sidQ, node, gid, gradebook, origin })
      : action === "category"
        ? lpService.buildLegacyActionUrl("add_lp_category", { cid: courseId, sid: sidQ, node, gid, gradebook, origin })
        : action === "import"
          ? `/main/upload/index.php?${new URLSearchParams({ cid: courseId, sid: sidQ, tool: "learnpath", curdirpath: "/", node, gid, gradebook, origin }).toString()}`
          : action === "rapid"
            ? `/main/upload/upload_ppt.php?${new URLSearchParams({ cid: courseId, sid: sidQ, tool: "learnpath", curdirpath: "/", node, gid, gradebook, origin }).toString()}`
            : action === "ai"
              ? lpService.buildLegacyActionUrl("ai_helper", { cid: courseId, sid: sidQ, node, gid, gradebook, origin })
              : null

  if (url) window.location.assign(url)
}

const onReport = (lp) =>
  (window.location.href = lpService.buildLegacyActionUrl(lp.iid, "report", { cid: cid.value, sid: sid.value }))
const onSettings = (lp) =>
  (window.location.href = lpService.buildLegacyActionUrl(lp.iid, "edit", { cid: cid.value, sid: sid.value }))
const onBuild = (lp) =>
  (window.location.href = lpService.buildLegacyActionUrl(lp.iid, "add_item", {
    cid: cid.value,
    sid: sid.value,
    params: { type: "step", isStudentView: "false" },
  }))
const onToggleVisible = (lp) => {
  const newStatus = typeof lp.visible !== "undefined" ? (lp.visible ? 0 : 1) : 1
  window.location.href = lpService.buildLegacyActionUrl(lp.iid, "toggle_visible", {
    cid: cid.value,
    sid: sid.value,
    params: { new_status: newStatus },
  })
}
const onTogglePublish = (lp) => {
  const newStatus = lp.published === "v" ? "i" : "v"
  window.location.href = lpService.buildLegacyActionUrl(lp.iid, "toggle_publish", {
    cid: cid.value,
    sid: sid.value,
    params: { new_status: newStatus },
  })
}
const onDelete = (lp) => {
  const label = (lp.title || "").trim() || t("Learning path")
  const msg = `${t("Are you sure to delete:")} ${label}?`
  if (confirm(msg)) {
    window.location.href = lpService.buildLegacyActionUrl(lp.iid, "delete", { cid: cid.value, sid: sid.value })
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
  if (!canExportScorm.value) return
  const params = new URLSearchParams({ action: "export", lp_id: lp.iid, cid: cid.value })
  if (sid.value) params.append("sid", sid.value)
  const exportUrl = `/main/lp/lp_controller.php?${params.toString()}`
  window.location.href = exportUrl
}
</script>
