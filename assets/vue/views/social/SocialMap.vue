<template>
  <div class="social-map flex flex-col gap-4">
    <BaseCard>
      <template #header>
        <div class="-mb-2 bg-gray-15 px-4 py-2">
          <h2 class="text-h5">{{ t("Social map") }}</h2>
        </div>
      </template>

      <div
        v-if="isLoading"
        class="py-8 text-center text-gray-50"
      >
        {{ t("Loading") }}
      </div>

      <div
        v-else-if="!mapConfig.enabled"
        class="rounded-lg border border-warning bg-warning/10 p-4 text-warning"
      >
        {{ t("The map plugin is not enabled or configured.") }}
      </div>

      <div
        v-else
        class="flex flex-col gap-4"
      >
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <p class="text-body-2 text-gray-50">
              {{ providerLabel }}
            </p>
            <p
              v-if="missingFields.length"
              class="mt-1 text-body-2 text-warning"
            >
              {{ t("Some configured extra fields were not found") }}: {{ missingFields.join(", ") }}
            </p>
          </div>
          <BaseButton
            icon="refresh"
            :label="t('Refresh')"
            type="secondary"
            @click="loadMapData"
          />
        </div>

        <div
          v-if="!markers.length"
          class="rounded-lg border border-gray-20 bg-support-1 p-6 text-center text-gray-50"
        >
          {{ t("No user locations were found.") }}
        </div>

        <template v-else>
          <div
            v-if="isGoogleProvider"
            ref="googleMapRef"
            class="h-[520px] w-full overflow-hidden rounded-xl border border-gray-25"
          />

          <div
            v-else
            class="overflow-hidden rounded-xl border border-gray-25"
          >
            <iframe
              v-if="openStreetMapEmbedUrl"
              class="h-[520px] w-full border-0"
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"
              :src="openStreetMapEmbedUrl"
              title="OpenStreetMap"
            />
            <div
              v-else
              class="bg-support-1 p-6 text-center text-gray-50"
            >
              {{ t("OpenStreetMap needs coordinates to display an embedded marker. Address-only locations are listed below.") }}
            </div>
          </div>

          <BaseCard class="border border-gray-20 shadow-none">
            <template #header>
              <div class="-mb-2 bg-gray-15 px-4 py-2">
                <h3 class="text-body-1 font-semibold">{{ t("Locations") }}</h3>
              </div>
            </template>

            <ul class="divide-y divide-gray-20">
              <li
                v-for="marker in markers"
                :key="`${marker.userId}-${marker.field}-${marker.label}`"
                class="flex flex-col gap-2 py-3 md:flex-row md:items-center md:justify-between"
              >
                <div>
                  <BaseAppLink
                    :to="marker.profileUrl"
                    class="font-semibold text-primary"
                  >
                    {{ marker.userName }}
                  </BaseAppLink>
                  <div class="text-body-2 text-gray-70">
                    {{ marker.label || marker.address }}
                  </div>
                  <div class="text-caption text-gray-50">
                    {{ marker.fieldLabel || marker.field }}
                  </div>
                </div>
                <a
                  class="inline-flex items-center gap-1 text-body-2 text-primary hover:underline"
                  :href="getExternalMapUrl(marker)"
                  rel="noopener noreferrer"
                  target="_blank"
                >
                  <span
                    aria-hidden="true"
                    class="mdi mdi-map-marker"
                  />
                  {{ t("Open map") }}
                </a>
              </li>
            </ul>
          </BaseCard>
        </template>
      </div>
    </BaseCard>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseAppLink from "../../components/basecomponents/BaseAppLink.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import socialService from "../../services/socialService"

const { t } = useI18n()

const isLoading = ref(true)
const mapConfig = ref({
  enabled: false,
  provider: null,
  apiKey: null,
  defaultCenter: { lat: 20, lng: 0 },
  defaultZoom: 2,
})
const markers = ref([])
const missingFields = ref([])
const googleMapRef = ref(null)

const isGoogleProvider = computed(() => mapConfig.value.provider === "google_maps")
const providerLabel = computed(() => {
  if (mapConfig.value.provider === "openstreetmap") {
    return t("Provider: OpenStreetMap")
  }

  return t("Provider: Google Maps")
})

const firstMarkerWithCoordinates = computed(() => {
  return markers.value.find((marker) => hasCoordinates(marker)) || null
})

const openStreetMapEmbedUrl = computed(() => {
  const marker = firstMarkerWithCoordinates.value

  if (!marker) {
    return ""
  }

  const lat = Number(marker.lat)
  const lng = Number(marker.lng)
  const delta = 0.05
  const bbox = [lng - delta, lat - delta, lng + delta, lat + delta].join(",")

  return `https://www.openstreetmap.org/export/embed.html?bbox=${encodeURIComponent(bbox)}&layer=mapnik&marker=${encodeURIComponent(`${lat},${lng}`)}`
})

onMounted(() => loadMapData())

async function loadMapData() {
  isLoading.value = true

  try {
    const [configResponse, markersResponse] = await Promise.all([
      socialService.getMapConfig(),
      socialService.getMapMarkers(),
    ])

    mapConfig.value = {
      enabled: Boolean(configResponse?.enabled),
      provider: configResponse?.provider || null,
      apiKey: configResponse?.apiKey || null,
      defaultCenter: configResponse?.defaultCenter || { lat: 20, lng: 0 },
      defaultZoom: Number(configResponse?.defaultZoom || 2),
    }
    markers.value = Array.isArray(markersResponse?.markers) ? markersResponse.markers : []
    missingFields.value = Array.isArray(markersResponse?.missingFields) ? markersResponse.missingFields : []

    await nextTick()

    if (mapConfig.value.enabled && isGoogleProvider.value && markers.value.length) {
      await renderGoogleMap()
    }
  } catch (error) {
    console.error("[SocialMap] Failed to load map data", error)
    mapConfig.value.enabled = false
    markers.value = []
  } finally {
    isLoading.value = false
  }
}

function hasCoordinates(marker) {
  return Number.isFinite(Number(marker?.lat)) && Number.isFinite(Number(marker?.lng))
}

function getExternalMapUrl(marker) {
  if (hasCoordinates(marker)) {
    const lat = Number(marker.lat)
    const lng = Number(marker.lng)

    if (isGoogleProvider.value) {
      return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(`${lat},${lng}`)}`
    }

    return `https://www.openstreetmap.org/?mlat=${encodeURIComponent(lat)}&mlon=${encodeURIComponent(lng)}#map=15/${encodeURIComponent(lat)}/${encodeURIComponent(lng)}`
  }

  const query = marker?.address || marker?.label || ""

  if (isGoogleProvider.value) {
    return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(query)}`
  }

  return `https://www.openstreetmap.org/search?query=${encodeURIComponent(query)}`
}

async function renderGoogleMap() {
  if (!mapConfig.value.apiKey) {
    return
  }

  await loadGoogleMapsApi(mapConfig.value.apiKey)

  if (!googleMapRef.value || !window.google?.maps) {
    return
  }

  const defaultCenter = {
    lat: Number(mapConfig.value.defaultCenter?.lat || 20),
    lng: Number(mapConfig.value.defaultCenter?.lng || 0),
  }
  const map = new window.google.maps.Map(googleMapRef.value, {
    center: defaultCenter,
    zoom: Number(mapConfig.value.defaultZoom || 2),
    mapTypeControl: true,
  })
  const bounds = new window.google.maps.LatLngBounds()
  const infoWindow = new window.google.maps.InfoWindow()
  const geocoder = new window.google.maps.Geocoder()
  let markersAdded = 0

  for (const marker of markers.value) {
    const position = await resolveGoogleMarkerPosition(marker, geocoder)

    if (!position) {
      continue
    }

    const mapMarker = new window.google.maps.Marker({
      map,
      position,
      title: marker.userName || marker.label || marker.address || "",
    })

    mapMarker.addListener("click", () => {
      infoWindow.setContent(buildInfoWindowContent(marker))
      infoWindow.open(map, mapMarker)
    })

    bounds.extend(position)
    markersAdded++
  }

  if (markersAdded > 0) {
    map.fitBounds(bounds)
  }
}

function resolveGoogleMarkerPosition(marker, geocoder) {
  if (hasCoordinates(marker)) {
    return Promise.resolve({
      lat: Number(marker.lat),
      lng: Number(marker.lng),
    })
  }

  const address = marker?.address || marker?.label

  if (!address) {
    return Promise.resolve(null)
  }

  return new Promise((resolve) => {
    geocoder.geocode({ address }, (results, status) => {
      if (status !== window.google.maps.GeocoderStatus.OK || !results?.[0]) {
        resolve(null)
        return
      }

      resolve(results[0].geometry.location)
    })
  })
}

function buildInfoWindowContent(marker) {
  return `
    <strong>${escapeHtml(marker.userName)}</strong><br>
    ${escapeHtml(marker.label || marker.address || "")}
  `
}

function escapeHtml(value) {
  return String(value || "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;")
}

function loadGoogleMapsApi(apiKey) {
  if (window.google?.maps) {
    return Promise.resolve()
  }

  const existingScript = document.querySelector("script[data-chamilo-google-maps-api]")

  if (existingScript) {
    return new Promise((resolve, reject) => {
      existingScript.addEventListener("load", resolve, { once: true })
      existingScript.addEventListener("error", reject, { once: true })
    })
  }

  return new Promise((resolve, reject) => {
    const script = document.createElement("script")
    script.async = true
    script.defer = true
    script.dataset.chamiloGoogleMapsApi = "true"
    script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}`
    script.addEventListener("load", resolve, { once: true })
    script.addEventListener("error", reject, { once: true })
    document.head.appendChild(script)
  })
}
</script>
