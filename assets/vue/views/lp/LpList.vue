<template>
  <div class="lp-list">
    <SectionHeader :title="t('Learning paths')">
      <BaseButton
        :label="t('More actions')"
        icon="dots-vertical"
        only-icon
        popup-identifier="lp-list-tmenu"
        type="black"
        @click="mLpList.toggle($event)"
      />
      <BaseMenu
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
        <div v-if="uncatList.length">
          <Draggable
            v-model="uncatList"
            :animation="180"
            :disabled="!canEdit"
            chosen-class="chosen"
            class="space-y-6"
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
                :legacyContext="legacyContext"
                :lp="element"
                :ringDash="ringDash"
                :ringValue="ringValue"
                @export-pdf="onExportPdf"
              />
            </template>
          </Draggable>
        </div>
        <LpCategorySection
          v-for="group in categorizedGroups"
          :key="group?.[0]?.iid || group?.[0]?.title"
          :buildDates="buildDates"
          :canAutoLaunch="canAutoLaunch"
          :canEdit="canEdit"
          :canExportPdf="canExportPdf"
          :canExportScorm="canExportScorm"
          :category="group[0]"
          :isSessionCategory="group[2]"
          :list="group[1]"
          :ringDash="ringDash"
          :ringValue="ringValue"
          :title="group[0]?.title"
          @reorder="(ids) => onReorderCategory(group[0], ids)"
          @export-pdf="onExportPdf"
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

const { showErrorNotification } = useNotification()

const loading = ref(true)
const draggingUncat = ref(false)

const rawCanEdit = ref(false)
const isStudentView = computed(() => route.query?.isStudentView === "true")
const canEdit = computed(() => rawCanEdit.value && !isStudentView.value)

const mLpList = ref(null)
const mItems = computed(() => {
  const ctx = legacyContext.value

  const uploadParams = new URLSearchParams({
    cid: ctx.cid,
    sid: ctx.sid,
    tool: "learnpath",
    curdirpath: "/",
    node: ctx.node,
    gid: ctx.gid,
    gradebook: ctx.gradebook,
    origin: ctx.origin,
  }).toString()

  return [
    { label: t("Create new learning path"), url: lpService.buildLegacyActionUrl("add_lp", ctx) },
    ...(canUseAi.value
      ? [{ label: t("AI learning path generator"), url: lpService.buildLegacyActionUrl("ai_helper", ctx) }]
      : []),
    { label: t("Import"), url: `/main/upload/index.php?${uploadParams}` },
    { label: t("Chamilo RAPID"), url: `/main/upload/upload_ppt.php?${uploadParams}` },
    { label: t("Add category"), url: lpService.buildLegacyActionUrl("add_lp_category", ctx) },
  ]
})

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

const canUseAi = computed(
  () =>
    canEdit.value &&
    String(platformConfig.getSetting("ai_helpers.enable_ai_helpers")) === "true" &&
    String(courseSettingsStore?.getSetting?.("learning_path_generator")) === "true",
)

const showExportDialog = ref(false)
const exportTarget = ref(null)

const canExportScorm = computed(
  () => canEdit.value && platformConfig.getSetting("lp.hide_scorm_export_link") !== "true",
)

const canExportPdf = computed(() => platformConfig.getSetting("lp.hide_scorm_pdf_link") !== "true")

// Uses a click handler instead of :to-url so the URL (including cid/sid) is
// built at click time, when the course store is guaranteed to be populated.
// A static :to-url binding can render before the store resolves, producing a
// link without cid that the legacy controller rejects as "Not allowed".
const goCreateLp = () => {
  window.location.assign(lpService.buildLegacyActionUrl("add_lp", { ...legacyContext.value }))
}

const canAutoLaunch = computed(() => {
  if (!canEdit.value) {
    return false
  }

  const val = courseSettingsStore?.getSetting?.("enable_lp_auto_launch")

  return String(val) === "true" || Number(val) === 1
})

const items = ref([])
const categories = ref([])
const visibilityMap = ref({})

const filteredItems = computed(() =>
  canEdit.value ? items.value : items.value.filter((lp) => !!visibilityMap.value[lp.iid]),
)

const uncatList = ref([])

watch(
  filteredItems,
  (lps) => {
    if (!draggingUncat.value) {
      uncatList.value = lps.filter((lp) => !lp.category?.iid)
    }
  },
  { immediate: true },
)

const catLists = computed(() => {
  const byCat = {}

  for (const lp of filteredItems.value) {
    const catId = lp.category?.iid

    if (catId) {
      if (!byCat[catId]) {
        byCat[catId] = []
      }

      byCat[catId].push(lp)
    }
  }

  return byCat
})

onMounted(() => {
  platformConfig.setStudentViewEnabled(route.query?.isStudentView === "true")
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

  const { data } = await api.get(`/main/inc/ajax/lp.ajax.php?${params.toString()}`).catch(() => ({ data: {} }))
  visibilityMap.value = data.map || {}
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

  const qs = sp.toString()

  return path + (qs ? `?${qs}` : "") + (hash ? `#${hash}` : "")
}

const load = async () => {
  loading.value = true

  try {
    let allowed = await checkIsAllowedToEdit(true, true, true, false)

    if (!allowed && securityStore.isAdmin) {
      allowed = true
    }

    rawCanEdit.value = !!allowed
  } catch (e) {
    showErrorNotification(e)
  }

  try {
    categories.value = await lpService.getLpCategories({
      cid: legacyContext.value.cid,
      sid: legacyContext.value.sid ?? 0,
    })

    const raw = await lpService.getLearningPaths({
      "resourceNode.parent": route.params?.node ?? 0,
      sid: legacyContext.value.sid ?? 0,
      pagination: false,
    })

    items.value = raw.map((lp) => ({
      ...lp,
      coverUrl: hasImageRF(lp) && lp.contentUrl ? withCidSid(lp.contentUrl) : null,
    }))

    await loadVisibilityFor(items.value.map((lp) => lp.iid))
  } catch (e) {
    showErrorNotification(e)
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await load()

  await nextTick()

  document.dispatchEvent(new CustomEvent(LP_LIST_LOADED))
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

const categorizedGroups = computed(() => {
  const cats = Array.isArray(categories.value) ? categories.value : []
  const rows = []

  for (const cat of cats) {
    if (!cat) {
      continue
    }

    const list = cat.iid ? (catLists.value[cat.iid] ?? []) : []
    const safeList = Array.isArray(list) ? list : []
    const isSessionCategory = hasSession(cat)

    if (canEdit.value || safeList.length) {
      rows.push([cat, safeList, isSessionCategory])
    }
  }

  return rows
})

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
}

async function sendReorder(orderedIds, { categoryId } = {}) {
  await lpService.reorder({
    courseId: legacyContext.value.cid,
    sessionId: legacyContext.value.sid,
    categoryId: categoryId ?? null,
    ids: orderedIds,
  })
}

async function onReorderCategory(cat, ids) {
  await nextTick()

  applyOrderWithinContext((lp) => lp.category && lp.category.iid === cat.iid, ids)

  try {
    await sendReorder(ids, { categoryId: cat.iid })
  } catch (e) {
    showErrorNotification(e)

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

watch(
  () => platformConfig.isStudentViewActive,
  async (val) => {
    await router.replace({
      name: route.name,
      params: route.params,
      query: { ...route.query, isStudentView: val ? "true" : "false" },
    })
  },
)

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
    showErrorNotification(e)

    await load()
    alert(t("Could not save the new order."))
  } finally {
    draggingUncat.value = false
  }
}
</script>
