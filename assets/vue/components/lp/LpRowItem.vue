<script setup>
import { computed } from "vue"
import { useI18n } from "vue-i18n"
import BaseDropdownMenu from "../basecomponents/BaseDropdownMenu.vue"

const { t } = useI18n()

const props = defineProps({
  lp: { type: Object, required: true },
  canEdit: { type: Boolean, default: false },
  canExportScorm: { type: Boolean, default: false },
  canExportPdf: { type: Boolean, default: false },
  canAutoLaunch: { type: Boolean, default: false },
  buildDates: { type: Function, required: true },
  ringDash: { type: Function, required: true },
  ringValue: { type: Function, required: true },
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
  "toggle-auto-launch",
  "update-scorm",
])

const lpType = computed(() => {
  const v = props.lp?.lpType ?? props.lp?.lp_type ?? props.lp?.type ?? props.lp?.lpTypeId ?? props.lp?.lp_type_id ?? 0

  return Number(v) || 0
})

// Only SCORM packages (type = 2 in Chamilo legacy)
const canUpdateScorm = computed(() => props.canEdit && lpType.value === 2)

const dateText = computed(() => {
  const v = props.buildDates ? props.buildDates(props.lp) : ""
  return typeof v === "string" ? v.trim() : ""
})

const progressBgClass = computed(() => {
  return props.ringValue(props.lp.progress) === 100 ? "bg-success" : "bg-support-5"
})

const progressTextClass = computed(() => {
  return props.ringValue(props.lp.progress) === 100 ? "text-success" : "text-support-5"
})
</script>

<template>
  <article
    class="relative md:flex items-center gap-4 pl-5 pr-4 py-3 rounded-2xl border border-gray-25 bg-support-6 shadow-[0_1px_8px_rgba(0,0,0,0.04)] w-full"
  >
    <span
      aria-hidden="true"
      class="absolute left-0 top-0 bottom-0 w-1.5 bg-support-5"
    />
    <button
      v-if="canEdit"
      :aria-label="t('Drag to reorder')"
      :title="t('Drag to reorder')"
      class="drag-handle absolute top-2.5 left-2.5 w-5 h-5 grid place-content-center text-gray-40 hover:text-gray-70 transition-colors cursor-move"
    >
      <svg
        aria-hidden="true"
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

    <div class="flex gap-4 w-full">
      <div class="ml-5 w-20 h-20 md:w-24 md:h-24 rounded-xl overflow-hidden ring-1 ring-gray-25 bg-gray-15 shrink-0">
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
            height="30"
            stroke="currentColor"
            viewBox="0 0 24 24"
            width="30"
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
      <div class="flex-1 min-w-0 md:flex md:flex-col md:justify-center">
        <h3 class="font-semibold text-gray-90 md:truncate text-lg md:text-2xl leading-none">
          <button
            :title="t('Open')"
            class="text-left hover:underline focus:underline underline-offset-2"
            @click="emit('open', lp)"
          >
            {{ lp.title || t("Learning path title here") }}
          </button>
        </h3>
        <p
          v-if="dateText"
          class="text-caption text-gray-50 mt-8 hidden md:block"
        >
          {{ dateText }}
        </p>
        <div
          v-if="lp.prerequisiteName"
          class="mt-1 text-caption hidden md:block"
        >
          <span class="text-support-5 font-medium">{{ t("Prerequisites") }}</span>
          <span class="text-support-5">{{ lp.prerequisiteName }}</span>
        </div>
      </div>
      <BaseDropdownMenu
        :dropdown-id="`row-${lp.iid}`"
        class="row-start-1 col-start-5 relative block md:hidden h-fit"
      >
        <template #button>
          <span
            :aria-label="t('More')"
            :title="t('More')"
            class="list-none w-8 h-8 rounded-lg border border-gray-25 grid place-content-center hover:bg-gray-15 cursor-pointer"
          >
            <i
              aria-hidden="true"
              class="mdi mdi-dots-vertical text-lg"
            ></i>
          </span>
        </template>
        <template #menu>
          <div class="absolute right-0 z-50 w-52 bg-white border border-gray-25 rounded-xl shadow-xl p-1">
            <button
              class="w-full text-left px-3 py-2 rounded hover:bg-gray-15"
              @click="emit('open', lp)"
            >
              {{ t("Open") }}
            </button>
            <button
              class="w-full text-left px-3 py-2 rounded hover:bg-gray-15"
              @click="emit('toggle-publish', lp)"
            >
              {{ t("Publish / Hide") }}
            </button>
            <button
              class="w-full text-left px-3 py-2 rounded hover:bg-gray-15"
              @click="emit('build', lp)"
            >
              {{ t("Edit learnpath") }}
            </button>
            <button
              class="w-full text-left px-3 py-2 rounded hover:bg-gray-15 text-danger"
              @click="emit('delete', lp)"
            >
              {{ t("Delete") }}
            </button>
            <button
              v-if="canExportScorm"
              class="w-full text-left px-3 py-2 rounded hover:bg-gray-15 md:hidden"
              @click="emit('export-scorm', lp)"
            >
              {{ t("Export as SCORM") }}
            </button>
            <button
              v-if="canUpdateScorm"
              class="w-full text-left px-3 py-2 rounded hover:bg-gray-15 md:hidden"
              @click="emit('update-scorm', lp)"
            >
              {{ t("Update SCORM") }}
            </button>
            <button
              class="w-full text-left px-3 py-2 rounded hover:bg-gray-15 md:hidden"
              @click="emit('settings', lp)"
            >
              {{ t("Settings") }}
            </button>
          </div>
        </template>
      </BaseDropdownMenu>
    </div>
    <p
      v-if="dateText"
      class="text-caption text-gray-50 mt-4 block md:hidden ml-5"
    >
      {{ dateText }}
    </p>
    <div
      v-if="lp.prerequisiteName"
      class="mt-1 text-caption"
    >
      <span class="text-support-5 font-medium">{{ t("Prerequisites") }}</span>
      <span class="text-support-5">{{ lp.prerequisiteName }}</span>
    </div>

    <template v-if="canEdit">
      <div class="ml-5 md:ml-auto md:flex-col flex items-end justify-between md:justify-start">
        <div class="flex gap-x-3 order-2 md:order1 mt-5 md:mt-0">
          <button
            :aria-label="t('Reports')"
            :title="t('Reports')"
            class="row-start-1 col-start-1 opacity-70 hover:opacity-100"
            @click="emit('report', lp)"
          >
            <i class="mdi mdi-chart-box-outline text-xl" />
          </button>
          <button
            :aria-label="t('Visibility')"
            :title="t('Visibility')"
            class="row-start-1 col-start-2 opacity-70 hover:opacity-100"
            @click="emit('toggle-visible', lp)"
          >
            <i class="mdi mdi-eye-outline text-xl" />
          </button>
          <button
            :aria-label="t('Settings')"
            :title="t('Settings')"
            class="row-start-1 col-start-3 opacity-70 hover:opacity-100"
            @click="emit('settings', lp)"
          >
            <i class="mdi mdi-cog-outline text-xl" />
          </button>
          <button
            v-if="canExportScorm"
            :aria-label="t('Export as SCORM')"
            :title="t('Export as SCORM')"
            class="row-start-1 col-start-4 opacity-70 hover:opacity-100 hidden md:block"
            @click="emit('export-scorm', lp)"
          >
            <i class="mdi mdi-archive-arrow-down text-xl" />
          </button>
          <button
            v-if="canUpdateScorm"
            :aria-label="t('Update SCORM')"
            :title="t('Update SCORM')"
            class="row-start-1 col-start-5 opacity-70 hover:opacity-100 hidden md:block"
            @click="emit('update-scorm', lp)"
          >
            <i class="mdi mdi-upload text-xl" />
          </button>
          <button
            v-if="canExportPdf"
            :aria-label="t('Export to PDF')"
            :title="t('Export to PDF')"
            class="row-start-1 col-start-5 opacity-70 hover:opacity-100 hidden md:block"
            @click="emit('export-pdf', lp)"
          >
            <i class="mdi mdi-file-pdf-box text-xl" />
          </button>
          <button
            v-if="canAutoLaunch"
            :title="
              Number(lp.autolaunch) === 1
                ? $t('Disable learning path auto-launch')
                : $t('Enable learning path auto-launch')
            "
            class="w-9 h-9 rounded-xl border border-gray-25 grid place-content-center hover:bg-gray-15"
            @click.stop="emit('toggle-auto-launch', lp)"
          >
            <i
              :class="Number(lp.autolaunch) === 1 ? 'mdi-rocket-launch' : 'mdi-rocket-launch-outline'"
              aria-hidden="true"
              class="mdi"
            ></i>
          </button>
          <BaseDropdownMenu
            :dropdown-id="`row-${lp.iid}`"
            class="row-start-1 col-start-5 relative hidden md:block"
          >
            <template #button>
              <span
                :aria-label="t('More')"
                :title="t('More')"
                class="list-none w-8 h-8 rounded-lg border border-gray-25 grid place-content-center hover:bg-gray-15 cursor-pointer"
              >
                <i
                  aria-hidden="true"
                  class="mdi mdi-dots-vertical text-lg"
                ></i>
              </span>
            </template>
            <template #menu>
              <div class="absolute right-0 z-50 w-52 bg-white border border-gray-25 rounded-xl shadow-xl p-1">
                <button
                  class="w-full text-left px-3 py-2 rounded hover:bg-gray-15"
                  @click="emit('open', lp)"
                >
                  {{ t("Open") }}
                </button>
                <button
                  class="w-full text-left px-3 py-2 rounded hover:bg-gray-15"
                  @click="emit('toggle-publish', lp)"
                >
                  {{ t("Publish / Hide") }}
                </button>
                <button
                  class="w-full text-left px-3 py-2 rounded hover:bg-gray-15"
                  @click="emit('build', lp)"
                >
                  {{ t("Edit learnpath") }}
                </button>
                <button
                  v-if="canUpdateScorm"
                  class="w-full text-left px-3 py-2 rounded hover:bg-gray-15"
                  @click="emit('update-scorm', lp)"
                >
                  {{ t("Update SCORM") }}
                </button>
                <button
                  class="w-full text-left px-3 py-2 rounded hover:bg-gray-15 text-danger"
                  @click="emit('delete', lp)"
                >
                  {{ t("Delete") }}
                </button>
              </div>
            </template>
          </BaseDropdownMenu>
        </div>

        <div class="row-start-2 col-start-1 col-end-5 flex items-center gap-2 justify-self-end mt-5 order-1 md:order-2">
          <span class="text-caption text-gray-50 order-2 md:order-1">
            {{ ringValue(lp.progress) === 100 ? t("Completed") : t("Progress") }}
          </span>
          <div class="relative w-10 h-10 order-1 md:order-2">
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
              aria-hidden="true"
              class="absolute -top-0.5 left-1/2 -translate-x-1/2 w-1.5 h-1.5 rounded-full ring-2 ring-white"
            />
            <div class="absolute inset-0 grid place-content-center text-tiny font-semibold text-gray-90">
              {{ ringValue(lp.progress) }}%
            </div>
          </div>
        </div>
      </div>
    </template>

    <template v-else>
      <div class="ml-auto flex items-center gap-3">
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
            @click="emit('export-pdf', lp)"
          >
            <i class="mdi mdi-file-pdf-box text-xl" />
          </button>

          <button
            :aria-label="t('Open')"
            :title="t('Open')"
            class="opacity-80 hover:opacity-100 w-9 h-9 rounded-lg border border-gray-25 grid place-content-center"
            @click="emit('open', lp)"
          >
            <i class="mdi mdi-open-in-new text-lg" />
          </button>
        </div>

        <span class="text-caption text-gray-50">
          {{ ringValue(lp.progress) === 100 ? t("Completed") : t("Progress") }}
        </span>
        <div class="relative w-10 h-10">
          <svg
            class="w-10 h-10"
            viewBox="0 0 40 40"
          >
            <circle
              class="text-gray-25"
              cx="20"
              cy="20"
              fill="none"
              r="16"
              stroke="currentColor"
              stroke-width="3.5"
            />
            <circle
              :stroke-dasharray="ringDash(lp.progress)"
              class="text-support-5"
              cx="20"
              cy="20"
              fill="none"
              r="16"
              stroke="currentColor"
              stroke-linecap="round"
              stroke-width="3.5"
              transform="rotate(-90 20 20)"
            />
          </svg>
          <span
            aria-hidden="true"
            class="absolute -top-0.5 left-1/2 -translate-x-1/2 w-1.5 h-1.5 rounded-full bg-support-5 ring-2 ring-white"
          />
          <div class="absolute inset-0 grid place-content-center text-tiny font-semibold text-gray-90">
            {{ ringValue(lp.progress) }}%
          </div>
        </div>
      </div>
    </template>
  </article>
</template>
