<script setup>
import { computed } from "vue"
import { useI18n } from "vue-i18n"

const { t } = useI18n()

const props = defineProps({
  lp: { type: Object, required: true },
  canEdit: { type: Boolean, default: false },
  ringDash: { type: Function, required: true },
  ringValue: { type: Function, required: true },
})
const emit = defineEmits([
  "open","edit","report","settings","build",
  "toggle-visible","toggle-publish","delete"
])

const dateText = computed(() => {
  const v = props.lp?.dateText ?? ""
  return typeof v === "string" ? v.trim() : ""
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

    <details v-if="canEdit" class="absolute right-3 top-3">
      <summary
        class="w-8 h-8 grid place-content-center rounded-lg border border-gray-25 hover:bg-gray-15 cursor-pointer"
        :title="t('More')" :aria-label="t('More')"
      >
        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
          <circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/>
        </svg>
      </summary>
      <div class="absolute right-0 mt-2 w-44 bg-white border border-gray-25 rounded-xl shadow p-1 z-10">
        <button class="w-full text-left px-3 py-2 rounded hover:bg-gray-15" @click="emit('settings', lp)">{{ t('Settings') }}</button>
        <button class="w-full text-left px-3 py-2 rounded hover:bg-gray-15" @click="emit('toggle-visible', lp)">{{ t('Toggle visibility') }}</button>
        <button class="w-full text-left px-3 py-2 rounded hover:bg-gray-15" @click="emit('toggle-publish', lp)">{{ t('Publish / Unpublish') }}</button>
        <button class="w-full text-left px-3 py-2 rounded hover:bg-gray-15 text-danger" @click="emit('delete', lp)">{{ t('Delete') }}</button>
      </div>
    </details>

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
        <span class="text-caption text-gray-50">
          {{ ringValue(lp.progress) === 100 ? t('Completed') : t('Progress') }}
        </span>
      </div>

      <div v-if="canEdit" class="ml-auto flex items-center gap-4">
        <button class="opacity-80 hover:opacity-100" :title="t('Reports')" :aria-label="t('Reports')" @click="emit('report', lp)">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M4 19h16M6 17V7m6 10V5m6 12v-8" stroke-width="1.7" stroke-linecap="round"/>
          </svg>
        </button>

        <button class="opacity-80 hover:opacity-100" :title="t('Build')" :aria-label="t('Build')" @click="emit('build', lp)">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <rect x="9" y="9" width="10" height="10" rx="2" stroke-width="1.7"/>
            <rect x="5" y="5" width="10" height="10" rx="2" stroke-width="1.7"/>
          </svg>
        </button>

        <button class="opacity-80 hover:opacity-100" :title="t('Visibility')" :aria-label="t('Visibility')" @click="emit('toggle-visible', lp)">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" stroke-width="1.7"/>
            <circle cx="12" cy="12" r="3" stroke-width="1.7"/>
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>
