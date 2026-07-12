<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useRoute, useRouter } from "vue-router"
import Draggable from "vuedraggable"
import LpCardItem from "./LpCardItem.vue"
import BaseDropdownMenu from "../basecomponents/BaseDropdownMenu.vue"
import lpService from "../../services/lpService"
import { useI18n } from "vue-i18n"
import { useNotification } from "../../composables/notification"
import { useConfirmation } from "../../composables/useConfirmation"

const { t } = useI18n()
const { showErrorNotification } = useNotification()
const { requireConfirmation } = useConfirmation()

const props = defineProps({
  title: { type: String, default: "Learning Path Category" },
  category: { type: Object, required: true },
  list: { type: Array, default: () => [] },
  canEdit: { type: Boolean, default: false },
  canReorder: { type: Boolean, default: false },
  canOrderCategory: { type: Boolean, default: false },
  layoutBusy: { type: Boolean, default: false },
  canExportScorm: { type: Boolean, default: false },
  canExportPdf: { type: Boolean, default: false },
  canExportChamilo: { type: Boolean, default: false },
  canAutoLaunch: { type: Boolean, default: false },
  canCopy: { type: Boolean, default: false },
  canCopyScorm: { type: Boolean, default: false },
  canSeriousGame: { type: Boolean, default: false },
  ringDash: { type: Function, required: true },
  ringValue: { type: Function, required: true },
  buildDates: { type: Function, required: false },
  isSessionCategory: { type: Boolean, default: false },
  csrfToken: { type: String, default: "" },
})

const emit = defineEmits([
  "export-chamilo",
  "export-pdf",
  "layout-changed",
  "management-changed",
  "visibility-changed",
])

const displayTitle = computed(() => props.title || t("Learning path categories"))

const route = useRoute()
const router = useRouter()
const cid = computed(() => Number(route.query?.cid ?? 0) || undefined)
const sid = computed(() => Number(route.query?.sid ?? 0) || undefined)
const gid = computed(() => Number(route.query?.gid ?? 0))

const canManageCategory = computed(() =>
  props.canEdit && (Number(sid.value ?? 0) > 0 ? props.isSessionCategory : !props.isSessionCategory),
)

const onCatEdit = () =>
  router.push({
    name: "LpCategoryEdit",
    params: { node: route.params.node, categoryId: props.category.iid },
    query: route.query,
  })

const onCatAddUsers = () =>
  router.push({
    name: "LpCategorySubscriptions",
    params: { node: route.params.node, categoryId: props.category.iid },
    query: route.query,
  })

const categoryIsPublished = computed(() => Boolean(props.category.publishedOnCourseHome))

const categoryIsVisible = computed(() => {
  const value = props.category.visible ?? props.category.visibility

  if (typeof value === "string") {
    return ["1", "true", "v", "visible", "published"].includes(value.toLowerCase())
  }

  return typeof value === "undefined" || value === null ? true : Boolean(value)
})

const onCatToggleVisibility = async () => {
  if (!props.csrfToken) {
    return
  }

  try {
    await lpService.toggleCategoryVisibility(
      props.category.iid,
      {
        cid: cid.value || 0,
        sid: sid.value || 0,
        gid: gid.value || 0,
      },
      {
        visible: !categoryIsVisible.value,
        csrfToken: props.csrfToken,
      },
    )
    emit("visibility-changed")
  } catch (error) {
    showErrorNotification(error)
  }
}

const onCatTogglePublish = async () => {
  if (!props.csrfToken) {
    return
  }

  try {
    await lpService.manageCategory(
      props.category.iid,
      { cid: cid.value || 0, sid: sid.value || 0, gid: gid.value || 0 },
      { action: "toggle_publish", csrfToken: props.csrfToken },
    )
    emit("management-changed")
  } catch (error) {
    showErrorNotification(error)
  }
}

const onCatDelete = () => {
  const label = (props.category.title || "").trim() || t("Category")
  requireConfirmation({
    message: t("Are you sure you want to delete {0}?", [label]),
    accept: async () => {
      try {
        await lpService.deleteCategory(
          props.category.iid,
          { cid: cid.value || 0, sid: sid.value || 0, gid: gid.value || 0 },
          props.csrfToken,
        )
        emit("management-changed")
      } catch (error) {
        showErrorNotification(error)
      }
    },
  })
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
  if (props.list.length) isOpen.value = !isOpen.value
}
</script>

<template>
  <section class="relative w-full rounded-2xl shadow-lg">
    <header class="relative bg-support-6 rounded-t-2xl flex items-center justify-between pl-2 pr-4 py-3 sm:pl-4">
      <span
        aria-hidden
        class="pointer-events-none absolute inset-y-0 left-0 w-1.5 bg-support-5 rounded-l-2xl"
      />
      <div class="flex items-center gap-3">
        <button
          v-if="canOrderCategory"
          :aria-label="t('Drag to reorder')"
          :disabled="layoutBusy"
          :title="t('Drag to reorder')"
          class="category-drag-handle grid h-8 w-8 shrink-0 cursor-move place-content-center rounded-lg text-gray-50 hover:bg-gray-15 hover:text-gray-90 disabled:cursor-not-allowed disabled:opacity-50"
          type="button"
        >
          <svg
            aria-hidden
            fill="currentColor"
            height="14"
            viewBox="0 0 14 14"
            width="14"
          >
            <circle cx="4" cy="3" r="1.2" />
            <circle cx="4" cy="7" r="1.2" />
            <circle cx="4" cy="11" r="1.2" />
            <circle cx="10" cy="3" r="1.2" />
            <circle cx="10" cy="7" r="1.2" />
            <circle cx="10" cy="11" r="1.2" />
          </svg>
        </button>
        <span
          v-else
          aria-hidden
          class="inline-block h-8 w-8"
        ></span>

        <h2 class="text-body-1 font-semibold text-gray-90">
          <span>{{ displayTitle }}</span>
          <span
            v-if="isSessionCategory"
            class="ml-2 text-warning"
            :title="t('Session')"
            >★</span
          >
        </h2>
      </div>

      <div class="flex items-center gap-2">
        <div class="text-tiny text-gray-50">{{ list.length }} {{ t("Learning paths") }}</div>

        <BaseDropdownMenu
          v-if="canManageCategory"
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
                v-if="category.subscriptionsAllowed !== false"
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
                {{ categoryIsPublished ? t("do not publish") : t("Publish on course homepage") }}
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
          v-if="list.length"
          :aria-controls="panelId"
          :aria-expanded="isOpen ? 'true' : 'false'"
          :title="isOpen ? t('Collapse') : t('Expand')"
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
      v-if="isOpen || (canReorder && list.length === 0)"
      :id="panelId"
      class="bg-white px-2 pb-2 sm:px-4 sm:pb-4 rounded-b-2xl"
    >
      <Draggable
        :list="list"
        :animation="180"
        :disabled="!canReorder || layoutBusy"
        :empty-insert-threshold="80"
        :fallback-on-body="true"
        :force-fallback="true"
        :prevent-on-filter="true"
        chosen-class="chosen"
        class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3 mt-5"
        drag-class="dragging"
        ghost-class="ghosting"
        :group="{ name: 'learning-paths', pull: true, put: true }"
        handle=".drag-handle"
        item-key="iid"
        tag="div"
        :data-category-id="category.iid"
        @end="emit('layout-changed', $event)"
      >
        <template #item="{ element }">
          <LpCardItem
            :buildDates="buildDates"
            :canEdit="canEdit"
            :canReorder="canReorder"
            :canAutoLaunch="canAutoLaunch"
            :canCopy="canCopy"
            :canCopyScorm="canCopyScorm"
            :canExportPdf="canExportPdf"
            :canExportChamilo="canExportChamilo"
            :canExportScorm="canExportScorm"
            :canSeriousGame="canSeriousGame"
            :csrf-token="csrfToken"
            :lp="element"
            :ringDash="ringDash"
            :ringValue="ringValue"
            @export-chamilo="emit('export-chamilo', element)"
            @export-pdf="emit('export-pdf', element)"
            @management-changed="emit('management-changed')"
            @visibility-changed="emit('visibility-changed')"
          />
        </template>
        <template #footer>
          <div
            v-if="canReorder && list.length === 0"
            class="col-span-full flex min-h-[72px] items-center justify-center rounded-xl border border-dashed border-gray-30 bg-gray-10 px-4 py-3 text-sm text-gray-50"
          >
            {{ t("Drag and drop an element here") }}
          </div>
        </template>
      </Draggable>
    </div>
  </section>
</template>
