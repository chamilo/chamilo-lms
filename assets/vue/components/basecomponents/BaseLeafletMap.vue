<template>
  <div
    v-if="hasCoordinates"
    ref="mapContainer"
    :class="mapClass"
  />
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from "vue"
import L from "leaflet"
import "leaflet/dist/leaflet.css"
import "../../utils/leafletMarkerIcons"

const props = defineProps({
  latitude: {
    type: Number,
    default: null,
  },
  longitude: {
    type: Number,
    default: null,
  },
  draggable: {
    type: Boolean,
    default: false,
  },
  zoom: {
    type: Number,
    default: 15,
  },
  mapClass: {
    type: String,
    default: "h-48 w-full rounded border border-gray-30",
  },
})

const emit = defineEmits(["marker-moved"])

const hasCoordinates = computed(() => Number.isFinite(props.latitude) && Number.isFinite(props.longitude))

const mapContainer = ref(null)
let leafletMap = null
let leafletMarker = null

function renderMap() {
  const latLng = [props.latitude, props.longitude]

  if (!leafletMap) {
    leafletMap = L.map(mapContainer.value).setView(latLng, props.zoom)
    L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      maxZoom: 19,
    }).addTo(leafletMap)

    leafletMarker = L.marker(latLng, { draggable: props.draggable }).addTo(leafletMap)

    if (props.draggable) {
      leafletMarker.on("dragend", () => {
        const { lat, lng } = leafletMarker.getLatLng()
        emit("marker-moved", { latitude: lat, longitude: lng })
      })
    }

    return
  }

  leafletMap.setView(latLng)
  leafletMarker.setLatLng(latLng)
}

watch(
  [() => props.latitude, () => props.longitude],
  async () => {
    if (!hasCoordinates.value) {
      leafletMap?.remove()
      leafletMap = null
      leafletMarker = null

      return
    }

    await nextTick()

    if (mapContainer.value) {
      renderMap()
    }
  },
  { immediate: true },
)

onBeforeUnmount(() => {
  leafletMap?.remove()
})
</script>
