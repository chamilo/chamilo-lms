<template>
  <section class="mt-4 rounded border p-3">
    <h3 class="mb-2 font-semibold">{{ t("Room on floor plan") }}</h3>
    <p v-if="!branchId">{{ t("Select a branch first") }}</p>
    <p v-else-if="!hasPlan">{{ t("The branch has no attached floor plan") }}</p>
    <template v-else>
      <p class="mb-2 text-sm">{{ t("Use the polygon tool to outline the room; use Edit to move corners") }}</p>
      <div ref="mapElement" class="h-96 w-full overflow-hidden rounded border" :aria-label="t('Room on floor plan')" />
      <p v-if="mapError" role="alert" class="mt-2 text-sm text-red-600">{{ mapError }}</p>
      <div class="mt-3 flex flex-wrap gap-2">
        <BaseButton
          :label="t('Save room area')"
          icon="save"
          type="success"
          :disabled="!editablePolygon || !!mapError"
          @click="save"
        />
        <BaseButton
          :label="t('Clear room area')"
          icon="delete"
          type="danger"
          :disabled="!editablePolygon"
          @click="confirmClear"
        />
      </div>
      <p v-if="changed" role="status" class="mt-2 text-sm">
        {{ t("Save the room form to persist the area") }}
      </p>
    </template>
  </section>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import L from "leaflet"
import "leaflet/dist/leaflet.css"
import "leaflet-draw"
import "leaflet-draw/dist/leaflet.draw.css"
import baseService from "../../services/baseService"
import BaseButton from "../basecomponents/BaseButton.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import { fromLeafletPoints, imageUnits, toLeafletPoints } from "./roomFloorPlanGeometry.mjs"

const props = defineProps({
  branch: { type: [Object, String], default: null },
  modelValue: { type: Object, default: null },
})
const emit = defineEmits(["update:modelValue"])
const { t } = useI18n()
const { requireConfirmation } = useConfirmation()
const hasPlan = ref(false)
const mapError = ref("")
const mapElement = ref(null)
const changed = ref(false)
const editablePolygon = ref(false)
const branchId = computed(() => {
  const value = typeof props.branch === "object"
    ? props.branch?.id || props.branch?.["@id"]
    : props.branch
  const id = Number(String(value || "").split("/").pop())
  return Number.isInteger(id) && id > 0 ? id : 0
})
const imageUrl = computed(() => `/api/branches/${branchId.value}/floor-plan`)

let map = null
let drawnLayers = null
let requestId = 0
let destroyed = false
let resizeObserver = null
let currentUnits = { width: 1000, height: 1000 }

function currentPolygon() {
  return drawnLayers?.getLayers().find((layer) => layer instanceof L.Polygon) || null
}

function updateHasPolygon() {
  editablePolygon.value = Boolean(currentPolygon())
}

function setFromModel(value) {
  if (!drawnLayers) return
  drawnLayers.clearLayers()
  const latLngs = toLeafletPoints(value, currentUnits)
  if (latLngs.length) {
    drawnLayers.addLayer(L.polygon(latLngs, {
      color: "#00897b", weight: 3, fillOpacity: 0.25,
    }))
  }
  updateHasPolygon()
}

function cleanupMap() {
  if (resizeObserver) {
    resizeObserver.disconnect()
    resizeObserver = null
  }
  if (map) {
    map.remove()
    map = null
  }
  drawnLayers = null
  editablePolygon.value = false
}

function setupMap(units) {
  if (!mapElement.value || !hasPlan.value || destroyed) return
  cleanupMap()
  currentUnits = units
  const bounds = L.latLngBounds([[0, 0], [units.height, units.width]])
  map = L.map(mapElement.value, {
    crs: L.CRS.Simple,
    zoomSnap: 0.25,
    minZoom: -3,
    maxZoom: 4,
    attributionControl: false,
  })
  L.imageOverlay(imageUrl.value, bounds, { alt: t("Floor plan") })
    .on("error", () => { mapError.value = t("Floor plan unavailable") })
    .addTo(map)
  drawnLayers = L.featureGroup().addTo(map)
  setFromModel(props.modelValue)

  const control = new L.Control.Draw({
    draw: {
      polygon: {
        allowIntersection: false,
        showArea: false,
        shapeOptions: { color: "#00897b", weight: 3, fillOpacity: 0.25 },
      },
      polyline: false,
      rectangle: false,
      circle: false,
      circlemarker: false,
      marker: false,
    },
    edit: {
      featureGroup: drawnLayers,
      poly: { allowIntersection: false },
      remove: false,
    },
  })
  map.addControl(control)
  map.on(L.Draw.Event.CREATED, (event) => {
    // Each room has exactly one polygon. A new drawing replaces the old one.
    drawnLayers.clearLayers()
    drawnLayers.addLayer(event.layer)
    updateHasPolygon()
    changed.value = false
  })
  map.on(L.Draw.Event.EDITED, () => {
    updateHasPolygon()
    changed.value = false
  })
  map.fitBounds(bounds)
  if (typeof ResizeObserver !== "undefined") {
    resizeObserver = new ResizeObserver(() => map?.invalidateSize())
    resizeObserver.observe(mapElement.value)
  }
}

watch(branchId, async (id) => {
  const currentRequest = ++requestId
  cleanupMap()
  hasPlan.value = false
  changed.value = false
  mapError.value = ""
  if (!id) return
  try {
    const branch = await baseService.get(`/api/branches/${id}`)
    if (destroyed || currentRequest !== requestId) return
    hasPlan.value = Boolean(branch.floorPlanId)
    if (hasPlan.value) {
      await nextTick()
      const imageSize = await new Promise((resolve, reject) => {
        const image = new Image()
        image.onload = () => resolve(imageUnits(image.naturalWidth, image.naturalHeight))
        image.onerror = reject
        image.src = imageUrl.value
      })
      if (!destroyed && currentRequest === requestId && imageSize) setupMap(imageSize)
    }
  } catch (e) {
    if (!destroyed && currentRequest === requestId) mapError.value = t("Floor plan unavailable")
  }
}, { immediate: true })

watch(() => props.modelValue, (value) => {
  if (map) setFromModel(value)
}, { deep: true })

function save() {
  const polygon = currentPolygon()
  if (!polygon) return
  const latLngs = polygon.getLatLngs()[0]
  const roomMap = fromLeafletPoints(latLngs, currentUnits)
  if (!roomMap) {
    mapError.value = t("Room map points must be within the floor plan")
    return
  }
  mapError.value = ""
  emit("update:modelValue", roomMap)
  changed.value = true
}

function clear() {
  drawnLayers?.clearLayers()
  updateHasPolygon()
  emit("update:modelValue", null)
  changed.value = true
}

function confirmClear() {
  requireConfirmation({
    title: t("Clear room area"),
    message: t("Are you sure you want to clear the room area?"),
    accept: clear,
  })
}

onBeforeUnmount(() => {
  destroyed = true
  ++requestId
  cleanupMap()
})
</script>
