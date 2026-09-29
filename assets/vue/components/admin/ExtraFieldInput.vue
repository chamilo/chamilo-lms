<template>
  <BaseInputText
    v-if="1 === field.valueType"
    :id="inputId"
    v-model="textModel"
    :disabled="disabled"
    :error-text="effectiveErrorText"
    form-submitted
    :help-text="field.helperText"
    :is-invalid="effectiveIsInvalid"
    :label="field.displayText"
    :name="inputId"
  />

  <BaseTextArea
    v-else-if="2 === field.valueType"
    :id="inputId"
    v-model="textModel"
    :disabled="disabled"
    :error-text="effectiveErrorText"
    :is-invalid="effectiveIsInvalid"
    :label="field.displayText"
    :name="inputId"
    rows="3"
  />

  <div v-else-if="3 === field.valueType">
    <BaseRadioButtons
      v-model="textModel"
      :disabled="disabled"
      :name="inputId"
      :options="optionItems"
      :title="field.displayText"
    />
    <small
      v-if="effectiveIsInvalid && effectiveErrorText"
      class="p-error block"
    >
      {{ effectiveErrorText }}
    </small>
  </div>

  <BaseSelect
    v-else-if="4 === field.valueType || TIMEZONE === field.valueType"
    :id="inputId"
    v-model="textModel"
    :disabled="disabled"
    :error-text="effectiveErrorText"
    :is-invalid="effectiveIsInvalid"
    :label="field.displayText"
    :options="optionItems"
    allow-clear
  />

  <BaseMultiSelect
    v-else-if="isMultipleValueType"
    v-model="arrayModel"
    :disabled="disabled"
    :error-text="effectiveErrorText"
    :input-id="inputId"
    :is-invalid="effectiveIsInvalid"
    :label="field.displayText"
    :options="optionItems"
  />

  <!-- Free-text tags (no predefined options): a real chip input, not a plain
       text field -- typing "," or a space commits the current text as its own
       chip immediately, instead of letting it sit as ambiguous literal text
       (which the server would otherwise save as ONE tag containing a comma). -->
  <div v-else-if="TAG === field.valueType">
    <label
      class="mb-1 block text-body-2 font-medium text-gray-90"
      :for="inputId"
    >
      {{ field.displayText }}
    </label>
    <InputChips
      v-model="arrayModel"
      add-on-blur
      class="w-full"
      :disabled="disabled"
      :input-id="inputId"
      :invalid="effectiveIsInvalid"
      :placeholder="t('Add tags')"
      separator="[,\s]"
    />
    <small
      v-if="effectiveIsInvalid && effectiveErrorText"
      class="p-error block"
    >
      {{ effectiveErrorText }}
    </small>
  </div>

  <BaseCalendar
    v-else-if="6 === field.valueType"
    :id="inputId"
    v-model="dateModel"
    :disabled="disabled"
    :error-text="effectiveErrorText"
    :is-invalid="effectiveIsInvalid"
    :label="field.displayText"
  />

  <BaseCalendar
    v-else-if="7 === field.valueType"
    :id="inputId"
    v-model="dateModel"
    :disabled="disabled"
    :error-text="effectiveErrorText"
    :is-invalid="effectiveIsInvalid"
    :label="field.displayText"
    show-time
  />

  <BaseInputNumber
    v-else-if="15 === field.valueType"
    :id="inputId"
    v-model="numberModel"
    :disabled="disabled"
    :error-text="effectiveErrorText"
    :is-invalid="effectiveIsInvalid"
    :label="field.displayText"
  />

  <BaseInputNumber
    v-else-if="17 === field.valueType"
    :id="inputId"
    v-model="numberModel"
    :disabled="disabled"
    :error-text="effectiveErrorText"
    :is-invalid="effectiveIsInvalid"
    :label="field.displayText"
    :step="0.1"
  />

  <div v-else-if="13 === field.valueType">
    <BaseCheckbox
      :id="inputId"
      v-model="booleanModel"
      :disabled="disabled"
      :label="field.displayText"
      :name="inputId"
    />
    <small
      v-if="effectiveIsInvalid && effectiveErrorText"
      class="p-error block"
    >
      {{ effectiveErrorText }}
    </small>
  </div>

  <!-- Not a real input -- a section-separator heading, matching the legacy
       "panel-separator" <h4>. Nothing to bind, nothing submitted. -->
  <div
    v-else-if="DIVIDER === field.valueType"
    class="mt-2 border-t border-gray-30 pt-3"
  >
    <h4 class="text-body-1 font-semibold text-gray-90">
      {{ field.displayText }}
    </h4>
  </div>

  <!-- Plain text fields that share the same shape (one string, one optional
       client-side rule) but differ in placeholder/help/regex: social network
       link (no rule in legacy either), mobile phone number, video URL,
       letters/alphanumeric (+ space) variants, and duration (hh:mm:ss). -->
  <BaseInputText
    v-else-if="isRuledTextType"
    :id="inputId"
    v-model="textModel"
    :disabled="disabled"
    :error-text="effectiveErrorText"
    form-submitted
    :help-text="ruledTextHelpText"
    :is-invalid="effectiveIsInvalid"
    :label="field.displayText"
    :name="inputId"
    :placeholder="ruledTextPlaceholder"
  />

  <!-- No Google Maps-backed picker exists in this app (see CLAUDE.md); this
       mirrors legacy's own fallback when the GoogleMaps plugin isn't enabled
       -- a plain address field plus a "lat,lng" coordinates field, previewed
       on a free OpenStreetMap/Leaflet map once coordinates are set.
       Wrapped in a Fieldset so the related inputs are visually grouped
       with a border + legend when several geolocalization fields appear on
       the same form (e.g. "City" and "City of internship"). -->
  <Fieldset
    v-else-if="GEOLOCALIZATION === field.valueType || GEOLOCALIZATION_COORDINATES === field.valueType"
    :legend="field.displayText"
  >
    <div class="flex flex-col gap-4">
      <BaseInputText
        :id="inputId"
        v-model="geoAddress"
        :disabled="disabled"
        :label="t('Address')"
        :name="inputId"
      />
      <div>
        <div
          v-if="geocodingAvailable"
          class="mb-2"
        >
          <BaseButton
            :disabled="disabled || !geoAddress || isGeocoding"
            icon="crosshairs"
            :is-loading="isGeocoding"
            :label="t('Search for this location')"
            size="small"
            type="primary"
            @click="lookupCoordinates"
          />
        </div>
        <BaseInputText
          :id="`${inputId}_coordinates`"
          v-model="geoCoordinates"
          :disabled="disabled"
          :label="t('Coordinates')"
          :name="`${inputId}_coordinates`"
          placeholder="latitude,longitude"
        />
        <small
          v-if="geocodeError"
          class="p-error mt-1 block"
        >
          {{ geocodeError }}
        </small>
        <div
          v-if="parsedCoordinates"
          ref="mapContainer"
          class="mt-2 h-48 w-full rounded border border-gray-30"
        />
      </div>
    </div>
  </Fieldset>

  <!-- Cascading selects: double (2 levels), triple (3 levels), and
       select-with-text (1 select + 1 free-text field). Options are supplied
       flat with id/parentId pairs; children are filtered client-side instead
       of porting the legacy AJAX round-trip. -->
  <div v-else-if="isHierarchicalSelectType">
    <label
      class="mb-1 block text-body-2 font-medium text-gray-90"
      :for="inputId"
    >
      {{ field.displayText }}
    </label>
    <div class="flex flex-wrap gap-4">
      <BaseSelect
        :id="inputId"
        v-model="level1Value"
        allow-clear
        :disabled="disabled"
        label=""
        :options="level1Options"
        :placeholder="t('Please select an option')"
      />
      <BaseSelect
        v-if="DOUBLE_SELECT === field.valueType || TRIPLE_SELECT === field.valueType"
        v-model="level2Value"
        allow-clear
        :disabled="disabled || !level1Value"
        label=""
        :options="level2Options"
        :placeholder="t('Please select an option')"
      />
      <BaseInputText
        v-else-if="SELECT_WITH_TEXT_FIELD === field.valueType"
        v-model="level2Value"
        :disabled="disabled || !level1Value"
        label=""
        name=""
      />
      <BaseSelect
        v-if="TRIPLE_SELECT === field.valueType"
        v-model="level3Value"
        allow-clear
        :disabled="disabled || !level2Value"
        label=""
        :options="level3Options"
        :placeholder="t('Please select an option')"
      />
    </div>
  </div>

  <!-- File / image upload -- value is either the existing asset URL (string,
       untouched), a freshly-picked File object, or {remove: true} once the
       user clears it. The parent form's FormData builder branches on this
       same shape when serializing. -->
  <div
    v-else-if="isFileLikeType"
    class="flex flex-col gap-2"
  >
    <label
      class="mb-1 block text-body-2 font-medium text-gray-90"
      :for="inputId"
    >
      {{ field.displayText }}
    </label>

    <img
      v-if="FILE_IMAGE === field.valueType && (stagedPreviewUrl || existingUrl)"
      :alt="field.displayText"
      class="max-h-40 rounded border border-gray-25 object-contain"
      :src="stagedPreviewUrl || existingUrl"
    />
    <a
      v-else-if="FILE === field.valueType && existingUrl && !stagedFile"
      class="text-body-2 text-primary underline"
      :href="existingUrl"
      rel="noopener"
      target="_blank"
    >
      {{ existingFileName }}
    </a>

    <div class="flex items-center gap-2">
      <input
        :id="inputId"
        :accept="FILE_IMAGE === field.valueType ? 'image/*' : undefined"
        class="text-body-2"
        :disabled="disabled"
        type="file"
        @change="onFileSelected"
      />
      <BaseButton
        v-if="!isCleared && (existingUrl || stagedFile)"
        icon="delete"
        :label="t('Delete')"
        only-icon
        size="small"
        type="danger-text"
        @click="clearFile"
      />
    </div>
    <small
      v-if="isCleared"
      class="text-body-2 text-gray-60"
    >
      {{ t("Deleted") }}
    </small>
    <small
      v-if="fileTypeError"
      class="p-error block"
    >
      {{ fileTypeError }}
    </small>
    <small
      v-if="isInvalid && errorText"
      class="p-error block"
    >
      {{ errorText }}
    </small>
  </div>

  <BaseInputText
    v-else
    :id="inputId"
    v-model="textModel"
    :disabled="disabled"
    :error-text="effectiveErrorText"
    form-submitted
    :help-text="t('This field type is not fully supported yet — raw value only.')"
    :is-invalid="effectiveIsInvalid"
    :label="field.displayText"
    :name="inputId"
  />
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import InputChips from "primevue/inputchips"
import Fieldset from "primevue/fieldset"
import L from "leaflet"
import "leaflet/dist/leaflet.css"
import markerIcon2x from "leaflet/dist/images/marker-icon-2x.png"
import markerIcon from "leaflet/dist/images/marker-icon.png"
import markerShadow from "leaflet/dist/images/marker-shadow.png"
import geocodingService from "../../services/geocodingService"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseCalendar from "../basecomponents/BaseCalendar.vue"
import BaseCheckbox from "../basecomponents/BaseCheckbox.vue"
import BaseInputNumber from "../basecomponents/BaseInputNumber.vue"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import BaseMultiSelect from "../basecomponents/BaseMultiSelect.vue"
import BaseRadioButtons from "../basecomponents/BaseRadioButtons.vue"
import BaseSelect from "../basecomponents/BaseSelect.vue"
import BaseTextArea from "../basecomponents/BaseTextArea.vue"

// Webpack renames Leaflet's default marker images, breaking its built-in
// relative-path lookup; point it at the bundled URLs instead (well-known
// Leaflet + bundler fix, see https://github.com/Leaflet/Leaflet/issues/4968).
delete L.Icon.Default.prototype._getIconUrl
L.Icon.Default.mergeOptions({
  iconRetinaUrl: markerIcon2x,
  iconUrl: markerIcon,
  shadowUrl: markerShadow,
})

// Mirrors Chamilo\CoreBundle\Entity\ExtraField's FIELD_TYPE_* constants.
const SELECT_MULTIPLE = 5
const DOUBLE_SELECT = 8
const DIVIDER = 9
const TAG = 10
const TIMEZONE = 11
const SOCIAL_PROFILE = 12
const CHECKBOX = 13
const MOBILE_PHONE_NUMBER = 14
const FILE_IMAGE = 16
const FILE = 18
const VIDEO_URL = 19
const LETTERS_ONLY = 20
const ALPHANUMERIC = 21
const LETTERS_SPACE = 22
const ALPHANUMERIC_SPACE = 23
const GEOLOCALIZATION = 24
const GEOLOCALIZATION_COORDINATES = 25
const SELECT_WITH_TEXT_FIELD = 26
const TRIPLE_SELECT = 27
const DURATION = 28

const { t } = useI18n()

// Legacy's FormValidator rules (public/main/inc/lib/formvalidator/FormValidator.class.php)
// for the plain-text-shaped types. Kept as a plain object (not computed) since the
// translated messages only need to be read lazily, when a value actually fails.
const RULED_TEXT_CONFIG = {
  [SOCIAL_PROFILE]: {
    placeholder: "https://",
  },
  [MOBILE_PHONE_NUMBER]: {
    placeholder: "(xx)xxxxxxxxx",
    help: () => t("Include the country dial code"),
    filter: (value) => value.replace(/[+()]/g, "").replace(/^0+/, ""),
    pattern: /^\d{11}$/,
    message: () => t("Mobile phone number is incomplete or contains invalid characters"),
  },
  [VIDEO_URL]: {
    placeholder: "https://",
    validate: (value) => {
      try {
        new URL(value)
        return true
      } catch {
        return false
      }
    },
    message: () => t("Invalid format"),
  },
  [LETTERS_ONLY]: {
    pattern: /^[a-zA-ZñÑ]+$/,
    message: () => t("Only letters"),
  },
  [ALPHANUMERIC]: {
    pattern: /^[a-zA-Z0-9ñÑ]+$/,
    message: () => t("Only letters (a-z) and numbers (0-9)"),
  },
  [LETTERS_SPACE]: {
    pattern: /^[a-zA-ZñÑ\s]+$/,
    message: () => t("Only letters and spaces"),
  },
  [ALPHANUMERIC_SPACE]: {
    pattern: /^[a-zA-Z0-9ñÑ\s]+$/,
    message: () => t("Only letters, numbers and spaces"),
  },
  [DURATION]: {
    placeholder: "hh:mm:ss",
    pattern: /^\d+:[0-5]?\d:[0-5]?\d$/,
    message: () => t("Invalid format"),
  },
}

const props = defineProps({
  field: {
    type: Object,
    required: true,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  isInvalid: {
    type: Boolean,
    default: false,
  },
  errorText: {
    type: String,
    default: "",
  },
})

const model = defineModel({ default: "" })

const inputId = computed(() => `extra_${props.field.variable}`)
const optionItems = computed(() => props.field.options.map((o) => ({ label: o.label, value: o.value })))
const isMultipleValueType = computed(
  () =>
    (SELECT_MULTIPLE === props.field.valueType || CHECKBOX === props.field.valueType) && optionItems.value.length > 0,
)

const textModel = computed({
  get: () => (Array.isArray(model.value) ? "" : (model.value ?? "")),
  set: (value) => {
    model.value = value
  },
})

const arrayModel = computed({
  get: () => (Array.isArray(model.value) ? model.value : []),
  set: (value) => {
    model.value = value
  },
})

const dateModel = computed({
  get: () => (model.value ? new Date(model.value) : null),
  set: (value) => {
    model.value = value ? new Date(value).toISOString() : ""
  },
})

const numberModel = computed({
  get: () => (model.value ? Number(model.value) : null),
  set: (value) => {
    model.value = null === value || undefined === value ? "" : String(value)
  },
})

const booleanModel = computed({
  get: () => "1" === model.value,
  set: (value) => {
    model.value = value ? "1" : "0"
  },
})

// --- Ruled plain-text types (social profile, mobile phone, video URL,
// letters/alphanumeric [+space] variants, duration) ------------------------

const isRuledTextType = computed(() => Object.hasOwn(RULED_TEXT_CONFIG, props.field.valueType))
const ruledTextPlaceholder = computed(() => RULED_TEXT_CONFIG[props.field.valueType]?.placeholder || "")
const ruledTextHelpText = computed(() => {
  const rule = RULED_TEXT_CONFIG[props.field.valueType]
  return rule?.help ? rule.help() : props.field.helperText || ""
})

const clientValidationError = computed(() => {
  const rule = RULED_TEXT_CONFIG[props.field.valueType]
  const raw = textModel.value
  if (!rule || !raw) {
    return ""
  }
  const value = rule.filter ? rule.filter(raw) : raw
  const isValid = rule.validate ? rule.validate(value) : !rule.pattern || rule.pattern.test(value)

  return isValid ? "" : rule.message()
})

const effectiveIsInvalid = computed(() => props.isInvalid || Boolean(clientValidationError.value))
const effectiveErrorText = computed(() => props.errorText || clientValidationError.value)

// --- Geolocation: address + "lat,lng" coordinates + a read-only preview map

const geoModel = computed({
  get: () => (Array.isArray(model.value) ? model.value : [model.value || "", ""]),
  set: (value) => {
    model.value = value
  },
})
const geoAddress = computed({
  get: () => geoModel.value[0] || "",
  set: (value) => {
    geoModel.value = [value, geoModel.value[1] || ""]
  },
})
const geoCoordinates = computed({
  get: () => geoModel.value[1] || "",
  set: (value) => {
    geoModel.value = [geoModel.value[0] || "", value]
  },
})

const geocodingAvailable = ref(false)
const isGeocoding = ref(false)
const geocodeError = ref("")

async function lookupCoordinates() {
  if (!geoAddress.value || isGeocoding.value) {
    return
  }

  isGeocoding.value = true
  geocodeError.value = ""

  try {
    const result = await geocodingService.lookup(geoAddress.value)
    geoCoordinates.value = `${result.lat},${result.lon}`
  } catch {
    geocodeError.value = t("An error occurred")
  } finally {
    isGeocoding.value = false
  }
}

// A free OpenStreetMap tile layer (no key, same non-commercial usage policy
// family as Nominatim) to preview the current coordinates. Read-only: it
// just follows geoCoordinates, it does not let the user set them by clicking.
const parsedCoordinates = computed(() => {
  const parts = geoCoordinates.value.split(",").map((part) => parseFloat(part.trim()))

  if (2 !== parts.length || parts.some((part) => Number.isNaN(part))) {
    return null
  }

  return { lat: parts[0], lng: parts[1] }
})

const mapContainer = ref(null)
let leafletMap = null
let leafletMarker = null

function showMap(coordinates) {
  const latLng = [coordinates.lat, coordinates.lng]

  if (!leafletMap) {
    leafletMap = L.map(mapContainer.value).setView(latLng, 15)
    L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      maxZoom: 19,
    }).addTo(leafletMap)
    leafletMarker = L.marker(latLng).addTo(leafletMap)

    return
  }

  leafletMap.setView(latLng)
  leafletMarker.setLatLng(latLng)
}

watch(parsedCoordinates, async (coordinates) => {
  if (!coordinates) {
    leafletMap?.remove()
    leafletMap = null
    leafletMarker = null

    return
  }

  await nextTick()

  if (mapContainer.value) {
    showMap(coordinates)
  }
})

onMounted(async () => {
  if (GEOLOCALIZATION === props.field.valueType || GEOLOCALIZATION_COORDINATES === props.field.valueType) {
    try {
      const data = await geocodingService.status()
      geocodingAvailable.value = Boolean(data?.available)
    } catch {
      geocodingAvailable.value = false
    }
  }

  if (parsedCoordinates.value) {
    await nextTick()

    if (mapContainer.value) {
      showMap(parsedCoordinates.value)
    }
  }
})

onBeforeUnmount(() => {
  leafletMap?.remove()
})

// --- Cascading selects (double / triple / select-with-text) ---------------

const isHierarchicalSelectType = computed(() =>
  [DOUBLE_SELECT, SELECT_WITH_TEXT_FIELD, TRIPLE_SELECT].includes(props.field.valueType),
)

function childrenOf(parentId) {
  if ("" === parentId || null === parentId || undefined === parentId) {
    return []
  }

  return (props.field.options || [])
    .filter((option) => String(option.parentId) === String(parentId))
    .map((option) => ({ label: option.label, value: String(option.id) }))
}

const hierarchyModel = computed({
  get: () => (Array.isArray(model.value) ? model.value : ["", "", ""]),
  set: (value) => {
    model.value = value
  },
})

const level1Options = computed(() => childrenOf(0))
const level1Value = computed({
  get: () => hierarchyModel.value[0] || "",
  set: (value) => {
    hierarchyModel.value = [value, "", ""]
  },
})

const level2Options = computed(() => childrenOf(level1Value.value))
const level2Value = computed({
  get: () => hierarchyModel.value[1] || "",
  set: (value) => {
    hierarchyModel.value = [hierarchyModel.value[0] || "", value, ""]
  },
})

const level3Options = computed(() => childrenOf(level2Value.value))
const level3Value = computed({
  get: () => hierarchyModel.value[2] || "",
  set: (value) => {
    hierarchyModel.value = [hierarchyModel.value[0] || "", hierarchyModel.value[1] || "", value]
  },
})

// --- File / image upload ---------------------------------------------------

const ALLOWED_IMAGE_EXTENSIONS = ["jpg", "jpeg", "png", "gif"]

const isFileLikeType = computed(() => [FILE_IMAGE, FILE].includes(props.field.valueType))
const existingUrl = computed(() => ("string" === typeof model.value ? model.value : ""))
const existingFileName = computed(() => existingUrl.value.split("/").pop() || existingUrl.value)
const stagedFile = computed(() => (model.value instanceof File ? model.value : null))
const isCleared = computed(() => Boolean(model.value && "object" === typeof model.value && model.value.remove))
const stagedPreviewUrl = ref("")
const fileTypeError = ref("")

watch(stagedFile, (file) => {
  if (stagedPreviewUrl.value) {
    URL.revokeObjectURL(stagedPreviewUrl.value)
  }
  stagedPreviewUrl.value = file && FILE_IMAGE === props.field.valueType ? URL.createObjectURL(file) : ""
})

onBeforeUnmount(() => {
  if (stagedPreviewUrl.value) {
    URL.revokeObjectURL(stagedPreviewUrl.value)
  }
})

function onFileSelected(event) {
  const file = event.target.files?.[0]
  if (file) {
    const extension = file.name.split(".").pop()?.toLowerCase()
    if (FILE_IMAGE === props.field.valueType && !ALLOWED_IMAGE_EXTENSIONS.includes(extension)) {
      fileTypeError.value = `${t("Only PNG, JPG or GIF images allowed")} (${ALLOWED_IMAGE_EXTENSIONS.join(",")})`
      event.target.value = ""
      return
    }
    fileTypeError.value = ""
    model.value = file
  }
  event.target.value = ""
}

function clearFile() {
  model.value = { remove: true }
}
</script>
