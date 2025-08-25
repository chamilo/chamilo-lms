<script setup>
import { computed } from "vue"
import { useI18n } from "vue-i18n"

const { t } = useI18n()

const props = defineProps({
  lp: { type: Object, required: true },
  canEdit: { type: Boolean, default: false },
  buildDates: { type: Function, required: true },
  ringDash: { type: Function, required: true },
  ringValue: { type: Function, required: true },
})

const emit = defineEmits([
  "open","edit","report","settings","build",
  "toggle-visible","toggle-publish","delete"
])

const dateText = computed(() => {
  const v = props.buildDates ? props.buildDates(props.lp) : ""
  return typeof v === "string" ? v.trim() : ""
})
</script>

<template>
  <article class="relative flex items-center gap-4 pl-5 pr-4 py-3 rounded-2xl border border-gray-25 bg-support-6 shadow-[0_1px_8px_rgba(0,0,0,0.04)]">
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

    <div class="ml-5 w-24 h-24 rounded-xl overflow-hidden ring-1 ring-gray-25 bg-gray-15 shrink-0">
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
      <h3 class="font-semibold text-gray-90 truncate">
        <button
          class="text-left hover:underline focus:underline underline-offset-2"
          @click="emit('open', lp)"
          :title="t('Open')"
        >
          {{ lp.title || t('LP title here') }}
        </button>
      </h3>

      <p v-if="dateText" class="text-caption text-gray-50 mt-8">{{ dateText }}</p>

      <div v-if="lp.prerequisiteName" class="mt-1 text-caption">
        <span class="text-support-5 font-medium">{{ t('Pre-requisite:') }}</span>
        <span class="text-support-5">{{ lp.prerequisiteName }}</span>
      </div>
    </div>

    <template v-if="canEdit">
      <div class="ml-auto grid grid-cols-[auto_auto_auto_auto] grid-rows-[auto_auto] items-center gap-x-3">
        <button class="row-start-1 col-start-1 opacity-70 hover:opacity-100" :title="t('Reports')" :aria-label="t('Reports')" @click="emit('report', lp)">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M4 19h16M6 17V7m6 10V5m6 12v-8" stroke-width="1.7" stroke-linecap="round"/>
          </svg>
        </button>

        <button class="row-start-1 col-start-2 opacity-70 hover:opacity-100" :title="t('Visibility')" :aria-label="t('Visibility')" @click="emit('toggle-visible', lp)">
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
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M12 8.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Zm8.5 3.5a7 7 0 0 0-.18-1.59l2.02-1.57-2-3.46-2.39.78A7 7 0 0 0 15.6 3l-.36-2.5h-4.5L10.4 3a7 7 0 0 0-2.95 1.19l-2.38-.78-2 3.46 2.02 1.57c-.12.52-.18 1.06-.18 1.59s.06 1.07.18 1.59Z" stroke-width="1.2"/>
          </svg>
        </button>

        <details class="row-start-1 col-start-4 relative z-10">
          <summary
            class="list-none w-8 h-8 rounded-lg border border-gray-25 grid place-content-center hover:bg-gray-15 cursor-pointer"
            :title="t('More')"
            :aria-label="t('More')"
          >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
              <circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/>
            </svg>
          </summary>

          <div
            class="absolute right-0 top-full mt-2 z-50 w-52 bg-white border border-gray-25 rounded-xl shadow-xl p-1"
            @mousedown.stop
            @click.stop
          >
            <button class="w-full text-left px-3 py-2 rounded hover:bg-gray-15" @click="emit('open', lp)">{{ t('Open') }}</button>
            <button class="w-full text-left px-3 py-2 rounded hover:bg-gray-15" @click="emit('toggle-publish', lp)">{{ t('Publish / Unpublish') }}</button>
            <button class="w-full text-left px-3 py-2 rounded hover:bg-gray-15" @click="emit('build', lp)">{{ t('Edit items (Build)') }}</button>
            <button class="w-full text-left px-3 py-2 rounded hover:bg-gray-15 text-danger" @click="emit('delete', lp)">{{ t('Delete') }}</button>
          </div>
        </details>

        <div class="row-start-2 col-start-1 col-end-5 flex items-center gap-2 justify-self-end mt-5">
          <span class="text-caption text-gray-50">
            {{ ringValue(lp.progress) === 100 ? t('Completed') : t('Progress') }}
          </span>
          <div class="relative w-10 h-10">
            <svg viewBox="0 0 37 37" class="w-10 h-10">
              <circle cx="18.5" cy="19" r="16" stroke-width="3.5" class="text-gray-25" fill="none" stroke="currentColor" />
              <circle
                cx="21" cy="18.5" r="16" stroke-width="3.5" fill="none"
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
      </div>
    </template>

    <template v-else>
      <div class="ml-auto flex items-center gap-2">
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
