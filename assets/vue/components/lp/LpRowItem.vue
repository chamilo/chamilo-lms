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
  "open","edit","report","settings","build",
  "toggle-visible","toggle-publish","delete","export-scorm","export-pdf","toggle-auto-launch",
])

const dateText = computed(() => {
  const v = props.buildDates ? props.buildDates(props.lp) : ""
  return typeof v === "string" ? v.trim() : ""
})

const progressBgClass = computed(() => {
  return props.ringValue(props.lp.progress) === 100 ? 'bg-success' : 'bg-support-5'
})

const progressTextClass = computed(() => {
  return props.ringValue(props.lp.progress) === 100 ? 'text-success' : 'text-support-5'
})

</script>

<template>
  <article class="relative md:flex items-center gap-4 pl-5 pr-4 py-3 rounded-2xl border border-gray-25 bg-support-6 shadow-[0_1px_8px_rgba(0,0,0,0.04)] w-full">
    <span class="absolute left-0 top-0 bottom-0 w-1.5 bg-support-5" aria-hidden />
    <button
      v-if="canEdit"
      class="drag-handle absolute top-2.5 left-2.5 w-5 h-5 grid place-content-center text-gray-40 hover:text-gray-70 transition-colors cursor-move"
      :title="t('Drag to reorder')"
      :aria-label="t('Drag to reorder')"
    >
      <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor" aria-hidden>
        <circle cx="4"  cy="3"  r="1.2" />
        <circle cx="4"  cy="7"  r="1.2" />
        <circle cx="4"  cy="11" r="1.2" />
        <circle cx="10" cy="3"  r="1.2" />
        <circle cx="10" cy="7"  r="1.2" />
        <circle cx="10" cy="11" r="1.2" />
      </svg>
    </button>

    <div class="flex gap-4 w-full">
      <div class="ml-5 w-20 h-20 md:w-24 md:h-24 rounded-xl overflow-hidden ring-1 ring-gray-25 bg-gray-15 shrink-0">
        <img v-if="lp.coverUrl" :src="lp.coverUrl" alt="" class="w-full h-full object-cover" />
        <div v-else class="w-full h-full grid place-content-center text-gray-40">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="opacity-70">
            <rect x="3" y="3" width="18" height="18" rx="3" stroke-width="1.5" />
            <path d="M3 16l4-4 3 3 5-5 6 6" stroke-width="1.5" />
            <circle cx="9" cy="8" r="1.3" stroke-width="1.2" />
          </svg>
        </div>
      </div>
      <div class="flex-1 min-w-0">
        <h3 class="font-semibold text-gray-90 md:truncate text-lg md:text-xl leading-none md:leading-4">
          <button
            class="text-left hover:underline focus:underline underline-offset-2"
            @click="emit('open', lp)"
            :title="t('Open')"
          >
            {{ lp.title || t('Learning path title here') }}
          </button>
        </h3>
        <p v-if="dateText" class="text-caption text-gray-50 mt-8 hidden md:block">{{ dateText }}</p>
        <div v-if="lp.prerequisiteName" class="mt-1 text-caption hidden md:block">
          <span class="text-support-5 font-medium">{{ t('Prerequisites') }}</span>
          <span class="text-support-5">{{ lp.prerequisiteName }}</span>
        </div>
      </div>
      <BaseDropdownMenu
        :dropdown-id="`row-${lp.iid}`"
        class="row-start-1 col-start-5 relative block md:hidden h-fit"
      >
        <template #button>
          <span
            class="list-none w-8 h-8 rounded-lg border border-gray-25 grid place-content-center hover:bg-gray-15 cursor-pointer"
            :title="t('More')"
            :aria-label="t('More')"
          >
            <i class="mdi mdi-dots-vertical text-lg" aria-hidden></i>
          </span>
        </template>
        <template #menu>
          <div class="absolute right-0 z-50 w-52 bg-white border border-gray-25 rounded-xl shadow-xl p-1">
            <button class="w-full text-left px-3 py-2 rounded hover:bg-gray-15" @click="emit('open', lp)">{{ t('Open') }}</button>
            <button class="w-full text-left px-3 py-2 rounded hover:bg-gray-15" @click="emit('toggle-publish', lp)">{{ t('Publish / Hide') }}</button>
            <button class="w-full text-left px-3 py-2 rounded hover:bg-gray-15" @click="emit('build', lp)">{{ t('Edit learnpath') }}</button>
            <button class="w-full text-left px-3 py-2 rounded hover:bg-gray-15 text-danger" @click="emit('delete', lp)">{{ t('Delete') }}</button>
            <button v-if="canExportScorm" class="w-full text-left px-3 py-2 rounded hover:bg-gray-15 md:hidden" @click="emit('export-scorm', lp)">{{ t('Export as SCORM') }}</button>
            <button class="w-full text-left px-3 py-2 rounded hover:bg-gray-15 md:hidden" @click="emit('settings', lp)">{{ t('Settings') }}</button>
          </div>
        </template>
      </BaseDropdownMenu>
    </div>
    <p v-if="dateText" class="text-caption text-gray-50 mt-4 block md:hidden ml-5">{{ dateText }}</p>
    <div v-if="lp.prerequisiteName" class="mt-1 text-caption">
      <span class="text-support-5 font-medium">{{ t('Prerequisites') }}</span>
      <span class="text-support-5">{{ lp.prerequisiteName }}</span>
    </div>

    <template v-if="canEdit">
      <div class="ml-5 md:ml-auto md:flex-col flex items-end justify-between md:justify-start">
        <div class="flex gap-x-3 order-2 md:order1 mt-5 md:mt-0">
          <button class="row-start-1 col-start-1 opacity-70 hover:opacity-100" :title="t('Reports')" :aria-label="t('Reports')" @click="emit('report', lp)">
            <i class="mdi mdi-chart-box-outline text-xl" />
          </button>
          <button class="row-start-1 col-start-2 opacity-70 hover:opacity-100" :title="t('Visibility')" :aria-label="t('Visibility')" @click="emit('toggle-visible', lp)">
            <i class="mdi mdi-eye-outline text-xl" />
          </button>
          <button
            class="row-start-1 col-start-3 opacity-70 hover:opacity-100"
            :title="t('Settings')"
            :aria-label="t('Settings')"
            @click="emit('settings', lp)"
          >
            <i class="mdi mdi-cog-outline text-xl" />
          </button>
            <button
            v-if="canExportScorm"
            class="row-start-1 col-start-4 opacity-70 hover:opacity-100 hidden md:block"
            :title="t('Export as SCORM')"
            :aria-label="t('Export as SCORM')"
            @click="emit('export-scorm', lp)"
            >
              <i class="mdi mdi-archive-arrow-down text-xl" />
          </button>
          <button
            v-if="canExportPdf"
            class="row-start-1 col-start-5 opacity-70 hover:opacity-100 hidden md:block"
            :title="t('Export to PDF')"
            :aria-label="t('Export to PDF')"
            @click="emit('export-pdf', lp)"
          >
            <i class="mdi mdi-file-pdf-box text-xl" />
          </button>
          <button
            v-if="canAutoLaunch"
            class="w-9 h-9 rounded-xl border border-gray-25 grid place-content-center hover:bg-gray-15"
            :title="Number(lp.autolaunch) === 1 ? $t('Disable learning path auto-launch') : $t('Enable learning path auto-launch')"
            @click.stop="emit('toggle-auto-launch', lp)"
          >
            <i
              class="mdi"
              :class="Number(lp.autolaunch) === 1 ? 'mdi-rocket-launch' : 'mdi-rocket-launch-outline'"
              aria-hidden
            ></i>
          </button>
          <BaseDropdownMenu
            :dropdown-id="`row-${lp.iid}`"
            class="row-start-1 col-start-5 relative hidden md:block"
          >
            <template #button>
              <span
                class="list-none w-8 h-8 rounded-lg border border-gray-25 grid place-content-center hover:bg-gray-15 cursor-pointer"
                :title="t('More')"
                :aria-label="t('More')"
              >
                <i class="mdi mdi-dots-vertical text-lg" aria-hidden></i>
              </span>
            </template>
            <template #menu>
              <div class="absolute right-0 z-50 w-52 bg-white border border-gray-25 rounded-xl shadow-xl p-1">
                <button class="w-full text-left px-3 py-2 rounded hover:bg-gray-15" @click="emit('open', lp)">{{ t('Open') }}</button>
                <button class="w-full text-left px-3 py-2 rounded hover:bg-gray-15" @click="emit('toggle-publish', lp)">{{ t('Publish / Hide') }}</button>
                <button class="w-full text-left px-3 py-2 rounded hover:bg-gray-15" @click="emit('build', lp)">{{ t('Edit learnpath') }}</button>
                <button class="w-full text-left px-3 py-2 rounded hover:bg-gray-15 text-danger" @click="emit('delete', lp)">{{ t('Delete') }}</button>
              </div>
            </template>
          </BaseDropdownMenu>
        </div>

        <div class="row-start-2 col-start-1 col-end-5 flex items-center gap-2 justify-self-end mt-5 order-1 md:order-2">
          <span class="text-caption text-gray-50 order-2 md:order-1">
            {{ ringValue(lp.progress) === 100 ? t('Completed') : t('Progress') }}
          </span>
          <div class="relative w-10 h-10 order-1 md:order-2">
            <svg viewBox="0 0 37 37" class="w-10 h-10">
              <circle cx="18.5" cy="19" r="16" stroke-width="3.5" class="text-gray-25" fill="none" stroke="currentColor" />
              <circle
                cx="21" cy="18.5" r="16" stroke-width="3.5" fill="none"
                :stroke-dasharray="ringDash(lp.progress)"
                stroke-linecap="round"
                :class="progressTextClass"
                stroke="currentColor"
                transform="rotate(-90 20 20)"
              />
            </svg>
            <span
              class="absolute -top-0.5 left-1/2 -translate-x-1/2 w-1.5 h-1.5 rounded-full ring-2 ring-white"
              :class="progressBgClass"
              aria-hidden/>
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
          role="toolbar"
          aria-label="Student actions"
          class="flex items-center gap-2"
        >
          <button
            v-if="canExportPdf"
            class="opacity-80 hover:opacity-100 w-9 h-9 rounded-lg border border-gray-25 grid place-content-center"
            :title="t('Export to PDF')"
            :aria-label="t('Export to PDF')"
            @click="emit('export-pdf', lp)"
          >
            <i class="mdi mdi-file-pdf-box text-xl" />
          </button>

          <button
            class="opacity-80 hover:opacity-100 w-9 h-9 rounded-lg border border-gray-25 grid place-content-center"
            :title="t('Open')"
            :aria-label="t('Open')"
            @click="emit('open', lp)"
          >
            <i class="mdi mdi-open-in-new text-lg" />
          </button>
        </div>

        <span class="text-caption text-gray-50">
      {{ ringValue(lp.progress) === 100 ? t('Completed') : t('Progress') }}
    </span>
        <div class="relative w-10 h-10">
          <svg viewBox="0 0 40 40" class="w-10 h-10">
            <circle cx="20" cy="20" r="16" stroke-width="3.5" class="text-gray-25" fill="none" stroke="currentColor" />
            <circle
              cx="20" cy="20" r="16" stroke-width="3.5" fill="none"
              :stroke-dasharray="ringDash(lp.progress)"
              stroke-linecap="round"
              class="text-support-5"
              stroke="currentColor"
              transform="rotate(-90 20 20)"
            />
          </svg>
          <span class="absolute -top-0.5 left-1/2 -translate-x-1/2 w-1.5 h-1.5 rounded-full bg-support-5 ring-2 ring-white" aria-hidden/>
          <div class="absolute inset-0 grid place-content-center text-tiny font-semibold text-gray-90">
            {{ ringValue(lp.progress) }}%
          </div>
        </div>
      </div>
    </template>
  </article>
</template>
