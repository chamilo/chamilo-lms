<script setup>
import { computed } from "vue"
import { useI18n } from "vue-i18n"
import BaseDropdownMenu from "../basecomponents/BaseDropdownMenu.vue"

const { t } = useI18n()

const props = defineProps({
  lp: { type: Object, required: true },
  canEdit: { type: Boolean, default: false },
  canExportScorm: {type: Boolean, default: false},
  canExportPdf: { type: Boolean, default: false },
  ringDash: { type: Function, required: true },
  ringValue: { type: Function, required: true },
  buildDates: { type: Function, required: true },
})
const emit = defineEmits([
  "open","edit","report","settings", "build",
  "toggle-visible","toggle-publish","delete","export-scorm","export-pdf"
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
  <div class="relative rounded-2xl border border-gray-25 bg-white px-2 sm:px-4 pt-3 pb-4 min-h-[220px] flex flex-col">
    <button
      v-if="canEdit"
      class="drag-handle2 absolute left-0 sm:left-3 top-3 w-8 h-8 grid place-content-center rounded-lg text-gray-50 hover:text-gray-90 hover:bg-gray-15 cursor-move"
      :title="t('Drag to reorder')" :aria-label="t('Drag to reorder')"
    >
      <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor" aria-hidden>
        <circle cx="4"  cy="3"  r="1.2" /><circle cx="4"  cy="7"  r="1.2" /><circle cx="4"  cy="11" r="1.2" />
        <circle cx="10" cy="3"  r="1.2" /><circle cx="10" cy="7"  r="1.2" /><circle cx="10" cy="11" r="1.2" />
      </svg>
    </button>

    <div class="mt-2 grid grid-cols-[80px_1fr] gap-3 items-start md:pr-10 sm:ml-8 ml-5 mr-2 md:mr-0">
      <div class="w-20 h-20 rounded-xl overflow-hidden ring-1 ring-gray-25 bg-gray-15 shrink-0 ml-2 sm:ml-0">
        <img v-if="lp.coverUrl" :src="lp.coverUrl" alt="" class="w-full h-full object-cover" />
        <div v-else class="w-full h-full grid place-content-center text-gray-40">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="opacity-70">
            <rect x="3" y="3" width="18" height="18" rx="3" stroke-width="1.5"/>
            <path d="M3 16l4-4 3 3 5-5 6 6" stroke-width="1.5"/>
            <circle cx="9" cy="8" r="1.3" stroke-width="1.2"/>
          </svg>
        </div>
      </div>

      <div class="min-w-0 flex ml-2 md:ml-0">
        <div class="flex-1">
          <h3 class="font-semibold text-gray-90 leading-none md:truncate text-lg md:text-xl leading-none md:leading-4">
            <button
              class="underline-offset-2 hover:underline focus:underline text-left"
              @click="emit('open', lp)"
              :title="t('Open')"
            >
              {{ lp.title || t('Learning path title here') }}
            </button>
          </h3>
          <div v-if="lp.prerequisiteName" class="mt-1 text-caption text-support-5 flex items-center gap-1.5">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden>
              <circle cx="12" cy="12" r="3"/>
            </svg>
            <span class="font-medium">{{ t('Prerequisites') }}</span>
            <span class="text-support-5">{{ lp.prerequisiteName }}</span>
          </div>
        </div>
        <div class="relative w-8 h-8 block md:hidden">
          <BaseDropdownMenu v-if="canEdit"
          :dropdown-id="`card-${lp.iid}`"
          class="absolute"
          >
            <template #button>
              <span
                class="w-8 h-8 grid place-content-center rounded-lg border border-gray-25 hover:bg-gray-15 cursor-pointer"
                :title="t('More')" :aria-label="t('More')"
              >
                <i class="mdi mdi-dots-vertical text-lg" aria-hidden></i>
              </span>
            </template>
            <template #menu>
              <div class="absolute right-0 w-44 bg-white border border-gray-25 rounded-xl shadow-xl p-1 z-40 mb-2" style="bottom: calc(-100% + 2.5rem)">
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
      </div>

      <p class="col-span-2 md:mt-3 text-caption text-gray-50 ml-2 md:ml-0">
        {{ dateText }}
      </p>
    </div>

    <div class="mt-auto pt-3 flex items-center ml-5 pl-2 sm:ml-8 sm:pl-0 mr-2 md:mr-0">
      <div class="flex items-center gap-2">
        <div class="relative w-10 h-10">
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
            aria-hidden
          />
          <div class="absolute inset-0 grid place-content-center text-tiny font-semibold text-gray-90 ">
            {{ ringValue(lp.progress) }}%
          </div>
        </div>
        <span class="text-caption text-gray-50">
          {{ ringValue(lp.progress) === 100 ? t('Completed') : t('Progress') }}
        </span>
      </div>

      <div v-if="canEdit" class="ml-auto flex items-center gap-3">
        <button class="opacity-80 hover:opacity-100" :title="t('Reports')" :aria-label="t('Reports')" @click="emit('report', lp)">
          <i class="mdi mdi-chart-box-outline text-xl" />
        </button>

        <button class="opacity-80 hover:opacity-100" :title="t('Visibility')" :aria-label="t('Visibility')" @click="emit('toggle-visible', lp)">
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
          class="row-start-1 col-start-4 opacity-70 hover:opacity-100 md:block hidden"
          :title="t('Export as SCORM')"
          :aria-label="t('Export as SCORM')"
          @click="emit('export-scorm', lp)"
        >
          <i class="mdi mdi-archive-arrow-down text-xl" />
        </button>
        <button
          v-if="canExportPdf"
          class="row-start-1 col-start-5 opacity-70 hover:opacity-100 md:block hidden"
          :title="t('Export to PDF')"
          :aria-label="t('Export to PDF')"
          @click="emit('export-pdf', lp)"
        >
          <i class="mdi mdi-file-pdf-box text-xl" />
        </button>
        <div class="relative w-8 h-8 hidden md:block">
          <BaseDropdownMenu v-if="canEdit"
          :dropdown-id="`card-${lp.iid}`"
          class="absolute"
          >
            <template #button>
              <span
                class="w-8 h-8 grid place-content-center rounded-lg border border-gray-25 hover:bg-gray-15 cursor-pointer"
                :title="t('More')" :aria-label="t('More')"
              >
                <i class="mdi mdi-dots-vertical text-lg" aria-hidden></i>
              </span>
            </template>
            <template #menu>
              <div class="absolute right-0 w-44 bg-white border border-gray-25 rounded-xl shadow-xl p-1 z-40 mb-2" style="bottom: calc(-100% + 2.5rem)">
                  <button class="w-full text-left px-3 py-2 rounded hover:bg-gray-15" @click="emit('open', lp)">{{ t('Open') }}</button>
                  <button class="w-full text-left px-3 py-2 rounded hover:bg-gray-15" @click="emit('toggle-publish', lp)">{{ t('Publish / Hide') }}</button>
                  <button class="w-full text-left px-3 py-2 rounded hover:bg-gray-15" @click="emit('build', lp)">{{ t('Edit learnpath') }}</button>
                  <button class="w-full text-left px-3 py-2 rounded hover:bg-gray-15 text-danger" @click="emit('delete', lp)">{{ t('Delete') }}</button>
              </div>
            </template>
          </BaseDropdownMenu>
        </div>
      </div>
      <div v-else class="ml-auto flex items-center gap-2">
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
      </div>
    </div>
  </div>
</template>
