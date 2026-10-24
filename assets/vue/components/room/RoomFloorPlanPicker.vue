<template>
  <details
    v-if="groups.length"
    class="mt-2 rounded border p-3"
  >
    <summary class="cursor-pointer font-medium">{{ t("Floor plan") }}</summary>
    <p class="mb-2 mt-2 text-sm">{{ t("Click a highlighted room to select it") }}</p>
    <section
      v-for="group in groups"
      :key="group.id"
      class="mb-3"
    >
      <h4 class="mb-1 font-medium">{{ group.title }}</h4>
      <div class="relative w-full overflow-hidden rounded border">
        <img
          :src="imageUrl(group.id)"
          :alt="t('Floor plan')"
          class="block w-full"
        />
        <svg
          viewBox="0 0 1000 1000"
          preserveAspectRatio="none"
          class="absolute inset-0 h-full w-full"
        >
          <polygon
            v-for="room in group.rooms"
            :key="room.id"
            :points="polygonPoints(room.roomMap)"
            :fill="String(room.id) === String(modelValue) ? '#1976d2' : '#00897b'"
            fill-opacity="0.45"
            stroke="#004d40"
            stroke-width="3"
            class="cursor-pointer"
            role="button"
            tabindex="0"
            :aria-label="`${t('Select room')}: ${room.name}`"
            @click="emit('update:modelValue', room.id)"
            @keydown.enter.prevent="emit('update:modelValue', room.id)"
            @keydown.space.prevent="emit('update:modelValue', room.id)"
          >
            <title>{{ room.name }}</title>
          </polygon>
        </svg>
      </div>
    </section>
  </details>
</template>

<script setup>
import { computed, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import baseService from "../../services/baseService"

const props = defineProps({
  rooms: { type: Array, default: () => [] },
  modelValue: { type: [String, Number], default: null },
})
const emit = defineEmits(["update:modelValue"])
const { t } = useI18n()
const route = useRoute()
function imageUrl(branchId) {
  const params = new URLSearchParams()
  const cid = Number(route.query.cid || 0)
  const sid = Number(route.query.sid || 0)
  if (Number.isInteger(cid) && cid > 0) params.set("cid", String(cid))
  if (Number.isInteger(sid) && sid > 0) params.set("sid", String(sid))
  const query = params.toString()
  return `/api/branches/${branchId}/floor-plan${query ? `?${query}` : ""}`
}
const branchDetails = ref({})
const validRooms = computed(() =>
  props.rooms.filter((room) => {
    const points = room.roomMap?.points
    return (
      room.roomMap?.type === "polygon" &&
      Array.isArray(points) &&
      points.length >= 3 &&
      points.every(
        (p) =>
          Array.isArray(p) &&
          p.length === 2 &&
          p.every((v) => typeof v === "number" && Number.isFinite(v) && v >= 0 && v <= 1),
      )
    )
  }),
)
const groups = computed(() => {
  const result = new Map()
  for (const room of validRooms.value) {
    const id = parseId(room.branch?.id || room.branch?.["@id"] || room.branch)
    if (!id || !branchDetails.value[id]?.floorPlanId) continue
    if (!result.has(id)) result.set(id, { id, title: branchDetails.value[id].title, rooms: [] })
    result.get(id).rooms.push(room)
  }
  return [...result.values()]
})
function parseId(value) {
  return Number(
    String(value || "")
      .split("/")
      .pop() || 0,
  )
}
function polygonPoints(map) {
  return map.points.map((p) => `${p[0] * 1000},${p[1] * 1000}`).join(" ")
}
watch(
  validRooms,
  async (rooms) => {
    const ids = [...new Set(rooms.map((r) => parseId(r.branch?.id || r.branch?.["@id"] || r.branch)).filter(Boolean))]
    const rows = await Promise.all(
      ids.map(async (id) => {
        try {
          const branch = await baseService.get(`/api/branches/${id}`)
          return [id, branch]
        } catch (e) {
          console.error("Could not retrieve branch floor plan", e)
          return [id, null]
        }
      }),
    )
    branchDetails.value = Object.fromEntries(rows.filter(([, branch]) => branch))
  },
  { immediate: true },
)
</script>
