<template>
  <template v-if="dialogMode">
    <template v-if="details?.available && !error">
      <BaseButton
        :label="t('View room on floor plan')"
        icon="map-search"
        only-icon
        type="secondary"
        @click="dialogVisible = true"
      />

      <Dialog
        v-model:visible="dialogVisible"
        :header="t('View room on floor plan')"
        :style="{ width: 'min(960px, 95vw)' }"
        modal
      >
        <p class="mb-3 text-sm">{{ details.branchTitle }} — {{ details.roomTitle }}</p>
        <p
          v-if="imageFailed"
          role="status"
          class="text-sm text-gray-600"
        >
          {{ t("Floor plan unavailable") }}
        </p>
        <div
          v-else
          class="relative w-full overflow-hidden rounded border"
        >
          <img
            :src="imageUrl"
            :alt="t('Floor plan')"
            class="block w-full"
            @error="imageFailed = true"
          />
          <svg
            v-if="validPolygon"
            viewBox="0 0 1000 1000"
            preserveAspectRatio="none"
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 h-full w-full"
          >
            <polygon
              :points="polygonPoints"
              fill="#00897b"
              fill-opacity="0.3"
              stroke="#00897b"
              stroke-width="5"
            />
          </svg>
        </div>

        <template
          v-if="securityStore.isAdmin && !platformConfigStore.isStudentViewActive && Number(details.roomId) > 0"
          #footer
        >
          <BaseButton
            :label="t('Room')"
            :route="{ name: 'RoomUpdate', query: { id: `/api/rooms/${Number(details.roomId)}` } }"
            icon="room"
            type="secondary"
          />
        </template>
      </Dialog>
    </template>
  </template>

  <details
    v-else
    class="mt-2 rounded border p-3"
    @toggle="onToggle"
  >
    <summary class="cursor-pointer font-medium">{{ t("View room on floor plan") }}</summary>
    <p
      v-if="loading"
      class="mt-2 text-sm"
    >
      {{ t("Loading") }}...
    </p>
    <p
      v-else-if="error"
      role="status"
      class="mt-2 text-sm text-gray-600"
    >
      {{ error }}
    </p>
    <template v-else-if="details?.available">
      <p class="my-2 text-sm">{{ details.branchTitle }} — {{ details.roomTitle }}</p>
      <div class="relative w-full overflow-hidden rounded border">
        <img
          :src="imageUrl"
          :alt="t('Floor plan')"
          class="block w-full"
        />
        <svg
          v-if="validPolygon"
          viewBox="0 0 1000 1000"
          preserveAspectRatio="none"
          aria-hidden="true"
          class="pointer-events-none absolute inset-0 h-full w-full"
        >
          <polygon
            :points="polygonPoints"
            fill="#00897b"
            fill-opacity="0.3"
            stroke="#00897b"
            stroke-width="5"
          />
        </svg>
      </div>
    </template>
    <p
      v-else-if="loaded"
      class="mt-2 text-sm"
    >
      {{ t("No floor plan available") }}
    </p>
  </details>
</template>

<script setup>
import { computed, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import Dialog from "primevue/dialog"
import BaseButton from "../basecomponents/BaseButton.vue"
import baseService from "../../services/baseService"
import { useSecurityStore } from "../../store/securityStore"
import { usePlatformConfig } from "../../store/platformConfig"

const props = defineProps({
  courseId: { type: [Number, String], default: null },
  calendarId: { type: [Number, String], default: null },
  dialogMode: { type: Boolean, default: false },
})

const { t } = useI18n()
const route = useRoute()
const securityStore = useSecurityStore()
const platformConfigStore = usePlatformConfig()
const loading = ref(false)
const loaded = ref(false)
const details = ref(null)
const error = ref("")
const imageFailed = ref(false)
const dialogVisible = ref(false)

const url = computed(() =>
  props.calendarId
    ? `/api/room-floor-plans/attendance-calendars/${Number(props.calendarId)}`
    : `/api/room-floor-plans/courses/${Number(props.courseId)}`,
)
const contextParams = computed(() => {
  const params = {}
  for (const key of ["cid", "sid", "gid"]) {
    const number = Number((key === "cid" && props.courseId) || route.query[key] || 0)
    if (Number.isInteger(number) && number > 0) params[key] = number
  }
  return params
})
const imageUrl = computed(() => {
  const params = new URLSearchParams(contextParams.value).toString()
  return `${url.value}/image${params ? `?${params}` : ""}`
})
const validPolygon = computed(() => {
  const map = details.value?.roomMap
  return (
    map?.type === "polygon" &&
    Array.isArray(map.points) &&
    map.points.length >= 3 &&
    map.points.every(
      (p) =>
        Array.isArray(p) &&
        p.length === 2 &&
        p.every((n) => typeof n === "number" && Number.isFinite(n) && n >= 0 && n <= 1),
    )
  )
})
const polygonPoints = computed(() =>
  validPolygon.value ? details.value.roomMap.points.map((p) => `${p[0] * 1000},${p[1] * 1000}`).join(" ") : "",
)

watch(
  [url, contextParams, () => props.dialogMode],
  async (_, __, onCleanup) => {
    let active = true
    onCleanup(() => {
      active = false
    })

    loading.value = false
    loaded.value = false
    details.value = null
    error.value = ""
    imageFailed.value = false
    dialogVisible.value = false

    // Other callers keep their existing lazy-loaded inline viewer.
    if (!props.dialogMode || !Number(props.courseId || props.calendarId)) return

    loading.value = true
    try {
      const result = await baseService.get(url.value, contextParams.value)
      if (active) details.value = result
    } catch {
      if (active) error.value = t("Floor plan unavailable")
    } finally {
      if (active) {
        loaded.value = true
        loading.value = false
      }
    }
  },
  { immediate: true },
)

async function onToggle(event) {
  if (!event.target.open || loading.value || loaded.value) return
  if (!Number(props.courseId || props.calendarId)) return
  loading.value = true
  try {
    details.value = await baseService.get(url.value, contextParams.value)
  } catch {
    error.value = t("Floor plan unavailable")
  } finally {
    loaded.value = true
    loading.value = false
  }
}
</script>
