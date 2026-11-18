<template>
  <!-- bottom-16 keeps it clear of the Symfony debug toolbar (36px) on development platforms -->
  <div
    v-if="enabled"
    id="course-time-spent-clock"
    :aria-label="t('Time spent in the course')"
    :title="t('Time spent in the course')"
    class="fixed bottom-16 left-4 z-[1050] flex items-center gap-2 rounded-full border border-gray-25 bg-white px-3 py-1 text-gray-90 shadow-md"
    role="timer"
  >
    <BaseIcon
      icon="clock"
      size="small"
    />
    <span
      class="font-mono text-body-2"
      v-text="formattedTime"
    />
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue"
import { useRoute } from "vue-router"
import { useI18n } from "vue-i18n"
import BaseIcon from "../basecomponents/BaseIcon.vue"
import baseService from "../../services/baseService"
import { getCourseContext } from "../../utils/courseContext"

// How often the clock re-reads the time recorded by the course tracking.
const SYNC_INTERVAL_MS = 60000

const { t } = useI18n()
const route = useRoute()

const enabled = ref(false)
const seconds = ref(0)
const counting = ref(false)

let contextKey = ""
let tickTimer = null
let syncTimer = null
let requestId = 0

const formattedTime = computed(() => {
  const total = Math.max(0, Math.floor(seconds.value))
  const pad = (value) => String(value).padStart(2, "0")

  return `${pad(Math.floor(total / 3600))}:${pad(Math.floor((total % 3600) / 60))}:${pad(total % 60)}`
})

function stopTimers() {
  clearInterval(tickTimer)
  clearInterval(syncTimer)
  tickTimer = null
  syncTimer = null
}

async function sync() {
  const { cid, sid } = getCourseContext()
  const currentRequest = ++requestId

  if (!cid) {
    enabled.value = false
    stopTimers()

    return
  }

  try {
    const data = await baseService.get("/course/time-spent-clock-data", { cid, sid })

    // A newer request (another course) answered meanwhile.
    if (currentRequest !== requestId) {
      return
    }

    enabled.value = Boolean(data?.enabled)
    seconds.value = Number(data?.seconds) || 0
    counting.value = Boolean(data?.counting)
  } catch {
    if (currentRequest === requestId) {
      enabled.value = false
    }
  }

  if (!enabled.value) {
    stopTimers()

    return
  }

  if (!tickTimer) {
    tickTimer = setInterval(() => {
      if (counting.value) {
        seconds.value += 1
      }
    }, 1000)
  }

  if (!syncTimer) {
    syncTimer = setInterval(sync, SYNC_INTERVAL_MS)
  }
}

function syncWhenContextChanges() {
  const { cid, sid } = getCourseContext()
  const key = `${cid}:${sid}`

  if (key === contextKey) {
    return
  }

  contextKey = key
  sync()
}

// Any navigation inside the course is learner activity: re-read the time so the clock starts
// counting again after an inactivity pause.
watch(
  () => route.fullPath,
  () => {
    const previousKey = contextKey
    syncWhenContextChanges()

    if (previousKey === contextKey && enabled.value && !counting.value) {
      sync()
    }
  },
)

onMounted(syncWhenContextChanges)

onBeforeUnmount(() => {
  stopTimers()
  requestId++
})
</script>
