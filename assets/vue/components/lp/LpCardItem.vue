<script setup>
import { computed } from "vue"
import { useI18n } from "vue-i18n"
import BaseDropdownMenu from "../basecomponents/BaseDropdownMenu.vue"

const { t } = useI18n()

const props = defineProps({
  lp: { type: Object, required: true },
  canEdit: { type: Boolean, default: false },
  canExportScorm: {type: Boolean, default: false},
  ringDash: { type: Function, required: true },
  ringValue: { type: Function, required: true },
})
const emit = defineEmits([
  "open","edit","report","settings", "build",
  "toggle-visible","toggle-publish","delete","export-scorm"
])

const dateText = computed(() => {
  const v = props.lp?.dateText ?? ""
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
  <div class="relative rounded-2xl border border-gray-25 bg-white px-4 pt-3 pb-4 min-h-[220px] flex flex-col">
    <button
      v-if="canEdit"
      class="drag-handle2 absolute left-3 top-3 w-8 h-8 grid place-content-center rounded-lg text-gray-50 hover:text-gray-90 hover:bg-gray-15 cursor-move"
      :title="t('Drag to reorder')" :aria-label="t('Drag to reorder')"
    >
      <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor" aria-hidden>
        <circle cx="4"  cy="3"  r="1.2" /><circle cx="4"  cy="7"  r="1.2" /><circle cx="4"  cy="11" r="1.2" />
        <circle cx="10" cy="3"  r="1.2" /><circle cx="10" cy="7"  r="1.2" /><circle cx="10" cy="11" r="1.2" />
      </svg>
    </button>

    <div class="mt-2 grid grid-cols-[80px_1fr] gap-3 items-start pr-10 pl-8">
      <div class="w-20 h-20 rounded-xl overflow-hidden ring-1 ring-gray-25 bg-gray-15 shrink-0">
        <img v-if="lp.coverUrl" :src="lp.coverUrl" alt="" class="w-full h-full object-cover" />
        <div v-else class="w-full h-full grid place-content-center text-gray-40">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="opacity-70">
            <rect x="3" y="3" width="18" height="18" rx="3" stroke-width="1.5"/>
            <path d="M3 16l4-4 3 3 5-5 6 6" stroke-width="1.5"/>
            <circle cx="9" cy="8" r="1.3" stroke-width="1.2"/>
          </svg>
        </div>
      </div>

      <div class="min-w-0">
        <h3 class="font-semibold text-gray-90 leading-snug">
          <button
            class="underline-offset-2 hover:underline focus:underline text-left"
            @click="emit('open', lp)"
            :title="t('Open')"
          >
            {{ lp.title || t('LP title here') }}
          </button>
        </h3>

        <div v-if="lp.prerequisiteName" class="mt-1 text-caption text-support-5 flex items-center gap-1.5">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden>
            <circle cx="12" cy="12" r="3"/>
          </svg>
          <span class="font-medium">{{ t('Pre-requisite:') }}</span>
          <span class="text-support-5">{{ lp.prerequisiteName }}</span>
        </div>
      </div>

      <p class="col-span-2 mt-3 text-caption text-gray-50">
        {{ dateText }}
      </p>
    </div>

    <div class="mt-auto pt-3 flex items-center pl-8">
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
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M4 19h16M6 17V7m6 10V5m6 12v-8" stroke-width="1.7" stroke-linecap="round"/>
          </svg>
        </button>

        <button class="opacity-80 hover:opacity-100" :title="t('Visibility')" :aria-label="t('Visibility')" @click="emit('toggle-visible', lp)">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" stroke-width="1.7"/>
            <circle cx="12" cy="12" r="3" stroke-width="1.7"/>
          </svg>
        </button>

        <button
          class="row-start-1 col-start-3 opacity-70 hover:opacity-100"
          :title="t('Settings')"
          :aria-label="t('Settings')"
          @click="emit('settings', lp)"
        >
          <svg width="20" height="20" viewBox="0 0 1024 1024" fill="none" stroke="currentColor">
            <path fill="#000000" d="M600.704 64a32 32 0 0 1 30.464 22.208l35.2 109.376c14.784 7.232 28.928 15.36 42.432 24.512l112.384-24.192a32 32 0 0 1 34.432 15.36L944.32 364.8a32 32 0 0 1-4.032 37.504l-77.12 85.12a357.12 357.12 0 0 1 0 49.024l77.12 85.248a32 32 0 0 1 4.032 37.504l-88.704 153.6a32 32 0 0 1-34.432 15.296L708.8 803.904c-13.44 9.088-27.648 17.28-42.368 24.512l-35.264 109.376A32 32 0 0 1 600.704 960H423.296a32 32 0 0 1-30.464-22.208L357.696 828.48a351.616 351.616 0 0 1-42.56-24.64l-112.32 24.256a32 32 0 0 1-34.432-15.36L79.68 659.2a32 32 0 0 1 4.032-37.504l77.12-85.248a357.12 357.12 0 0 1 0-48.896l-77.12-85.248A32 32 0 0 1 79.68 364.8l88.704-153.6a32 32 0 0 1 34.432-15.296l112.32 24.256c13.568-9.152 27.776-17.408 42.56-24.64l35.2-109.312A32 32 0 0 1 423.232 64H600.64zm-23.424 64H446.72l-36.352 113.088-24.512 11.968a294.113 294.113 0 0 0-34.816 20.096l-22.656 15.36-116.224-25.088-65.28 113.152 79.68 88.192-1.92 27.136a293.12 293.12 0 0 0 0 40.192l1.92 27.136-79.808 88.192 65.344 113.152 116.224-25.024 22.656 15.296a294.113 294.113 0 0 0 34.816 20.096l24.512 11.968L446.72 896h130.688l36.48-113.152 24.448-11.904a288.282 288.282 0 0 0 34.752-20.096l22.592-15.296 116.288 25.024 65.28-113.152-79.744-88.192 1.92-27.136a293.12 293.12 0 0 0 0-40.256l-1.92-27.136 79.808-88.128-65.344-113.152-116.288 24.96-22.592-15.232a287.616 287.616 0 0 0-34.752-20.096l-24.448-11.904L577.344 128zM512 320a192 192 0 1 1 0 384 192 192 0 0 1 0-384zm0 64a128 128 0 1 0 0 256 128 128 0 0 0 0-256z" stroke-width="1.2"/>
          </svg>
        </button>
        <button
          v-if="canExportScorm"
          class="row-start-1 col-start-4 opacity-70 hover:opacity-100"
          :title="t('SCORM Export')"
          :aria-label="t('SCORM Export')"
          @click="emit('export-scorm', lp)"
        >
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path fill="#000000" d="M12,20 C16.418278,20 20,16.418278 20,12 C20,7.581722 16.418278,4 12,4 C7.581722,4 4,7.581722 4,12 C4,16.418278 7.581722,20 12,20 Z M12,22 C6.4771525,22 2,17.5228475 2,12 C2,6.4771525 6.4771525,2 12,2 C17.5228475,2 22,6.4771525 22,12 C22,17.5228475 17.5228475,22 12,22 Z M12,13 C12.5522847,13 13,12.5522847 13,12 C13,11.4477153 12.5522847,11 12,11 C11.4477153,11 11,11.4477153 11,12 C11,12.5522847 11.4477153,13 12,13 Z M12,15 C10.3431458,15 9,13.6568542 9,12 C9,10.3431458 10.3431458,9 12,9 C13.6568542,9 15,10.3431458 15,12 C15,13.6568542 13.6568542,15 12,15 Z M13,7.5 C12.7238576,7.5 12.5,7.27614237 12.5,7 C12.5,6.72385763 12.7238576,6.5 13,6.5 C15.2761424,6.5 17.5,8.72385763 17.5,11 C17.5,11.2761424 17.2761424,11.5 17,11.5 C16.7238576,11.5 16.5,11.2761424 16.5,11 C16.5,9.27614237 14.7238576,7.5 13,7.5 Z" stroke-width="0"/>
          </svg>
        </button>
        <div class="relative w-8 h-8">
          <BaseDropdownMenu v-if="canEdit" 
          :dropdown-id="`card-${lp.iid}`"
          class="absolute"
          >
            <template #button>
              <span
                class="w-8 h-8 grid place-content-center rounded-lg border border-gray-25 hover:bg-gray-15 cursor-pointer"
                :title="t('More')" :aria-label="t('More')"
              >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                  <circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/>
                </svg>
              </span>
            </template>
            <template #menu>
              <div class="absolute right-0 w-44 bg-white border border-gray-25 rounded-xl shadow-xl p-1 z-10 mb-2" style="bottom: calc(-100% + 2.5rem)">
                  <button class="w-full text-left px-3 py-2 rounded hover:bg-gray-15" @click="emit('open', lp)">{{ t('Open') }}</button>
                  <button class="w-full text-left px-3 py-2 rounded hover:bg-gray-15" @click="emit('toggle-publish', lp)">{{ t('Publish / Unpublish') }}</button>
                  <button class="w-full text-left px-3 py-2 rounded hover:bg-gray-15" @click="emit('build', lp)">{{ t('Edit items (Build)') }}</button>
                  <button class="w-full text-left px-3 py-2 rounded hover:bg-gray-15 text-danger" @click="emit('delete', lp)">{{ t('Delete') }}</button>
              </div>
            </template>
          </BaseDropdownMenu>
        </div>
      </div>
    </div>
  </div>
</template>
