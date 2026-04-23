<script setup>
import { computed } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseDropdownMenu from "../basecomponents/BaseDropdownMenu.vue"
import lpService from "../../services/lpService"
import BaseAppLink from "../basecomponents/BaseAppLink.vue"
import { useConfirmation } from "../../composables/useConfirmation"

const { t } = useI18n()
const { requireConfirmation } = useConfirmation()
const route = useRoute()

const props = defineProps({
  lp: { type: Object, required: true },
  canEdit: { type: Boolean, default: false },
  canExportScorm: { type: Boolean, default: false },
  canExportPdf: { type: Boolean, default: false },
  ringDash: { type: Function, required: true },
  ringValue: { type: Function, required: true },
  buildDates: { type: Function, required: true },
})
const emit = defineEmits(["export-pdf"])

const routeCtx = computed(() => ({
  cid: Number(route.query?.cid ?? 0) || undefined,
  sid: Number(route.query?.sid ?? 0) || undefined,
  node: Number(route.params?.node ?? 0) || undefined,
}))

const openUrl = computed(() =>
  lpService.buildLegacyViewUrl(props.lp.iid, {
    cid: routeCtx.value.cid ?? 0,
    sid: routeCtx.value.sid ?? 0,
    isStudentView: route.query?.isStudentView === "true" ? "true" : "false",
  }),
)

const reportUrl = computed(() => lpService.buildLegacyActionUrl(props.lp.iid, "report", routeCtx.value))

const settingsUrl = computed(() => lpService.buildLegacyActionUrl(props.lp.iid, "edit", routeCtx.value))

const buildUrl = computed(() =>
  lpService.buildLegacyActionUrl(props.lp.iid, "add_item", {
    ...routeCtx.value,
    params: { type: "step", isStudentView: "false" },
  }),
)

const exportScormUrl = computed(() =>
  lpService.buildLegacyActionUrl(props.lp.iid, "export", {
    ...routeCtx.value,
    gid: Number(route.query?.gid ?? 0),
    gradebook: Number(route.query?.gradebook ?? 0),
    origin: String(route.query?.origin ?? ""),
  }),
)

const togglePublishUrl = computed(() =>
  lpService.buildLegacyActionUrl(props.lp.iid, "toggle_publish", {
    ...routeCtx.value,
    params: { new_status: props.lp.published === "v" ? "i" : "v" },
  }),
)

const toggleVisibleUrl = computed(() =>
  lpService.buildLegacyActionUrl(props.lp.iid, "toggle_visible", {
    ...routeCtx.value,
    params: { new_status: typeof props.lp.visible !== "undefined" ? (props.lp.visible ? 0 : 1) : 1 },
  }),
)

const deleteUrl = computed(() => lpService.buildLegacyActionUrl(props.lp.iid, "delete", routeCtx.value))

const onDelete = () => {
  const label = (props.lp.title || "").trim() || t("Learning path")

  requireConfirmation({
    message: `${t("Are you sure to delete")} ${label}?`,
    accept: () => {
      window.location.href = deleteUrl.value
    },
  })
}

const dateText = computed(() => {
  const v = props.buildDates(props.lp)

  return typeof v === "string" ? v.trim() : ""
})

const progressBgClass = computed(() => (props.ringValue(props.lp.progress) === 100 ? "bg-success" : "bg-support-5"))

const progressTextClass = computed(() =>
  props.ringValue(props.lp.progress) === 100 ? "text-success" : "text-support-5",
)
</script>

<template>
  <div class="relative rounded-2xl border border-gray-25 bg-white px-2 sm:px-4 pt-3 pb-4 min-h-[220px] flex flex-col">
    <button
      v-if="canEdit"
      :aria-label="t('Drag to reorder')"
      :title="t('Drag to reorder')"
      class="drag-handle absolute left-0 sm:left-3 top-3 w-8 h-8 grid place-content-center rounded-lg text-gray-50 hover:text-gray-90 hover:bg-gray-15 cursor-move"
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

    <div class="mt-2 grid grid-cols-[80px_1fr] gap-3 items-start md:pr-10 sm:ml-8 ml-5 mr-2 md:mr-0">
      <div class="w-20 h-20 rounded-xl overflow-hidden ring-1 ring-gray-25 bg-gray-15 shrink-0 ml-2 sm:ml-0">
        <img
          v-if="lp.coverUrl"
          :src="lp.coverUrl"
          alt=""
          class="w-full h-full object-cover"
        />
        <div
          v-else
          class="w-full h-full grid place-content-center text-gray-40"
        >
          <svg
            class="opacity-70"
            fill="none"
            height="26"
            stroke="currentColor"
            viewBox="0 0 24 24"
            width="26"
          >
            <rect
              height="18"
              rx="3"
              stroke-width="1.5"
              width="18"
              x="3"
              y="3"
            />
            <path
              d="M3 16l4-4 3 3 5-5 6 6"
              stroke-width="1.5"
            />
            <circle
              cx="9"
              cy="8"
              r="1.3"
              stroke-width="1.2"
            />
          </svg>
        </div>
      </div>

      <div class="min-w-0 flex ml-2 md:ml-0">
        <div class="flex-1">
          <h3 class="font-semibold text-gray-90 leading-none md:truncate text-lg md:text-xl leading-none">
            <BaseAppLink
              :title="t('Open')"
              :url="openUrl"
              class="lp-panel__title"
            >
              {{ lp.title || t("Learning path title here") }}
            </BaseAppLink>
          </h3>
          <div
            v-if="lp.prerequisiteName"
            class="mt-1 text-caption text-support-5 flex items-center gap-1.5"
          >
            <svg
              aria-hidden
              class="w-4 h-4"
              fill="currentColor"
              viewBox="0 0 24 24"
            >
              <circle
                cx="12"
                cy="12"
                r="3"
              />
            </svg>
            <span class="font-medium">{{ t("Prerequisites") }}</span>
            <span class="text-support-5">{{ lp.prerequisiteName }}</span>
          </div>
        </div>
        <div class="relative w-8 h-8 block md:hidden">
          <BaseDropdownMenu
            v-if="canEdit"
            :dropdown-id="`card-${lp.iid}`"
            class="absolute"
          >
            <template #button>
              <span
                :aria-label="t('More')"
                :title="t('More')"
                class="w-8 h-8 grid place-content-center rounded-lg border border-gray-25 hover:bg-gray-15 cursor-pointer"
              >
                <i
                  aria-hidden
                  class="mdi mdi-dots-vertical text-lg"
                ></i>
              </span>
            </template>
            <template #menu>
              <div
                class="absolute right-0 w-44 bg-white border border-gray-25 rounded-xl shadow-xl p-1 z-40 mb-2"
                style="bottom: calc(-100% + 2.5rem)"
              >
                <BaseAppLink
                  :url="togglePublishUrl"
                  class="block w-full text-left px-3 py-2 rounded hover:bg-gray-15"
                >
                  {{ t("Publish / Hide") }}
                </BaseAppLink>
                <BaseAppLink
                  v-if="canExportScorm"
                  :url="exportScormUrl"
                  class="block w-full text-left px-3 py-2 rounded hover:bg-gray-15 md:hidden"
                >
                  {{ t("Export as SCORM") }}
                </BaseAppLink>
                <BaseAppLink
                  :url="settingsUrl"
                  class="block w-full text-left px-3 py-2 rounded hover:bg-gray-15 md:hidden"
                >
                  {{ t("Settings") }}
                </BaseAppLink>
                <button
                  class="w-full text-left px-3 py-2 rounded hover:bg-gray-15 text-danger"
                  type="button"
                  @click="onDelete"
                >
                  {{ t("Delete") }}
                </button>
              </div>
            </template>
          </BaseDropdownMenu>
        </div>
      </div>

      <p class="col-span-2 md:mt-3 text-caption text-gray-50 ml-2 md:ml-0">
        {{ dateText }}
      </p>
    </div>

    <div class="mt-auto pt-3 flex items-center ml-5 pl-2 sm:ml-8 sm:pl-0 mr-2 md:mr-0">
      <div class="flex items-center gap-2">
        <div class="relative w-10 h-10">
          <svg
            class="w-10 h-10"
            viewBox="0 0 37 37"
          >
            <circle
              class="text-gray-25"
              cx="18.5"
              cy="19"
              fill="none"
              r="16"
              stroke="currentColor"
              stroke-width="3.5"
            />
            <circle
              :class="progressTextClass"
              :stroke-dasharray="ringDash(lp.progress)"
              cx="21"
              cy="18.5"
              fill="none"
              r="16"
              stroke="currentColor"
              stroke-linecap="round"
              stroke-width="3.5"
              transform="rotate(-90 20 20)"
            />
          </svg>
          <span
            :class="progressBgClass"
            aria-hidden
            class="absolute -top-0.5 left-1/2 -translate-x-1/2 w-1.5 h-1.5 rounded-full ring-2 ring-white"
          />
          <div class="absolute inset-0 grid place-content-center text-tiny font-semibold text-gray-90">
            {{ ringValue(lp.progress) }}%
          </div>
        </div>
        <span class="text-caption text-gray-50">
          {{ ringValue(lp.progress) === 100 ? t("Completed") : t("Progress") }}
        </span>
      </div>

      <div
        v-if="canEdit"
        class="ml-auto flex items-center gap-2"
      >
        <BaseButton
          :label="t('Edit learnpath')"
          :to-url="buildUrl"
          icon="edit"
          only-icon
          size="small"
          type="tertiary-alternative-text"
        />

        <BaseButton
          :label="t('Reports')"
          :to-url="reportUrl"
          icon="tracking"
          only-icon
          size="small"
          type="tertiary-alternative-text"
        />

        <BaseButton
          :label="t('Visibility')"
          :to-url="toggleVisibleUrl"
          icon="eye-on"
          only-icon
          size="small"
          type="tertiary-alternative-text"
        />

        <BaseButton
          :label="t('Settings')"
          :to-url="settingsUrl"
          icon="cog"
          only-icon
          size="small"
          type="tertiary-alternative-text"
        />
        <div class="relative w-8 h-8 hidden md:block">
          <BaseDropdownMenu
            v-if="canEdit"
            :dropdown-id="`card-${lp.iid}`"
            class="absolute"
          >
            <template #button>
              <span
                :aria-label="t('More')"
                :title="t('More')"
                class="w-8 h-8 grid place-content-center rounded-lg border border-gray-25 hover:bg-gray-15 cursor-pointer"
              >
                <i
                  aria-hidden
                  class="mdi mdi-dots-vertical text-lg"
                ></i>
              </span>
            </template>
            <template #menu>
              <div
                class="absolute right-0 w-44 bg-white border border-gray-25 rounded-xl shadow-xl p-1 z-40 mb-2"
                style="bottom: calc(-100% + 2.5rem)"
              >
                <BaseAppLink
                  :url="togglePublishUrl"
                  class="block w-full text-left px-3 py-2 rounded hover:bg-gray-15"
                >
                  {{ t("Publish / Hide") }}
                </BaseAppLink>

                <BaseAppLink
                  v-if="canExportScorm"
                  :url="exportScormUrl"
                  class="block w-full text-left px-3 py-2 rounded hover:bg-gray-15"
                >
                  {{ t("Export as SCORM") }}
                </BaseAppLink>

                <button
                  v-if="canExportPdf"
                  class="w-full text-left px-3 py-2 rounded hover:bg-gray-15"
                  type="button"
                  @click="emit('export-pdf', lp)"
                >
                  {{ t("Export to PDF") }}
                </button>
                <button
                  class="w-full text-left px-3 py-2 rounded hover:bg-gray-15 text-danger"
                  type="button"
                  @click="onDelete"
                >
                  {{ t("Delete") }}
                </button>
              </div>
            </template>
          </BaseDropdownMenu>
        </div>
      </div>

      <div
        v-else
        class="ml-auto flex items-center gap-2"
      >
        <div
          aria-label="Student actions"
          class="flex items-center gap-2"
          role="toolbar"
        >
          <button
            v-if="canExportPdf"
            :aria-label="t('Export to PDF')"
            :title="t('Export to PDF')"
            class="opacity-80 hover:opacity-100 w-9 h-9 rounded-lg border border-gray-25 grid place-content-center"
            type="button"
            @click="emit('export-pdf', lp)"
          >
            <i class="mdi mdi-file-pdf-box text-xl" />
          </button>

          <BaseAppLink
            :title="t('Open')"
            :url="openUrl"
            class="opacity-80 hover:opacity-100 w-9 h-9 rounded-lg border border-gray-25 grid place-content-center"
          >
            <i class="mdi mdi-open-in-new text-lg" />
          </BaseAppLink>
        </div>
      </div>
    </div>
  </div>
</template>
