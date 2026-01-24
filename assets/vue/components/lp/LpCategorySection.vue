<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useRoute } from "vue-router"
import Draggable from "vuedraggable"
import LpCardItem from "./LpCardItem.vue"
import BaseDropdownMenu from "../basecomponents/BaseDropdownMenu.vue"
import lpService from "../../services/lpService"
import { useI18n } from "vue-i18n"

const { t } = useI18n()

const props = defineProps({
  title: { type: String, default: "Learning Path Category" },
  category: { type: Object, required: true },
  list: { type: Array, default: () => [] },
  canEdit: { type: Boolean, default: false },
  canExportScorm: { type: Boolean, default: false },
  canExportPdf: { type: Boolean, default: false },
  canAutoLaunch: { type: Boolean, default: false },
  ringDash: { type: Function, required: true },
  ringValue: { type: Function, required: true },
  buildDates: { type: Function, required: false },
  isSessionCategory: { type: Boolean, default: false },
})

const emit = defineEmits([
  "open",
  "edit",
  "report",
  "settings",
  "build",
  "toggle-visible",
  "toggle-publish",
  "delete",
  "export-scorm",
  "export-pdf",
  "update-scorm",
  "reorder",
  "toggle-auto-launch",
])

const displayTitle = computed(() => props.title || t("Learning path categories"))

const localList = ref([...(props.list ?? [])])
const dragging = ref(false)

watch(
  () => props.list,
  (nv) => {
    if (dragging.value) return
    localList.value = [...(nv ?? [])]
  },
  { immediate: true },
)

function onEndCat() {
  dragging.value = false
  emit(
    "reorder",
    localList.value.map((i) => i.iid),
  )
}

const route = useRoute()
const cid = computed(() => Number(route.query?.cid ?? 0) || undefined)
const sid = computed(() => Number(route.query?.sid ?? 0) || undefined)
const node = computed(() => Number(route.params?.node ?? 0) || undefined)

const goCat = (action, extraParams = {}) => {
  const url = lpService.buildLegacyActionUrl(action, {
    cid: cid.value,
    sid: sid.value,
    node: node.value,
    params: { id: props.category.iid, ...extraParams },
  })
  window.location.assign(url)
}

const onCatEdit = () => goCat("add_lp_category")
const onCatAddUsers = () => goCat("add_users_to_category")

const onCatToggleVisibility = () => {
  const vis = props.category.visibility ?? props.category.visible
  const next = typeof vis === "number" ? (vis ? 0 : 1) : 1
  goCat("toggle_category_visibility", { new_status: next })
}

const onCatTogglePublish = () => {
  const pub = props.category.isPublished ?? props.category.published
  let next = 1
  if (typeof pub === "number") next = pub ? 0 : 1
  if (typeof pub === "string") next = pub === "v" ? 0 : 1
  goCat("toggle_category_publish", { new_status: next })
}

const onCatDelete = () => {
  // Do not allow deletion if category is not empty
  if (localList.value.length > 0) {
    alert(t("You must move or remove all learning paths from this category before deleting it."))
    return
  }

  const label = (props.category.title || "").trim() || t("Category")
  const msg = `${t("Are you sure you want to delete {0}?", [label])}`
  if (confirm(msg)) {
    goCat("delete_lp_category")
  }
}

const isOpen = ref(true)
const storageKey = computed(() => `lpCatOpen:${props.category?.iid || props.title}`)

onMounted(() => {
  const saved = localStorage.getItem(storageKey.value)
  if (saved !== null) isOpen.value = saved === "1"
})

watch(isOpen, (v) => localStorage.setItem(storageKey.value, v ? "1" : "0"))

const panelId = computed(() => `cat-panel-${props.category?.iid || props.title}`)
const toggleOpen = () => {
  if (localList.value.length) isOpen.value = !isOpen.value
}
</script>

<template>
  <section class="relative ml-2 rounded-2xl shadow-lg">
    <header class="relative bg-support-6 rounded-t-2xl flex items-center justify-between pl-0 pr-4 py-3">
      <span
        aria-hidden
        class="pointer-events-none absolute inset-y-0 -left-1.5 w-1.5 bg-support-5 rounded-l-2xl"
      />
      <div class="flex items-center gap-3">
        <template v-if="canEdit">
          <button
            :aria-label="t('Drag to reorder')"
            :title="t('Drag to reorder')"
            class="w-8 h-8 grid place-content-center rounded-lg text-gray-50 hover:bg-gray-15 hover:text-gray-90"
            type="button"
          >
            <svg
              aria-hidden
              fill="currentColor"
              height="14"
              viewBox="0 0 14 14"
              width="14"
            >
              <circle
                cx="4"
                cy="3"
                r="1.2"
              />
              <circle
                cx="4"
                cy="7"
                r="1.2"
              />
              <circle
                cx="4"
                cy="11"
                r="1.2"
              />
              <circle
                cx="10"
                cy="3"
                r="1.2"
              />
              <circle
                cx="10"
                cy="7"
                r="1.2"
              />
              <circle
                cx="10"
                cy="11"
                r="1.2"
              />
            </svg>
          </button>
        </template>
        <template v-else>
          <span
            aria-hidden
            class="inline-block w-8 h-8"
          ></span>
        </template>

        <h2 class="text-body-1 font-semibold text-gray-90">
          <span>{{ displayTitle }}</span>
          <span
            v-if="isSessionCategory"
            class="ml-2 text-warning"
            title="Category created for a session"
            >★</span
          >
        </h2>
      </div>

      <div class="flex items-center gap-2">
        <div class="text-tiny text-gray-50">{{ localList.length }} {{ t("Learning paths") }}</div>

        <BaseDropdownMenu
          v-if="canEdit"
          :dropdown-id="`category-${category.iid}`"
          class="relative z-30"
        >
          <template #button>
            <span
              :aria-label="t('Options')"
              :title="t('Options')"
              class="list-none w-8 h-8 grid place-content-center rounded-lg border border-gray-25 hover:bg-gray-15 cursor-pointer"
            >
              <i
                aria-hidden
                class="mdi mdi-dots-vertical text-lg"
              ></i>
            </span>
          </template>

          <template #menu>
            <div
              class="absolute right-0 top-full mt-2 w-60 bg-white border border-gray-25 rounded-xl shadow-xl p-1 z-50"
            >
              <button
                class="w-full text-left px-3 py-2 rounded hover:bg-gray-15"
                @click="onCatEdit"
              >
                {{ t("Edit category") }}
              </button>

              <button
                class="w-full text-left px-3 py-2 rounded hover:bg-gray-15"
                @click="onCatAddUsers"
              >
                {{ t("Subscribe users to category") }}
              </button>

              <div class="my-1 h-px bg-gray-15"></div>

              <button
                class="w-full text-left px-3 py-2 rounded hover:bg-gray-15"
                @click="onCatToggleVisibility"
              >
                {{ t("Toggle visibility") }}
              </button>

              <button
                class="w-full text-left px-3 py-2 rounded hover:bg-gray-15"
                @click="onCatTogglePublish"
              >
                {{ t("Publish / Hide") }}
              </button>

              <div class="my-1 h-px bg-gray-15"></div>

              <button
                class="w-full text-left px-3 py-2 rounded hover:bg-gray-15 text-danger"
                @click="onCatDelete"
              >
                {{ t("Delete") }}
              </button>
            </div>
          </template>
        </BaseDropdownMenu>

        <button
          v-if="localList.length"
          :aria-controls="panelId"
          :aria-expanded="isOpen ? 'true' : 'false'"
          :title="t('Expand') / t('Collapse')"
          class="w-8 h-8 grid place-content-center rounded-lg border border-gray-25 hover:bg-gray-15 transition"
          type="button"
          @click="toggleOpen"
        >
          <svg
            :class="isOpen ? 'rotate-180' : ''"
            class="transition-transform duration-200"
            fill="none"
            height="18"
            stroke="currentColor"
            viewBox="0 0 24 24"
            width="18"
          >
            <path
              d="M6 9l6 6 6-6"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
            />
          </svg>
        </button>
      </div>
    </header>

    <div
      v-if="isOpen && localList.length"
      :id="panelId"
      class="sm:px-4 sm:pb-4 px-2 pb-2 bg-white rounded-b-2xl"
    >
      <Draggable
        v-model="localList"
        :animation="180"
        :disabled="!canEdit"
        :empty-insert-threshold="10"
        :fallback-on-body="true"
        :force-fallback="true"
        :prevent-on-filter="true"
        chosen-class="chosen"
        class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3 mt-5"
        drag-class="dragging"
        ghost-class="ghosting"
        handle=".drag-handle"
        item-key="iid"
        tag="div"
        @end="onEndCat"
        @start="dragging = true"
      >
        <template #item="{ element }">
          <LpCardItem
            :buildDates="buildDates"
            :canAutoLaunch="canAutoLaunch"
            :canEdit="canEdit"
            :canExportPdf="canExportPdf"
            :canExportScorm="canExportScorm"
            :lp="element"
            :ringDash="ringDash"
            :ringValue="ringValue"
            @build="emit('build', element)"
            @delete="emit('delete', element)"
            @edit="emit('edit', element)"
            @open="emit('open', element)"
            @report="emit('report', element)"
            @settings="emit('settings', element)"
            @toggle-auto-launch="emit('toggle-auto-launch', element)"
            @toggle-visible="emit('toggle-visible', element)"
            @toggle-publish="emit('toggle-publish', element)"
            @export-scorm="emit('export-scorm', element)"
            @export-pdf="emit('export-pdf', element)"
            @update-scorm="emit('update-scorm', element)"
          />
        </template>
      </Draggable>
    </div>
  </section>
</template>
